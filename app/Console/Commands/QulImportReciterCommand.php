<?php

namespace App\Console\Commands;

use App\Models\AyahAudio;
use App\Models\Reciter;
use App\Rendering\Ffmpeg;
use App\Support\QulClient;
use App\Support\QulDump;
use App\Support\SurahRange;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class QulImportReciterCommand extends Command
{
    protected $signature = 'qul:import-reciter
        {reciter=18 : QUL ayah recitation id (18=Mishari, 20=Husary, 16=Sudais)}
        {--file= : QUL recitation JSON or SQLite dump}
        {--surahs= : Comma/range list such as 1,36,112-114}
        {--download-audio : Download mp3 files into local library storage}';

    protected $description = 'Import a QUL ayah-by-ayah reciter and download its mp3s locally';

    public function handle(QulClient $client, Ffmpeg $ffmpeg): int
    {
        $reciterId = (string) $this->argument('reciter');
        $download = (bool) $this->option('download-audio');
        $surahs = SurahRange::parse($this->option('surahs')) ?? range(1, 114);
        $file = $this->option('file');

        $meta = $client->ayahRecitation($reciterId);
        $name = $meta['name'] ?? 'QUL reciter '.$reciterId;
        $style = $meta['style'] ?? null;

        if (filled($style) && ! str_contains($name, $style)) {
            $name .= ' ('.$style.')';
        }

        $reciter = Reciter::query()->updateOrCreate(
            ['qul_resource_id' => $reciterId],
            [
                'name' => $name,
                'granularity' => 'ayah',
            ],
        );

        try {
            $files = is_string($file) && $file !== ''
                ? QulDump::audioFrom($file)
                : $this->audioFromApi($client, $reciterId);
        } catch (InvalidArgumentException|JsonException|RuntimeException|RequestException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($files === []) {
            $this->error('No ayah audio in that dump or QUL recitation. Download JSON from https://qul.tarteel.ai/resources/recitation or pass a valid reciter id.');

            return self::FAILURE;
        }

        $disk = Storage::disk((string) config('bayan.disks.library'));
        $imported = 0;

        foreach ($files as $fileRow) {
            if (! in_array($fileRow['surah'], $surahs, true)) {
                continue;
            }

            $relative = "audio/{$reciter->id}/{$fileRow['surah']}/{$fileRow['ayah']}.mp3";
            $duration = $fileRow['duration_ms'];

            if ($download) {
                $response = Http::connectTimeout(10)
                    ->timeout(60)
                    ->retry([100, 500, 1000])
                    ->get($fileRow['audio_url']);

                if ($response->failed()) {
                    $this->warn("Skipped {$fileRow['surah']}:{$fileRow['ayah']} ({$response->status()})");

                    continue;
                }

                $disk->put($relative, $response->body());
                $duration = $ffmpeg->durationMs($disk->path($relative));
            }

            AyahAudio::query()->updateOrCreate(
                ['reciter_id' => $reciter->id, 'surah' => $fileRow['surah'], 'ayah' => $fileRow['ayah']],
                [
                    'path' => $relative,
                    'duration_ms' => $duration,
                ],
            );
            $imported++;
        }

        $this->info("Imported {$imported} ayah audio rows for {$reciter->name}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{surah: int, ayah: int, audio_url: string, duration_ms: int}>
     */
    private function audioFromApi(QulClient $client, string $reciterId): array
    {
        $payload = $client->qul()
            ->get("/api/v1/audio/ayah_recitations/{$reciterId}")
            ->throw()
            ->json();

        return QulDump::audio($payload);
    }
}
