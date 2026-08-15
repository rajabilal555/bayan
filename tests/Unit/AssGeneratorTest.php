<?php

use App\Enums\Orientation;
use App\Rendering\AssGenerator;

test('formats timestamps as ass time', function () {
    $ass = new AssGenerator;

    expect($ass->timestamp(0))->toBe('0:00:00.00')
        ->and($ass->timestamp(4224))->toBe('0:00:04.22')
        ->and($ass->timestamp(61234))->toBe('0:01:01.23');
});

test('writes arabic and translation cues', function () {
    $ass = new AssGenerator;
    $output = $ass->generate(
        [
            [
                'start_ms' => 0,
                'end_ms' => 2000,
                'arabic' => 'بِسْمِ',
                'translation' => 'In the name',
            ],
        ],
        Orientation::Vertical,
        ['font' => 'Noto Naskh Arabic', 'color' => '#FFFFFF', 'outline' => '#000000', 'position' => 'center', 'size' => 72],
        ['font' => 'Noto Sans', 'color' => '#EEEEEE', 'outline' => '#000000', 'position' => 'bottom', 'size' => 42],
        true,
        true,
    );

    expect($output)
        ->toContain('PlayResX: 1080')
        ->toContain('PlayResY: 1920')
        ->toContain('Style: Arabic,Noto Naskh Arabic,72')
        ->toContain('Dialogue: 0,0:00:00.00,0:00:02.00,Arabic')
        ->toContain('Dialogue: 1,0:00:00.00,0:00:02.00,Translation')
        ->toContain('بِسْمِ')
        ->toContain('In the name');
});
