<?php

namespace App\Filament\Resources\Backgrounds\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BackgroundForm
{
    public static function configure(Schema $schema): Schema
    {
        $disk = (string) config('bayan.disks.library');

        return $schema->components([
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('category')
                ->required()
                ->datalist(['rain', 'ocean', 'desert'])
                ->maxLength(255),
            FileUpload::make('path')
                ->label('Video')
                ->required()
                ->disk($disk)
                ->directory('backgrounds')
                ->visibility('private')
                ->acceptedFileTypes(['video/mp4', 'video/webm'])
                ->maxSize(512000),
            FileUpload::make('thumbnail_path')
                ->label('Thumbnail')
                ->image()
                ->disk($disk)
                ->directory('backgrounds')
                ->visibility('private')
                ->maxSize(5120)
                ->helperText(
                    'Optional. A still is taken from the video if you leave this empty.',
                ),
            TextInput::make('source')
                ->default('pexels')
                ->required()
                ->datalist(['pexels'])
                ->maxLength(255),
            TextInput::make('attribution'),
            Toggle::make('loop_default')->default(true),
        ]);
    }
}
