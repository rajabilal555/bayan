<?php

namespace App\Actions;

use App\Data\Qul\ChapterData;
use App\Models\Surah;
use App\Support\QulClient;

class ImportSurahs
{
    public function __construct(private QulClient $client) {}

    public function handle(): int
    {
        $imported = 0;

        foreach ($this->client->chapters() as $chapter) {
            $data = ChapterData::from($chapter);

            Surah::query()->updateOrCreate(
                ['number' => $data->id],
                [
                    'name_en' => $data->name_simple,
                    'name_ar' => $data->name_arabic,
                    'ayah_count' => $data->verses_count,
                    'bismillah_pre' => $data->bismillah_pre,
                ],
            );
            $imported++;
        }

        return $imported;
    }
}
