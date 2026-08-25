<?php

namespace App\Filament\Resources\Backgrounds\Pages;

use App\Actions\EnsureBackgroundThumbnail;
use App\Filament\Resources\Backgrounds\BackgroundResource;
use App\Models\Background;
use Filament\Resources\Pages\CreateRecord;

class CreateBackground extends CreateRecord
{
    protected static string $resource = BackgroundResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Background) {
            app(EnsureBackgroundThumbnail::class)->handle($record);
        }
    }
}
