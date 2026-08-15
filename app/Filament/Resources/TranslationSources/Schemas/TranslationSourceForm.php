<?php

namespace App\Filament\Resources\TranslationSources\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TranslationSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('name')->required(),
                TextInput::make('qul_resource_id')->required(),
            ]);
    }
}
