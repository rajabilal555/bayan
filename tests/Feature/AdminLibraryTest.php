<?php

use App\Actions\EnsureBackgroundThumbnail;
use App\Filament\Pages\LibraryImports;
use App\Filament\Resources\Ayahs\Pages\ListAyahs;
use App\Filament\Resources\Backgrounds\Pages\ListBackgrounds;
use App\Filament\Resources\Reciters\Pages\ListReciters;
use App\Filament\Resources\Surahs\Pages\ListSurahs;
use App\Filament\Resources\TranslationSources\Pages\ListTranslationSources;
use App\Jobs\DownloadReciterSurahAudio;
use App\Jobs\PersistTranslationDump;
use App\Models\Ayah;
use App\Models\Background;
use App\Models\Surah;
use App\Models\User;
use App\Rendering\Ffmpeg;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

test('non-admins cannot open library imports', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(LibraryImports::getUrl())
        ->assertForbidden();
});

test('admins can open library imports and library tables', function () {
    $admin = User::factory()->admin()->create();
    Background::factory()->create();

    $this->actingAs($admin)
        ->get(LibraryImports::getUrl())
        ->assertOk()
        ->assertSee('Library imports');

    $this->actingAs($admin)
        ->get(ListBackgrounds::getUrl())
        ->assertOk()
        ->assertSee('Ocean');

    $this->actingAs($admin)
        ->get(ListReciters::getUrl())
        ->assertOk();
});

test('admins can view surahs and ayahs', function () {
    $admin = User::factory()->admin()->create();
    Surah::factory()->create([
        'number' => 1,
        'name_en' => 'Al-Fatihah',
        'name_ar' => 'الفاتحة',
        'ayah_count' => 7,
    ]);
    Ayah::factory()->create([
        'surah' => 1,
        'ayah' => 1,
        'uthmani_text' => 'بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ',
    ]);

    $this->actingAs($admin)
        ->get(ListSurahs::getUrl())
        ->assertOk()
        ->assertSee('Al-Fatihah')
        ->assertSee('الفاتحة');

    $this->actingAs($admin)
        ->get(ListAyahs::getUrl())
        ->assertOk()
        ->assertSee('بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ');
});

test('non-admins cannot view surahs or ayahs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(ListSurahs::getUrl())
        ->assertForbidden();

    $this->actingAs($user)
        ->get(ListAyahs::getUrl())
        ->assertForbidden();
});

test('import surahs action pulls chapters from qul', function () {
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

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(LibraryImports::class)
        ->callAction('importSurahs')
        ->assertNotified('Surahs imported');

    expect(Surah::query()->where('number', 1)->value('name_en'))->toBe('Al-Fatihah');
});

test('import translation action queues persist from a dump', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Queue::fake();
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->createWithContent('saheeh.json', json_encode([
        '1:1' => ['t' => 'In the name of Allah', 'f' => []],
    ]));

    Livewire::actingAs($admin)
        ->test(ListTranslationSources::class)
        ->callAction('importTranslation', [
            'name' => 'Saheeh International',
            'file' => $file,
        ])
        ->assertNotified('Translation import queued');

    Queue::assertPushed(PersistTranslationDump::class);
});

test('import reciter action queues one download job per surah', function () {
    Storage::fake((string) config('bayan.disks.library'));
    Queue::fake();
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->createWithContent('recitation.json', json_encode([
        '1:1' => [
            'surah_number' => 1,
            'ayah_number' => 1,
            'audio_url' => 'https://audio.qurancdn.com/Sudais/mp3/001001.mp3',
            'duration' => 3.0,
            'segments' => [['1', '380', '730']],
        ],
    ]));

    Livewire::actingAs($admin)
        ->test(ListReciters::class)
        ->callAction('importReciter', [
            'name' => 'Abdur-Rahman as-Sudais',
            'file' => $file,
        ])
        ->assertNotified('Reciter import queued');

    Queue::assertPushed(DownloadReciterSurahAudio::class, 1);
});

test('background thumbnail is generated when missing', function () {
    Storage::fake(config('bayan.disks.library'));
    Storage::disk(config('bayan.disks.library'))->put('backgrounds/ocean.mp4', 'clip');

    $this->mock(Ffmpeg::class, function ($mock): void {
        $mock->shouldReceive('run')->once();
    });

    $background = Background::factory()->create([
        'path' => 'backgrounds/ocean.mp4',
        'thumbnail_path' => null,
    ]);

    app(EnsureBackgroundThumbnail::class)->handle($background);

    expect($background->refresh()->thumbnail_path)->toBe('backgrounds/ocean.jpg');
});

test('background thumbnail is left alone when already set', function () {
    $this->mock(Ffmpeg::class, function ($mock): void {
        $mock->shouldReceive('run')->never();
    });

    $background = Background::factory()->create([
        'thumbnail_path' => 'backgrounds/ocean.jpg',
    ]);

    app(EnsureBackgroundThumbnail::class)->handle($background);

    expect($background->refresh()->thumbnail_path)->toBe('backgrounds/ocean.jpg');
});
