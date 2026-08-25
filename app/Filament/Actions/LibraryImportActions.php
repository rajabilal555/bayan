<?php

namespace App\Filament\Actions;

use App\Jobs\RunImportCommand;
use App\Models\Reciter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class LibraryImportActions
{
    public static function surahs(): Action
    {
        return Action::make('importSurahs')
            ->label('Import surahs')
            ->icon(Heroicon::OutlinedBookOpen)
            ->requiresConfirmation()
            ->modalHeading('Import surah metadata')
            ->modalDescription('Pull chapter names and ayah counts from QUL.')
            ->action(fn () => self::queue('qul:import-surahs'));
    }

    public static function script(): Action
    {
        return Action::make('importScript')
            ->label('Import script')
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                self::dumpUpload(
                    'https://qul.tarteel.ai/resources/quran-script',
                )->required(),
                self::surahsInput(),
            ])
            ->modalHeading('Import Quran Script')
            ->modalDescription(
                'QUL has no public script API. Download JSON or SQLite from the Quran Script resources page.',
            )
            ->modalSubmitActionLabel('Queue import')
            ->action(
                fn (array $data) => self::queue('qul:import-script', [
                    '--file' => self::uploadedFile($data),
                    '--surahs' => $data['surahs'] ?? null,
                ]),
            );
    }

    public static function translation(): Action
    {
        return Action::make('importTranslation')
            ->label('Import translation')
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                Select::make('source')
                    ->label('QUL translation')
                    ->options([
                        'saheeh' => 'Saheeh International',
                        'clearquran' => 'The Clear Quran (Khattab)',
                        'pickthall' => 'Pickthall',
                        'yusufali' => 'Yusuf Ali',
                    ])
                    ->default('saheeh')
                    ->required(),
                self::surahsInput(),
            ])
            ->modalSubmitActionLabel('Queue import')
            ->action(
                fn (array $data) => self::queue('qul:import-translation', [
                    'source' => $data['source'],
                    '--surahs' => $data['surahs'] ?? null,
                ]),
            );
    }

    public static function reciter(): Action
    {
        return Action::make('importReciter')
            ->label('Import reciter')
            ->icon(Heroicon::OutlinedMicrophone)
            ->schema([
                Select::make('reciter')
                    ->label('QUL reciter')
                    ->options([
                        '18' => 'Mishari Rashid al Afasy (18)',
                        '20' => 'Mahmoud Khalil Al Husary (20)',
                        '16' => 'Abd ur Rahman as Sudais (16)',
                    ])
                    ->default('18')
                    ->required(),
                self::surahsInput(),
                Toggle::make('download_audio')
                    ->label('Download MP3s')
                    ->helperText(
                        'Copies audio from QUL into local storage. Tarteel CDN is not hotlinked at runtime.',
                    )
                    ->default(true),
            ])
            ->modalSubmitActionLabel('Queue import')
            ->action(
                fn (array $data) => self::queue('qul:import-reciter', [
                    'reciter' => $data['reciter'],
                    '--surahs' => $data['surahs'] ?? null,
                    '--download-audio' => (bool) ($data['download_audio'] ?? false),
                ]),
            );
    }

    public static function reciterAudio(): Action
    {
        return Action::make('importAudio')
            ->label('Import audio')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->schema([
                self::surahsInput(),
                Toggle::make('download_audio')
                    ->label('Download MP3s')
                    ->default(true),
            ])
            ->modalSubmitActionLabel('Queue import')
            ->action(function (Reciter $record, array $data): void {
                self::queue('qul:import-reciter', [
                    'reciter' => $record->qul_resource_id,
                    '--surahs' => $data['surahs'] ?? null,
                    '--download-audio' => (bool) ($data['download_audio'] ?? false),
                ]);
            });
    }

    public static function backgrounds(): Action
    {
        return Action::make('importBackgrounds')
            ->label('Import backgrounds')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                TextInput::make('limit')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(40)
                    ->default(8)
                    ->required(),
                Toggle::make('placeholder')
                    ->label('Placeholder clips')
                    ->helperText(
                        'Generate local color videos instead of calling Pexels.',
                    ),
            ])
            ->modalSubmitActionLabel('Queue import')
            ->action(
                fn (array $data) => self::queue('backgrounds:import-pexels', [
                    '--limit' => $data['limit'],
                    '--placeholder' => (bool) ($data['placeholder'] ?? false),
                ]),
            );
    }

    private static function dumpUpload(string $url): FileUpload
    {
        return FileUpload::make('file')
            ->label('QUL dump')
            ->helperText('JSON or SQLite from '.$url)
            ->disk((string) config('bayan.disks.library'))
            ->directory('imports')
            ->visibility('private')
            ->acceptedFileTypes([
                'application/json',
                'text/plain',
                'application/octet-stream',
                'application/vnd.sqlite3',
                'application/x-sqlite3',
            ])
            ->nullable()
            ->maxSize(102400);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function uploadedFile(array $data): ?string
    {
        $file = $data['file'] ?? null;

        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        return is_string($file) && $file !== '' ? $file : null;
    }

    private static function surahsInput(): TextInput
    {
        return TextInput::make('surahs')
            ->label('Surahs')
            ->placeholder('All')
            ->helperText(
                'Comma or range, e.g. 1,36,112-114. Leave blank for all.',
            )
            ->rules(['nullable', 'regex:/^[\d,\s\-]+$/']);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function queue(string $command, array $parameters = []): void
    {
        $parameters = array_filter(
            $parameters,
            fn (mixed $value): bool => $value !== null &&
                $value !== '' &&
                $value !== false,
        );

        RunImportCommand::dispatch($command, $parameters);

        Notification::make()
            ->title('Import queued')
            ->body($command.' will run on Horizon.')
            ->success()
            ->send();
    }
}
