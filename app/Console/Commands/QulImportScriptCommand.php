<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Support\QulClient;
use App\Support\SurahRange;
use Illuminate\Console\Command;

class QulImportScriptCommand extends Command
{
    protected $signature = 'qul:import-script {--surahs= : Comma/range list such as 1,36,112-114}';

    protected $description = 'Import Uthmani ayah text (QUL-compatible Quran.com uthmani script)';

    public function handle(QulClient $client): int
    {
        $surahs = SurahRange::parse($this->option('surahs'));
        $imported = 0;

        if ($surahs === null) {
            $verses = $client->quranCom()->get('/quran/verses/uthmani')->throw()->json('verses');
            $imported += $this->upsert($verses, null);
        } else {
            foreach ($surahs as $surah) {
                $verses = $client->quranCom()
                    ->get('/quran/verses/uthmani', ['chapter_number' => $surah])
                    ->throw()
                    ->json('verses');
                $imported += $this->upsert($verses, $surah);
            }
        }

        $this->info("Imported {$imported} ayahs.");

        return self::SUCCESS;
    }

    /**
     * @param  list<array{verse_key: string, text_uthmani: string}>  $verses
     */
    private function upsert(array $verses, ?int $onlySurah): int
    {
        $count = 0;

        foreach ($verses as $verse) {
            [$surah, $ayah] = array_map('intval', explode(':', $verse['verse_key'], 2));

            if ($onlySurah !== null && $surah !== $onlySurah) {
                continue;
            }

            Ayah::query()->updateOrCreate(
                ['surah' => $surah, 'ayah' => $ayah],
                ['uthmani_text' => $verse['text_uthmani']],
            );
            $count++;
        }

        return $count;
    }
}
