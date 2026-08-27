<?php

use App\Data\Qul\RecitationDumpData;
use App\Data\Qul\ScriptDumpData;
use App\Data\Qul\TranslationDumpData;
use InvalidArgumentException;

test('parses a qul script array', function () {
    $dump = ScriptDumpData::fromPayload([
        ['verse_key' => '1:1', 'text' => 'بِسْمِ ٱللَّهِ'],
        ['surah_id' => 1, 'ayah_number' => 2, 'text' => 'ٱلْحَمْدُ لِلَّهِ'],
    ]);

    expect($dump->verses)->toHaveCount(2)
        ->and($dump->verses[0]->surah)->toBe(1)
        ->and($dump->verses[0]->text)->toBe('بِسْمِ ٱللَّهِ');
});

test('parses a qul translation map and strips footnotes', function () {
    $dump = TranslationDumpData::fromPayload([
        '1:1' => [
            't' => 'In the name of Allāh,<sup foot_note="226402">1</sup> the Entirely Merciful.',
            'f' => ['226402' => 'Allāh is a proper name.'],
        ],
        '1:2' => [
            't' => '[All] praise is [due] to Allāh',
            'f' => [],
        ],
    ]);

    expect($dump->verses)->toHaveCount(2)
        ->and($dump->verses[0]->text)->toBe('In the name of Allāh, the Entirely Merciful.')
        ->and($dump->verses[1]->text)->toBe('[All] praise is [due] to Allāh');
});

test('parses a qul recitation verse map and groups audio by surah', function () {
    $dump = RecitationDumpData::fromPayload([
        '1:1' => [
            'surah_number' => 1,
            'ayah_number' => 1,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/001001.mp3',
            'duration' => 3.0,
            'segments' => [['1', '380', '730']],
        ],
        '1:2' => [
            'surah_number' => 1,
            'ayah_number' => 2,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/001002.mp3',
            'duration' => 4.0,
            'segments' => [],
        ],
        '2:1' => [
            'surah_number' => 2,
            'ayah_number' => 1,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/002001.mp3',
            'duration' => 5.0,
            'segments' => [],
        ],
    ]);

    expect($dump->files)->toHaveCount(3)
        ->and($dump->groupedBySurah())->toHaveKeys([1, 2])
        ->and($dump->groupedBySurah()[1])->toHaveCount(2)
        ->and($dump->files['1:1']->audio_url)->toBe('https://audio.qurancdn.com/Sudais/mp3/001001.mp3');
});

test('rejects a gapless recitation dump with no ayah audio urls', function () {
    RecitationDumpData::fromPayload([
        '1:1' => [
            'segments' => [[1, 361, 1051]],
            'duration_sec' => 4,
            'timestamp_from' => 361,
            'timestamp_to' => 4585,
        ],
    ]);
})->throws(InvalidArgumentException::class, 'Recitation dump has no ayah audio URLs.');
