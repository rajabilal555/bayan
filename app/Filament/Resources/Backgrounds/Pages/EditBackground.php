<?php

namespace App\Filament\Resources\Backgrounds\Pages;

use App\Actions\EnsureBackgroundThumbnail;
use App\Filament\Resources\Backgrounds\BackgroundResource;
use App\Models\Background;
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

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Background) {
            app(EnsureBackgroundThumbnail::class)->handle($record);
        }
    }
}
