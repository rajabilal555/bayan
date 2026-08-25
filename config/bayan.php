<?php

return [

    'disks' => [
        'library' => env('LIBRARY_DISK', 'library'),
        'renders' => env('RENDERS_DISK', 'renders'),
    ],

    'limits' => [
        'unverified' => 5,
        'verified' => 20,
        'ayahs_per_render' => 7,
    ],

    'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),

    'qul' => [
        'base' => env('QUL_BASE_URL', 'https://qul.tarteel.ai'),
    ],

    'pexels' => [
        'key' => env('PEXELS_API_KEY'),
    ],

    'fonts' => [
        'directory' => 'fonts',
        'families' => [
            'Scheherazade New' => 'ScheherazadeNew-Regular.ttf',
            'Noto Naskh Arabic' => 'NotoNaskhArabic-Regular.ttf',
            'Noto Sans Arabic' => 'NotoSansArabic-Regular.ttf',
            'Noto Sans' => 'NotoSans-Regular.ttf',
        ],
    ],

    'default_styles' => [
        'arabic' => [
            'font' => 'Scheherazade New',
            'color' => '#FFFFFF',
            'outline' => '#000000',
            'position' => 'center',
            'size' => 64,
        ],
        'translation' => [
            'font' => 'Noto Sans',
            'color' => '#F5F5F5',
            'outline' => '#000000',
            'position' => 'center',
            'size' => 32,
        ],
    ],

];
