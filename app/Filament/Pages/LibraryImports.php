<?php

namespace App\Filament\Pages;

use App\Filament\Actions\LibraryImportActions;
use App\Filament\Widgets\LibraryStats;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class LibraryImports extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Imports';

    protected static ?string $title = 'Library imports';

    protected static string|UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 0;

    // protected string $view = 'filament.pages.library-imports';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Callout::make('Download QUL JSON/SQLite dumps, then queue an import. Reciter MP3s are stored locally.')
                    ->color('info')
                    ->actions([
                        Action::make('quran_scripts')->label('Quran scripts')->url('https://qul.tarteel.ai/resources/quran-script')->icon(Heroicon::ArrowTopRightOnSquare),
                        Action::make('qul_resource')->label('QUL resources')->url('https://qul.tarteel.ai/resources')->icon(Heroicon::ArrowTopRightOnSquare),
                    ])
            ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Download QUL JSON/SQLite dumps, then queue an import. Reciter MP3s are stored locally.';
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            LibraryImportActions::surahs(),
            LibraryImportActions::script(),
            LibraryImportActions::translation(),
            LibraryImportActions::reciter(),
            LibraryImportActions::backgrounds(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            LibraryStats::class,
        ];
    }
}
