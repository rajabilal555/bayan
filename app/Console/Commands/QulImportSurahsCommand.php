<?php

namespace App\Console\Commands;

use App\Models\Surah;
use App\Support\QulClient;
use Illuminate\Console\Command;

class QulImportSurahsCommand extends Command
{
    protected $signature = 'qul:import-surahs';

    protected $description = 'Import surah metadata from QUL';

    public function handle(QulClient $client): int
    {
        $chapters = $client->qul()->get('/api/v1/chapters')->throw()->json('chapters');

        foreach ($chapters as $chapter) {
            Surah::query()->updateOrCreate(
                ['number' => $chapter['id']],
                [
                    'name_en' => $chapter['name_simple'],
                    'name_ar' => $chapter['name_arabic'],
                    'ayah_count' => $chapter['verses_count'],
                    'bismillah_pre' => (bool) $chapter['bismillah_pre'],
                ],
            );
        }

        $this->info('Imported '.count($chapters).' surahs.');

        return self::SUCCESS;
    }
}
