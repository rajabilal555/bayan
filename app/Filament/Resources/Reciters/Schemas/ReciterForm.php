<?php

namespace App\Filament\Resources\Reciters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReciterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('qul_resource_id')->label('QUL recitation id')->required()->maxLength(255),
                TextInput::make('granularity')->default('ayah')->required(),
            ]);
    }
}
