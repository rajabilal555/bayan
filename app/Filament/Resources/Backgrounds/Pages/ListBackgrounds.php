<?php

namespace App\Filament\Resources\Backgrounds\Pages;

use App\Filament\Resources\Backgrounds\BackgroundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackgrounds extends ListRecords
{
    protected static string $resource = BackgroundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
