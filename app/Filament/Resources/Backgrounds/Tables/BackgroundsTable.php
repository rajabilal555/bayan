<?php

namespace App\Filament\Resources\Backgrounds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BackgroundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->disk((string) config('bayan.disks.library'))
                    ->visibility('private')
                    ->height(48)
                    ->square(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('source'),
                IconColumn::make('loop_default')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
