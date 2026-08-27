<?php

namespace App\Actions;

use App\Data\Qul\RecitationDumpData;
use App\Jobs\DownloadReciterSurahAudio;
use App\Models\Reciter;
use App\Support\LibraryDumps;

class ImportReciterDump
{
    public function handle(string $uploadedRelative, string $name): Reciter
    {
        $dump = RecitationDumpData::fromFile(LibraryDumps::absolute($uploadedRelative));
        $contents = LibraryDumps::contents($uploadedRelative);

        $reciter = Reciter::query()->updateOrCreate(
            ['name' => $name],
            ['granularity' => 'ayah'],
        );

        LibraryDumps::replace(LibraryDumps::recitationPath((string) $reciter->id), $contents);

        foreach ($dump->groupedBySurah() as $surah => $files) {
            DownloadReciterSurahAudio::dispatch($reciter->id, $surah, $files);
        }

        return $reciter;
    }
}
