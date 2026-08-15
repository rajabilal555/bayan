<?php

namespace App\Filament\Resources\TranslationSources;

use App\Filament\Resources\TranslationSources\Pages\CreateTranslationSource;
use App\Filament\Resources\TranslationSources\Pages\EditTranslationSource;
use App\Filament\Resources\TranslationSources\Pages\ListTranslationSources;
use App\Filament\Resources\TranslationSources\Schemas\TranslationSourceForm;
use App\Filament\Resources\TranslationSources\Tables\TranslationSourcesTable;
use App\Models\TranslationSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TranslationSourceResource extends Resource
{
    protected static ?string $model = TranslationSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Library';

    public static function form(Schema $schema): Schema
    {
        return TranslationSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranslationSourcesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationSources::route('/'),
            'create' => CreateTranslationSource::route('/create'),
            'edit' => EditTranslationSource::route('/{record}/edit'),
        ];
    }
}
