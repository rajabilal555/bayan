<?php

namespace App\Actions;

use App\Data\Qul\ScriptDumpData;
use App\Jobs\PersistScriptDump;
use App\Support\LibraryDumps;

class ImportScriptDump
{
    public function handle(string $uploadedRelative): int
    {
        $dump = ScriptDumpData::fromFile(LibraryDumps::absolute($uploadedRelative));
        $contents = LibraryDumps::contents($uploadedRelative);
        $stable = LibraryDumps::scriptPath();

        LibraryDumps::replace($stable, $contents);

        PersistScriptDump::dispatch($stable);

        return count($dump->verses);
    }
}
