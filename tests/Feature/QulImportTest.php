<?php

use App\Models\Ayah;
use App\Models\AyahAudio;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\Translation;
use App\Rendering\Ffmpeg;
use App\Support\SurahRange;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('parses surah ranges', function () {
    expect(SurahRange::parse('1,36,112-114'))->toBe([1, 36, 112, 113, 114]);
});

test('imports surah metadata from qul', function () {
    Http::fake([
        'qul.tarteel.ai/api/v1/chapters' => Http::response([
            'chapters' => [
                [
                    'id' => 1,
                    'name_simple' => 'Al-Fatihah',
                    'name_arabic' => 'الفاتحة',
                    'verses_count' => 7,
                    'bismillah_pre' => false,
                ],
            ],
        ]),
    ]);

    $this->artisan('qul:import-surahs')->assertSuccessful();

    $surah = Surah::query()->where('number', 1)->first();

    expect($surah)->not->toBeNull()
        ->and($surah->ayah_count)->toBe(7)
        ->and($surah->name_en)->toBe('Al-Fatihah');
});

test('imports quran script from a qul dump', function () {
    $path = sys_get_temp_dir().'/qul-script-'.uniqid().'.json';
    file_put_contents($path, json_encode([
        ['verse_key' => '1:1', 'text' => 'بِسْمِ ٱللَّهِ'],
        ['verse_key' => '1:2', 'text' => 'ٱلْحَمْدُ لِلَّهِ'],
        ['verse_key' => '2:1', 'text' => 'الم'],
    ]));

    $this->artisan('qul:import-script', ['--file' => $path, '--surahs' => '1'])->assertSuccessful();

    unlink($path);

    expect(Ayah::query()->count())->toBe(2)
        ->and(Ayah::query()->where('surah', 1)->where('ayah', 1)->value('uthmani_text'))->toBe('بِسْمِ ٱللَّهِ');
});

test('imports a translation from the qul api', function () {
    Http::fake([
        'qul.tarteel.ai/api/v1/resources/translations.json' => Http::response([
            'translations' => [
                [
                    'id' => 20,
                    'name' => 'Saheeh International',
                    'slug' => 'en-sahih-international',
                    'language' => 'english',
                ],
            ],
        ]),
        'qul.tarteel.ai/api/v1/translations/20/by_range*' => Http::response([
            'translations' => [
                ['verse_key' => '1:1', 'text' => 'In the name of Allah<sup>1</sup>'],
            ],
        ]),
    ]);

    $this->artisan('qul:import-translation', ['source' => 'saheeh', '--surahs' => '1'])->assertSuccessful();

    expect(Translation::query()->value('text'))->toBe('In the name of Allah');
});

test('imports a translation from a qul dump', function () {
    Http::fake([
        'qul.tarteel.ai/api/v1/resources/translations.json' => Http::response([
            'translations' => [
                [
                    'id' => 20,
                    'name' => 'Saheeh International',
                    'slug' => 'en-sahih-international',
                    'language' => 'english',
                ],
            ],
        ]),
    ]);

    $path = sys_get_temp_dir().'/qul-translation-'.uniqid().'.json';
    file_put_contents($path, json_encode([
        '1:1' => 'In the name of Allah',
        '2:1' => 'Alif-Lam-Meem',
    ]));

    $this->artisan('qul:import-translation', [
        'source' => '20',
        '--file' => $path,
        '--surahs' => '1',
    ])->assertSuccessful();

    unlink($path);

    expect(Translation::query()->count())->toBe(1)
        ->and(Translation::query()->value('text'))->toBe('In the name of Allah');
});

test('imports reciter audio from qul and stores mp3s locally', function () {
    Storage::fake((string) config('bayan.disks.library'));

    Http::fake([
        'qul.tarteel.ai/api/v1/audio/ayah_recitations/18' => Http::response([
            'recitation' => [
                'id' => 18,
                'name' => 'Mishari Rashid al Afasy',
                'style' => 'Murattal',
                'audio_files' => [
                    '1:1' => [
                        'audio_url' => 'https://audio-cdn.tarteel.ai/quran/mishari/001001.mp3',
                        'duration' => null,
                    ],
                ],
            ],
        ]),
        'qul.tarteel.ai/api/v1/audio/ayah_recitations' => Http::response([
            'recitations' => [
                ['id' => 18, 'name' => 'Mishari Rashid al Afasy', 'style' => 'Murattal'],
            ],
        ]),
        'audio-cdn.tarteel.ai/*' => Http::response('mp3-bytes'),
    ]);

    $this->mock(Ffmpeg::class, function ($mock): void {
        $mock->shouldReceive('durationMs')->once()->andReturn(1500);
    });

    $this->artisan('qul:import-reciter', [
        'reciter' => '18',
        '--surahs' => '1',
        '--download-audio' => true,
    ])->assertSuccessful();

    $reciter = Reciter::query()->where('qul_resource_id', '18')->first();

    expect($reciter)->not->toBeNull()
        ->and(AyahAudio::query()->where('reciter_id', $reciter?->id)->value('duration_ms'))->toBe(1500);

    Storage::disk((string) config('bayan.disks.library'))->assertExists("audio/{$reciter?->id}/1/1.mp3");
});
