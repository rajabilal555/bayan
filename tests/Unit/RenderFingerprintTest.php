<?php

use App\Rendering\Fingerprint;

test('identical params produce the same fingerprint', function () {
    $params = [
        'reciter_id' => 1,
        'background_id' => 2,
        'surah' => 1,
        'ayah_start' => 1,
        'ayah_end' => 7,
        'orientation' => 'vertical',
        'show_arabic' => true,
        'show_translation' => true,
        'translation_source_id' => 3,
        'include_bismillah' => false,
        'loop_background' => true,
        'arabic_style' => ['font' => 'Noto Naskh Arabic', 'color' => '#ffffff', 'outline' => '#000000', 'position' => 'center', 'size' => 72],
        'translation_style' => ['font' => 'Noto Sans', 'color' => '#F5F5F5', 'outline' => '#000000', 'position' => 'bottom', 'size' => 42],
    ];

    $other = $params;
    $other['arabic_style']['color'] = '#FFFFFF';

    expect(Fingerprint::make($params))->toBe(Fingerprint::make($other));
});

test('style changes produce a different fingerprint', function () {
    $base = [
        'reciter_id' => 1,
        'background_id' => 2,
        'surah' => 1,
        'ayah_start' => 1,
        'ayah_end' => 7,
        'orientation' => 'vertical',
        'show_arabic' => true,
        'show_translation' => false,
        'translation_source_id' => null,
        'include_bismillah' => false,
        'loop_background' => true,
        'arabic_style' => ['color' => '#FFFFFF'],
        'translation_style' => [],
    ];

    $changed = $base;
    $changed['arabic_style']['color'] = '#FF0000';

    expect(Fingerprint::make($base))->not->toBe(Fingerprint::make($changed));
});

test('unused translation sources do not change the fingerprint', function () {
    $base = [
        'reciter_id' => 1,
        'background_id' => 2,
        'surah' => 1,
        'ayah_start' => 1,
        'ayah_end' => 7,
        'orientation' => 'vertical',
        'show_arabic' => true,
        'show_translation' => false,
        'translation_source_id' => 3,
        'include_bismillah' => false,
        'loop_background' => true,
        'arabic_style' => [],
        'translation_style' => [],
    ];

    $other = $base;
    $other['translation_source_id'] = 9;

    expect(Fingerprint::make($base))->toBe(Fingerprint::make($other));
});
