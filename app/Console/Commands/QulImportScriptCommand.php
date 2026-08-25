<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Support\QulDump;
use App\Support\SurahRange;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class QulImportScriptCommand extends Command
{
    protected $signature = 'qul:import-script
        {--file= : QUL Quran Script JSON or SQLite dump}
        {--surahs= : Comma/range list such as 1,36,112-114}';

    protected $description = 'Import Arabic ayah text from a QUL Quran Script dump';

    public function handle(): int
    {
        $file = $this->option('file');

        if (! is_string($file) || $file === '') {
            $this->error('Download a Quran Script JSON or SQLite file from https://qul.tarteel.ai/resources/quran-script and pass --file.');

            return self::FAILURE;
        }

        try {
            $verses = QulDump::versesFrom($file);
        } catch (InvalidArgumentException|JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $surahs = SurahRange::parse($this->option('surahs'));
        $imported = 0;

        foreach ($verses as $verse) {
            if ($surahs !== null && ! in_array($verse['surah'], $surahs, true)) {
                continue;
            }

            Ayah::query()->updateOrCreate(
                ['surah' => $verse['surah'], 'ayah' => $verse['ayah']],
                ['uthmani_text' => $verse['text']],
            );
            $imported++;
        }

        $this->info("Imported {$imported} ayahs.");

        return self::SUCCESS;
    }
}
