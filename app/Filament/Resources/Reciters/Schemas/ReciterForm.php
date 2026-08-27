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
                TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('granularity')->disabled()->dehydrated(),
            ]);
    }
}
