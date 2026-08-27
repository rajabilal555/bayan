<?php

namespace App\Actions;

use App\Data\Qul\TranslationDumpData;
use App\Jobs\PersistTranslationDump;
use App\Models\TranslationSource;
use App\Support\LibraryDumps;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportTranslationDump
{
    public function handle(string $uploadedRelative, string $name, ?string $slug = null): TranslationSource
    {
        TranslationDumpData::fromFile(LibraryDumps::absolute($uploadedRelative));

        $slug = $slug ?: Str::slug($name);

        if ($slug === '') {
            throw new InvalidArgumentException('Give the translation a slug, or a name that can be slugified.');
        }

        $contents = LibraryDumps::contents($uploadedRelative);
        $stable = LibraryDumps::translationPath($slug);

        LibraryDumps::replace($stable, $contents);

        $source = TranslationSource::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name],
        );

        PersistTranslationDump::dispatch($source->id, $stable);

        return $source;
    }
}
