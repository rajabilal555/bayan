<?php

use App\Models\Ayah;
use App\Models\Surah;
use App\Support\SurahRange;
use Illuminate\Support\Facades\Http;

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

test('imports uthmani script for selected surahs', function () {
    Http::fake([
        'api.quran.com/api/v4/quran/verses/uthmani*' => Http::response([
            'verses' => [
                ['verse_key' => '1:1', 'text_uthmani' => 'بِسْمِ ٱللَّهِ'],
                ['verse_key' => '1:2', 'text_uthmani' => 'ٱلْحَمْدُ لِلَّهِ'],
            ],
        ]),
    ]);

    $this->artisan('qul:import-script', ['--surahs' => '1'])->assertSuccessful();

    expect(Ayah::query()->count())->toBe(2)
        ->and(Ayah::query()->where('surah', 1)->where('ayah', 1)->value('uthmani_text'))->toBe('بِسْمِ ٱللَّهِ');
});
