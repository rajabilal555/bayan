<?php

namespace App\Console\Commands;

use App\Models\Surah;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Support\QulClient;
use App\Support\QulDump;
use App\Support\SurahRange;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class QulImportTranslationCommand extends Command
{
    protected $signature = 'qul:import-translation
        {source=saheeh : QUL translation id or slug}
        {--file= : QUL translation JSON or SQLite dump}
        {--surahs= : Comma/range list such as 1,36,112-114}';

    protected $description = 'Import an ayah-by-ayah translation from QUL (API or downloaded dump)';

    public function handle(QulClient $client): int
    {
        $meta = $client->translationResource((string) $this->argument('source'));

        if ($meta === null) {
            $this->error('Unknown translation. Use a QUL id/slug or download JSON from https://qul.tarteel.ai/resources/translation');

            return self::FAILURE;
        }

        $source = TranslationSource::query()->updateOrCreate(
            ['slug' => $meta['slug']],
            ['name' => $meta['name'], 'qul_resource_id' => $meta['id']],
        );

        $file = $this->option('file');
        $surahs = SurahRange::parse($this->option('surahs')) ?? range(1, 114);

        if (is_string($file) && $file !== '') {
            try {
                $rows = QulDump::versesFrom($file);
            } catch (InvalidArgumentException|JsonException|RuntimeException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $imported = 0;

            foreach ($rows as $row) {
                if (! in_array($row['surah'], $surahs, true)) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    [
                        'translation_source_id' => $source->id,
                        'surah' => $row['surah'],
                        'ayah' => $row['ayah'],
                    ],
                    ['text' => $row['text']],
                );
                $imported++;
            }

            $this->info("Imported {$imported} translation rows for {$source->name}.");

            return self::SUCCESS;
        }

        $imported = 0;

        foreach ($surahs as $surah) {
            $ayahCount = (int) (Surah::query()->where('number', $surah)->value('ayah_count') ?: 286);
            $from = $surah.':1';
            $to = $surah.':'.$ayahCount;
            $rows = $client->qul()
                ->get("/api/v1/translations/{$meta['id']}/by_range", ['from' => $from, 'to' => $to])
                ->throw()
                ->json('translations');

            if (! is_array($rows)) {
                continue;
            }

            foreach (QulDump::verses(['translations' => $rows]) as $row) {
                Translation::query()->updateOrCreate(
                    [
                        'translation_source_id' => $source->id,
                        'surah' => $row['surah'],
                        'ayah' => $row['ayah'],
                    ],
                    ['text' => $row['text']],
                );
                $imported++;
            }
        }

        $this->info("Imported {$imported} translation rows for {$source->name}.");

        return self::SUCCESS;
    }
}
