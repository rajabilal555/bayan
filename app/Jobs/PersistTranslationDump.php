<?php

namespace App\Jobs;

use App\Data\Qul\TranslationDumpData;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Support\LibraryDumps;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PersistTranslationDump implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public int $uniqueFor = 1800;

    public function __construct(public int $translationSourceId, public string $dumpPath) {}

    public function uniqueId(): string
    {
        return (string) $this->translationSourceId;
    }

    public function handle(): void
    {
        $source = TranslationSource::query()->findOrFail($this->translationSourceId);
        $dump = TranslationDumpData::fromFile(LibraryDumps::absolute($this->dumpPath));

        foreach ($dump->verses as $verse) {
            Translation::query()->updateOrCreate(
                [
                    'translation_source_id' => $source->id,
                    'surah' => $verse->surah,
                    'ayah' => $verse->ayah,
                ],
                ['text' => $verse->text],
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Translation dump import failed', [
            'translation_source_id' => $this->translationSourceId,
            'dump' => $this->dumpPath,
            'error' => $exception?->getMessage(),
        ]);
    }
}
