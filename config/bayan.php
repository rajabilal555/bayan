<?php

return [

    'disks' => [
        'library' => env('LIBRARY_DISK', 'library'),
        'renders' => env('RENDERS_DISK', 'renders'),
    ],

    'limits' => [
        'unverified' => 5,
        'verified' => 20,
    ],

    'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),

    'qul' => [
        'base' => env('QUL_BASE_URL', 'https://qul.tarteel.ai'),
        'quran_com' => env('QURAN_COM_API', 'https://api.quran.com/api/v4'),
        'audio_cdn' => env('QURAN_AUDIO_CDN', 'https://verses.quran.com/'),
    ],

    'pexels' => [
        'key' => env('PEXELS_API_KEY'),
    ],

    'fonts' => [
        'directory' => 'fonts',
        'families' => [
            'Noto Naskh Arabic' => 'NotoNaskhArabic-Regular.ttf',
            'Noto Sans Arabic' => 'NotoSansArabic-Regular.ttf',
            'Noto Sans' => 'NotoSans-Regular.ttf',
        ],
    ],

    'default_styles' => [
        'arabic' => [
            'font' => 'Noto Naskh Arabic',
            'color' => '#FFFFFF',
            'outline' => '#000000',
            'position' => 'center',
            'size' => 72,
        ],
        'translation' => [
            'font' => 'Noto Sans',
            'color' => '#F5F5F5',
            'outline' => '#000000',
            'position' => 'bottom',
            'size' => 42,
        ],
    ],

];
