<?php

namespace App\Filament\Resources\Reciters\Pages;

use App\Filament\Actions\LibraryImportActions;
use App\Filament\Resources\Reciters\ReciterResource;
use Filament\Resources\Pages\ListRecords;

class ListReciters extends ListRecords
{
    protected static string $resource = ReciterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LibraryImportActions::reciter(),
        ];
    }
}
