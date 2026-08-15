<?php

namespace App\Filament\Resources\TranslationSources\Pages;

use App\Filament\Resources\TranslationSources\TranslationSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTranslationSource extends EditRecord
{
    protected static string $resource = TranslationSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
