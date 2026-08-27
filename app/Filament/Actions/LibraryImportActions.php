<?php

namespace App\Filament\Actions;

use App\Actions\ImportReciterDump;
use App\Actions\ImportScriptDump;
use App\Actions\ImportSurahs;
use App\Actions\ImportTranslationDump;
use App\Jobs\RunImportCommand;
use App\Support\LibraryDumps;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Spatie\LaravelData\Exceptions\CannotCreateData;
use Throwable;

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
            ->action(function (): void {
                try {
                    $imported = app(ImportSurahs::class)->handle();
                } catch (Throwable $exception) {
                    self::failed($exception);

                    return;
                }

                Notification::make()
                    ->title('Surahs imported')
                    ->body("Imported {$imported} surahs.")
                    ->success()
                    ->send();
            });
    }

    public static function script(): Action
    {
        return Action::make('importScript')
            ->label('Import script')
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                self::dumpUpload('https://qul.tarteel.ai/resources/quran-script')->required(),
            ])
            ->modalHeading('Import Quran Script')
            ->modalDescription('Upload a Quran Script JSON dump. This replaces the previous script dump.')
            ->modalSubmitActionLabel('Queue import')
            ->action(function (array $data): void {
                try {
                    $count = app(ImportScriptDump::class)->handle(LibraryDumps::uploadedRelative($data['file'] ?? null));
                } catch (Throwable $exception) {
                    self::failed($exception);

                    return;
                }

                Notification::make()
                    ->title('Script import queued')
                    ->body("Parsed {$count} ayahs. Persist will run on Horizon.")
                    ->success()
                    ->send();
            });
    }

    public static function translation(): Action
    {
        return Action::make('importTranslation')
            ->label('Import translation')
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->helperText('Leave blank to slugify the name. Re-import with the same slug to replace.'),
                self::dumpUpload('https://qul.tarteel.ai/resources/translation')->required(),
            ])
            ->modalHeading('Import translation')
            ->modalDescription('Upload a QUL translation JSON dump (verse keys with t/f). Footnotes are stripped from stored text.')
            ->modalSubmitActionLabel('Queue import')
            ->action(function (array $data): void {
                try {
                    $source = app(ImportTranslationDump::class)->handle(
                        LibraryDumps::uploadedRelative($data['file'] ?? null),
                        (string) $data['name'],
                        isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== ''
                            ? $data['slug']
                            : null,
                    );
                } catch (Throwable $exception) {
                    self::failed($exception);

                    return;
                }

                Notification::make()
                    ->title('Translation import queued')
                    ->body("{$source->name} will persist on Horizon.")
                    ->success()
                    ->send();
            });
    }

    public static function reciter(): Action
    {
        return Action::make('importReciter')
            ->label('Import reciter')
            ->icon(Heroicon::OutlinedMicrophone)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Re-import with the same name to replace.'),
                self::dumpUpload('https://qul.tarteel.ai/resources/recitation')->required(),
            ])
            ->modalHeading('Import reciter')
            ->modalDescription('Upload an ayah-by-ayah JSON dump (verse keys with audio_url). MP3s are copied locally; one Horizon job runs per surah. Existing mp3s are skipped.')
            ->modalSubmitActionLabel('Queue import')
            ->action(function (array $data): void {
                try {
                    $reciter = app(ImportReciterDump::class)->handle(
                        LibraryDumps::uploadedRelative($data['file'] ?? null),
                        (string) $data['name'],
                    );
                } catch (Throwable $exception) {
                    self::failed($exception);

                    return;
                }

                Notification::make()
                    ->title('Reciter import queued')
                    ->body("{$reciter->name}: one download job per surah on the library queue.")
                    ->success()
                    ->send();
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
            ->helperText('JSON from '.$url)
            ->disk((string) config('bayan.disks.library'))
            ->directory('imports/incoming')
            ->acceptedFileTypes([
                'application/json',
                'text/json',
                'text/plain',
                'application/octet-stream',
            ])
            ->required()
            ->maxSize(102400);
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

    private static function failed(Throwable $exception): void
    {
        $message = $exception instanceof InvalidArgumentException
            || $exception instanceof JsonException
            || $exception instanceof RuntimeException
            || $exception instanceof CannotCreateData
            ? $exception->getMessage()
            : 'Import failed.';

        if ($message === 'Import failed.') {
            report($exception);
        }

        Notification::make()
            ->title('Import failed')
            ->body($message)
            ->danger()
            ->send();
    }
}
