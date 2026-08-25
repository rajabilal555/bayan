<?php

use App\Support\QulDump;
use PDO;

test('parses qul script arrays and keyed translation dumps', function () {
    $script = QulDump::verses([
        ['verse_key' => '1:1', 'text' => 'بِسْمِ ٱللَّهِ'],
        ['surah_id' => 1, 'ayah_number' => 2, 'text' => 'ٱلْحَمْدُ لِلَّهِ'],
    ]);

    $translation = QulDump::verses([
        '1:1' => 'In the name of Allah',
        '1:2' => ['t' => 'All praise is for Allah<sup>1</sup>', 'f' => ['1' => 'note']],
    ]);

    expect($script)->toBe([
        ['surah' => 1, 'ayah' => 1, 'text' => 'بِسْمِ ٱللَّهِ'],
        ['surah' => 1, 'ayah' => 2, 'text' => 'ٱلْحَمْدُ لِلَّهِ'],
    ])->and($translation)->toBe([
        ['surah' => 1, 'ayah' => 1, 'text' => 'In the name of Allah'],
        ['surah' => 1, 'ayah' => 2, 'text' => 'All praise is for Allah'],
    ]);
});

test('parses qul recitation audio maps', function () {
    $files = QulDump::audio([
        'recitation' => [
            'audio_files' => [
                '1:1' => [
                    'audio_url' => 'https://audio-cdn.tarteel.ai/quran/demo/001001.mp3',
                    'duration' => 4,
                ],
            ],
        ],
    ]);

    expect($files)->toBe([
        [
            'surah' => 1,
            'ayah' => 1,
            'audio_url' => 'https://audio-cdn.tarteel.ai/quran/demo/001001.mp3',
            'duration_ms' => 4000,
        ],
    ]);
});

test('reads qul sqlite dumps', function () {
    $path = sys_get_temp_dir().'/qul-dump-'.uniqid().'.sqlite';
    $pdo = new PDO('sqlite:'.$path);
    $pdo->exec('CREATE TABLE verses (surah_id INTEGER, ayah_number INTEGER, text TEXT)');
    $pdo->exec("INSERT INTO verses VALUES (36, 1, 'يس')");

    expect(QulDump::versesFrom($path))->toBe([
        ['surah' => 36, 'ayah' => 1, 'text' => 'يس'],
    ]);

    unlink($path);
});
