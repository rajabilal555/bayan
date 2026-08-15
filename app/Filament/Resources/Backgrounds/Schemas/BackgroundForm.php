<?php

namespace App\Filament\Resources\Backgrounds\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BackgroundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')->required(),
                TextInput::make('category')->required(),
                TextInput::make('path')->required()->helperText('Path on the library disk'),
                TextInput::make('thumbnail_path'),
                TextInput::make('source')->default('pexels'),
                TextInput::make('attribution'),
                Toggle::make('loop_default')->default(true),
            ]);
    }
}
