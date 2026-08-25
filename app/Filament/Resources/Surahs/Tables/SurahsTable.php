<?php

namespace App\Filament\Resources\Surahs\Tables;

use App\Filament\Resources\Ayahs\AyahResource;
use App\Models\Surah;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SurahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('name_en')->label('English')->searchable(),
                TextColumn::make('name_ar')
                    ->label('Arabic')
                    ->searchable()
                    ->extraCellAttributes(['dir' => 'rtl']),
                TextColumn::make('ayah_count')->label('Ayahs')->sortable(),
                TextColumn::make('ayahs_count')->counts('ayahs')->label('Imported'),
                IconColumn::make('bismillah_pre')->label('Bismillah')->boolean(),
            ])
            ->recordActions([
                Action::make('ayahs')
                    ->url(fn (Surah $record): string => AyahResource::getUrl('index', [
                        'tableFilters' => [
                            'surah' => ['value' => $record->number],
                        ],
                    ])),
            ])
            ->defaultSort('number');
    }
}
