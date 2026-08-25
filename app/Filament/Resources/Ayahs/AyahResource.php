<?php

namespace App\Filament\Resources\Ayahs;

use App\Filament\Resources\Ayahs\Pages\ListAyahs;
use App\Filament\Resources\Ayahs\Tables\AyahsTable;
use App\Models\Ayah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AyahResource extends Resource
{
    protected static ?string $model = Ayah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AyahsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAyahs::route('/'),
        ];
    }
}
