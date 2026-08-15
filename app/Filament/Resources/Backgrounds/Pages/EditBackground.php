<?php

namespace App\Filament\Resources\Backgrounds\Pages;

use App\Filament\Resources\Backgrounds\BackgroundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBackground extends EditRecord
{
    protected static string $resource = BackgroundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
