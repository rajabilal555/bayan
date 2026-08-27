<?php

namespace App\Jobs;

use App\Models\AyahAudio;
use App\Rendering\Ffmpeg;
use App\Support\LibraryDumps;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DownloadReciterSurahAudio implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [1, 5, 10];

    public int $uniqueFor = 1800;

    /**
     * @param  list<array{ayah: int, audio_url: string}>  $files
     */
    public function __construct(
        public int $reciterId,
        public int $surah,
        public array $files,
    ) {
        $this->onQueue((string) config('bayan.queues.library'));
    }

    public function uniqueId(): string
    {
        return $this->reciterId.':'.$this->surah;
    }

    public function handle(Ffmpeg $ffmpeg): void
    {
        $disk = LibraryDumps::disk();

        foreach ($this->files as $file) {
            $relative = "audio/{$this->reciterId}/{$this->surah}/{$file['ayah']}.mp3";
            $row = AyahAudio::query()
                ->where('reciter_id', $this->reciterId)
                ->where('surah', $this->surah)
                ->where('ayah', $file['ayah'])
                ->first();

            if ($disk->exists($relative) && $row !== null) {
                continue;
            }

            if (! $disk->exists($relative)) {
                $response = Http::connectTimeout(10)
                    ->timeout(60)
                    ->retry([100, 500, 1000])
                    ->get($file['audio_url']);

                if ($response->failed()) {
                    Log::warning('Reciter ayah audio skipped', [
                        'reciter_id' => $this->reciterId,
                        'surah' => $this->surah,
                        'ayah' => $file['ayah'],
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $disk->put($relative, $response->body());
            }

            AyahAudio::query()->updateOrCreate(
                [
                    'reciter_id' => $this->reciterId,
                    'surah' => $this->surah,
                    'ayah' => $file['ayah'],
                ],
                [
                    'path' => $relative,
                    'duration_ms' => $ffmpeg->durationMs($disk->path($relative)),
                ],
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Reciter surah audio import failed', [
            'reciter_id' => $this->reciterId,
            'surah' => $this->surah,
            'error' => $exception?->getMessage(),
        ]);
    }
}
