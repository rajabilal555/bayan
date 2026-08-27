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

    'queues' => [
        'library' => env('LIBRARY_QUEUE', 'library'),
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

    'subtitles' => [
        /*
         * ASS WrapStyle and matching \qN on every event. Change this one value for wrap experiments.
         * 0 = smart wrap, 1 = end-of-line, 2 = no wrap, 3 = smart wrap (lower line first).
         */
        'wrap_style' => 0,
        'margin_x' => 120,
        'margin_v' => [
            'top' => 120,
            'center' => 0,
            'bottom' => 160,
        ],
        'sizes' => [48, 56, 64, 72, 88, 96, 112],
    ],

    'default_styles' => [
        'arabic' => [
            'font' => 'Scheherazade New',
            'color' => '#FFFFFF',
            'outline' => '#000000',
            'position' => 'center',
            'size' => 88,
        ],
        'translation' => [
            'font' => 'Noto Sans',
            'color' => '#F5F5F5',
            'outline' => '#000000',
            'position' => 'center',
            'size' => 64,
        ],
    ],

];
