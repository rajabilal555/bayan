<?php

namespace App\Filament\Resources\Ayahs\Tables;

use App\Filament\Resources\Surahs\SurahResource;
use App\Models\Surah;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AyahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->groups([Group::make('surahRecord.name_en')->collapsible()])
            ->defaultGroup(Group::make('surahRecord.name_en')->collapsible())
            ->columns([
                TextColumn::make('surah')
                    ->sortable()
                    ->url(
                        fn ($record) => SurahResource::getUrl('index', [
                            'search' => $record->surah,
                        ]),
                    ),
                TextColumn::make('surahRecord.name_en')
                    ->label('Name')
                    ->toggleable(),
                TextColumn::make('ayah')->sortable(),
                TextColumn::make('uthmani_text')
                    ->label('Arabic')
                    ->searchable()
                    ->wrap()
                    ->extraCellAttributes(['dir' => 'rtl']),
            ])
            ->filters(
                [
                    SelectFilter::make('surah')
                        ->options(
                            fn (): array => Surah::query()
                                ->orderBy('number')
                                ->get()
                                ->mapWithKeys(
                                    fn (Surah $surah): array => [
                                        $surah->number => $surah->number.
                                            '. '.
                                            $surah->name_en,
                                    ],
                                )
                                ->all(),
                        )
                        ->searchable()
                        ->preload(),
                ],
                FiltersLayout::AboveContent,
            )
            ->defaultSort(
                fn (Builder $query): Builder => $query
                    ->orderBy('surah')
                    ->orderBy('ayah'),
            );
    }
}
