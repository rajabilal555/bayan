<?php

namespace App\Jobs;

use App\Data\Qul\ScriptDumpData;
use App\Models\Ayah;
use App\Support\LibraryDumps;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PersistScriptDump implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public int $uniqueFor = 1800;

    public function __construct(public string $dumpPath) {}

    public function uniqueId(): string
    {
        return $this->dumpPath;
    }

    public function handle(): void
    {
        $dump = ScriptDumpData::fromFile(LibraryDumps::absolute($this->dumpPath));

        foreach ($dump->verses as $verse) {
            Ayah::query()->updateOrCreate(
                ['surah' => $verse->surah, 'ayah' => $verse->ayah],
                ['uthmani_text' => $verse->text],
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Script dump import failed', [
            'dump' => $this->dumpPath,
            'error' => $exception?->getMessage(),
        ]);
    }
}
