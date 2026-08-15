<?php

namespace App\Console\Commands;

use App\Models\Surah;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Support\QulClient;
use App\Support\SurahRange;
use Illuminate\Console\Command;

class QulImportTranslationCommand extends Command
{
    protected $signature = 'qul:import-translation {source=saheeh : Source slug (saheeh)} {--surahs= : Comma/range list such as 1,36,112-114}';

    protected $description = 'Import an ayah-by-ayah translation from QUL';

    /**
     * @var array<string, array{id: string, name: string}>
     */
    private array $sources = [
        'saheeh' => ['id' => '20', 'name' => 'Saheeh International'],
    ];

    public function handle(QulClient $client): int
    {
        $slug = (string) $this->argument('source');

        if (! isset($this->sources[$slug])) {
            $this->error('Unknown translation source. Known: '.implode(', ', array_keys($this->sources)));

            return self::FAILURE;
        }

        $meta = $this->sources[$slug];
        $source = TranslationSource::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $meta['name'], 'qul_resource_id' => $meta['id']],
        );

        $surahs = SurahRange::parse($this->option('surahs')) ?? range(1, 114);
        $imported = 0;

        foreach ($surahs as $surah) {
            $ayahCount = (int) (Surah::query()->where('number', $surah)->value('ayah_count') ?: 286);
            $from = $surah.':1';
            $to = $surah.':'.$ayahCount;
            $rows = $client->qul()
                ->get("/api/v1/translations/{$meta['id']}/by_range", ['from' => $from, 'to' => $to])
                ->throw()
                ->json('translations');

            foreach ($rows as $row) {
                [$s, $a] = array_map('intval', explode(':', $row['verse_key'], 2));
                Translation::query()->updateOrCreate(
                    [
                        'translation_source_id' => $source->id,
                        'surah' => $s,
                        'ayah' => $a,
                    ],
                    ['text' => trim(html_entity_decode(strip_tags($row['text']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))],
                );
                $imported++;
            }
        }

        $this->info("Imported {$imported} translation rows for {$source->name}.");

        return self::SUCCESS;
    }
}
