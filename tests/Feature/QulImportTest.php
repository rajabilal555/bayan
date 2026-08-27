<?php

use App\Actions\ImportReciterDump;
use App\Actions\ImportScriptDump;
use App\Actions\ImportSurahs;
use App\Actions\ImportTranslationDump;
use App\Jobs\DownloadReciterSurahAudio;
use App\Jobs\PersistScriptDump;
use App\Jobs\PersistTranslationDump;
use App\Models\Ayah;
use App\Models\AyahAudio;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\Translation;
use App\Rendering\Ffmpeg;
use App\Support\LibraryDumps;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('imports surah metadata from qul chapters', function () {
    Http::preventStrayRequests();
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

    expect(app(ImportSurahs::class)->handle())->toBe(1);

    $surah = Surah::query()->where('number', 1)->first();

    expect($surah)->not->toBeNull()
        ->and($surah->ayah_count)->toBe(7)
        ->and($surah->name_en)->toBe('Al-Fatihah');
});

test('queues a script dump persist after parsing json', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Queue::fake();

    LibraryDumps::replace('imports/incoming/script.json', json_encode([
        ['verse_key' => '1:1', 'text' => 'بِسْمِ ٱللَّهِ'],
        ['verse_key' => '1:2', 'text' => 'ٱلْحَمْدُ لِلَّهِ'],
        ['verse_key' => '2:1', 'text' => 'الم'],
    ]));

    expect(app(ImportScriptDump::class)->handle('imports/incoming/script.json'))->toBe(3);

    Queue::assertPushed(PersistScriptDump::class, fn (PersistScriptDump $job): bool => $job->dumpPath === 'imports/script.json');

    (new PersistScriptDump('imports/script.json'))->handle();

    expect(Ayah::query()->count())->toBe(3)
        ->and(Ayah::query()->where('surah', 1)->where('ayah', 1)->value('uthmani_text'))->toBe('بِسْمِ ٱللَّهِ');
});

test('queues a translation dump persist and strips footnotes', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Queue::fake();

    LibraryDumps::replace('imports/incoming/saheeh.json', json_encode([
        '1:1' => [
            't' => 'In the name of Allāh,<sup foot_note="226402">1</sup> the Entirely Merciful.',
            'f' => ['226402' => 'Allāh is a proper name.'],
        ],
    ]));

    $source = app(ImportTranslationDump::class)->handle(
        'imports/incoming/saheeh.json',
        'Saheeh International',
        'en-sahih-international',
    );

    expect($source->slug)->toBe('en-sahih-international')
        ->and($source->name)->toBe('Saheeh International');

    Queue::assertPushed(
        PersistTranslationDump::class,
        fn (PersistTranslationDump $job): bool => $job->translationSourceId === $source->id
            && $job->dumpPath === 'imports/translations/en-sahih-international.json',
    );

    (new PersistTranslationDump($source->id, 'imports/translations/en-sahih-international.json'))->handle();

    expect(Translation::query()->value('text'))->toBe('In the name of Allāh, the Entirely Merciful.');
});

test('creates a reciter from a dump and dispatches one job per surah', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Queue::fake();

    LibraryDumps::replace('imports/incoming/recitation.json', json_encode([
        '1:1' => [
            'surah_number' => 1,
            'ayah_number' => 1,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/001001.mp3',
            'duration' => 3.0,
            'segments' => [['1', '380', '730']],
        ],
        '5:1' => [
            'surah_number' => 5,
            'ayah_number' => 1,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/005001.mp3',
            'duration' => 4.0,
            'segments' => [],
        ],
    ]));

    $reciter = app(ImportReciterDump::class)->handle(
        'imports/incoming/recitation.json',
        'Abdur-Rahman as-Sudais',
    );

    expect($reciter->name)->toBe('Abdur-Rahman as-Sudais');

    Queue::assertPushed(DownloadReciterSurahAudio::class, 2);
    Queue::assertPushed(
        DownloadReciterSurahAudio::class,
        fn (DownloadReciterSurahAudio $job): bool => $job->reciterId === $reciter->id
            && $job->surah === 1
            && $job->queue === 'library',
    );
});

test('downloads missing reciter audio and skips local mp3s', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Http::preventStrayRequests();
    Http::fake([
        'audio-cdn.tarteel.ai/quran/abdulBasitMurattal/001002.mp3' => Http::response('mp3-bytes'),
    ]);

    $reciter = Reciter::factory()->create();
    $disk = LibraryDumps::disk();
    $existing = "audio/{$reciter->id}/1/1.mp3";
    $disk->put($existing, 'already-there');

    AyahAudio::factory()->create([
        'reciter_id' => $reciter->id,
        'surah' => 1,
        'ayah' => 1,
        'path' => $existing,
        'duration_ms' => 900,
    ]);

    $this->mock(Ffmpeg::class, function ($mock): void {
        $mock->shouldReceive('durationMs')->once()->andReturn(1500);
    });

    (new DownloadReciterSurahAudio($reciter->id, 1, [
        ['ayah' => 1, 'audio_url' => 'https://audio-cdn.tarteel.ai/quran/abdulBasitMurattal/001001.mp3'],
        ['ayah' => 2, 'audio_url' => 'https://audio-cdn.tarteel.ai/quran/abdulBasitMurattal/001002.mp3'],
    ]))->handle(app(Ffmpeg::class));

    expect(AyahAudio::query()->where('ayah', 1)->value('duration_ms'))->toBe(900)
        ->and(AyahAudio::query()->where('ayah', 2)->value('duration_ms'))->toBe(1500);

    $disk->assertExists("audio/{$reciter->id}/1/2.mp3");
    Http::assertSentCount(1);
});
