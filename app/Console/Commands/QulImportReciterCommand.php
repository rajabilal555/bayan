<?php

namespace App\Console\Commands;

use App\Models\AyahAudio;
use App\Models\Reciter;
use App\Rendering\Ffmpeg;
use App\Support\QulClient;
use App\Support\SurahRange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QulImportReciterCommand extends Command
{
    protected $signature = 'qul:import-reciter
        {reciter=7 : Quran.com / QUL ayah-recitation id (7=Mishari, 6=Husary, 3=Sudais)}
        {--surahs= : Comma/range list such as 1,36,112-114}
        {--download-audio : Download mp3 files into local library storage}';

    protected $description = 'Import an ayah-by-ayah reciter and optionally download its mp3s';

    public function handle(QulClient $client, Ffmpeg $ffmpeg): int
    {
        $reciterId = (string) $this->argument('reciter');
        $download = (bool) $this->option('download-audio');
        $surahs = SurahRange::parse($this->option('surahs')) ?? range(1, 114);

        $catalog = $client->quranCom()->get('/resources/recitations')->throw()->json('recitations');
        $catalog = is_array($catalog) ? $catalog : [];
        $meta = collect($catalog)->first(
            fn (mixed $row): bool => is_array($row) && (int) ($row['id'] ?? 0) === (int) $reciterId
        );

        if (! is_array($meta)) {
            $this->error("Reciter {$reciterId} not found.");

            return self::FAILURE;
        }

        $reciter = Reciter::query()->updateOrCreate(
            ['qul_resource_id' => $reciterId],
            [
                'name' => $meta['reciter_name'].($meta['style'] ? ' ('.$meta['style'].')' : ''),
                'granularity' => 'ayah',
            ],
        );

        $disk = Storage::disk(config('bayan.disks.library'));
        $imported = 0;

        foreach ($surahs as $surah) {
            $files = $this->audioFiles($client, (int) $reciterId, $surah);

            foreach ($files as $file) {
                [$s, $a] = array_map('intval', explode(':', $file['verse_key'], 2));
                $relative = "audio/{$reciter->id}/{$s}/{$a}.mp3";
                $duration = 0;

                if ($download) {
                    $absoluteUrl = str_starts_with($file['url'], 'http')
                        ? $file['url']
                        : rtrim((string) config('bayan.qul.audio_cdn'), '/').'/'.$file['url'];
                    $disk->put($relative, Http::timeout(60)->get($absoluteUrl)->throw()->body());
                    $duration = $ffmpeg->durationMs($disk->path($relative));
                }

                AyahAudio::query()->updateOrCreate(
                    ['reciter_id' => $reciter->id, 'surah' => $s, 'ayah' => $a],
                    [
                        'path' => $relative,
                        'duration_ms' => $duration ?: (int) ($file['duration_ms'] ?? 0),
                    ],
                );
                $imported++;
            }

            $this->line("Surah {$surah}: ".count($files).' ayahs');
        }

        $this->info("Imported {$imported} ayah audio rows for {$reciter->name}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{verse_key: string, url: string, duration_ms?: int}>
     */
    private function audioFiles(QulClient $client, int $reciterId, int $surah): array
    {
        $page = 1;
        $files = [];

        do {
            $payload = $client->quranCom()
                ->get("/recitations/{$reciterId}/by_chapter/{$surah}", ['per_page' => 300, 'page' => $page])
                ->throw()
                ->json();

            if (! is_array($payload)) {
                break;
            }

            $chunk = $payload['audio_files'] ?? [];
            if (is_array($chunk)) {
                foreach ($chunk as $file) {
                    if (! is_array($file) || ! isset($file['verse_key'], $file['url'])) {
                        continue;
                    }

                    $row = [
                        'verse_key' => (string) $file['verse_key'],
                        'url' => (string) $file['url'],
                    ];
                    if (isset($file['duration_ms'])) {
                        $row['duration_ms'] = (int) $file['duration_ms'];
                    }
                    $files[] = $row;
                }
            }

            $page = $payload['pagination']['next_page'] ?? null;
        } while ($page);

        return $files;
    }
}
