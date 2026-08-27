<?php

namespace App\Filament\Resources\TranslationSources\Pages;

use App\Filament\Actions\LibraryImportActions;
use App\Filament\Resources\TranslationSources\TranslationSourceResource;
use Filament\Resources\Pages\ListRecords;

class ListTranslationSources extends ListRecords
{
    protected static string $resource = TranslationSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LibraryImportActions::translation(),
        ];
    }
}
