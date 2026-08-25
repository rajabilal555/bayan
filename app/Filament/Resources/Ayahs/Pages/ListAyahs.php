<?php

namespace App\Filament\Resources\Ayahs\Pages;

use App\Filament\Actions\LibraryImportActions;
use App\Filament\Resources\Ayahs\AyahResource;
use Filament\Resources\Pages\ListRecords;

class ListAyahs extends ListRecords
{
    protected static string $resource = AyahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LibraryImportActions::script(),
        ];
    }
}
