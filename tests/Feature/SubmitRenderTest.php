<?php

use App\Actions\SubmitRender;
use App\Enums\RenderStatus;
use App\Jobs\RenderVideoJob;
use App\Models\AyahAudio;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\RenderCache;
use App\Models\RenderJob;
use App\Models\Surah;
use App\Models\User;
use App\Rendering\Fingerprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

function renderPayload(Reciter $reciter, Background $background, Surah $surah): array
{
    return [
        'reciter_id' => $reciter->id,
        'background_id' => $background->id,
        'surah' => $surah->number,
        'ayah_start' => 1,
        'ayah_end' => 2,
        'orientation' => 'vertical',
        'show_arabic' => true,
        'show_translation' => false,
        'translation_source_id' => null,
        'include_bismillah' => false,
        'loop_background' => true,
        'arabic_style' => config('bayan.default_styles.arabic'),
        'translation_style' => config('bayan.default_styles.translation'),
    ];
}

test('cache hits complete immediately and do not dispatch ffmpeg', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();
    $reciter = Reciter::factory()->create();
    $background = Background::factory()->create();
    $surah = Surah::factory()->create(['number' => 99, 'ayah_count' => 2]);

    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 99, 'ayah' => 1]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 99, 'ayah' => 2]);

    $payload = renderPayload($reciter, $background, $surah);
    RenderCache::factory()->create([
        'fingerprint' => Fingerprint::make($payload),
        'output_path' => 'cached.mp4',
        'duration_ms' => 4000,
    ]);

    $job = app(SubmitRender::class)->handle($user, $payload);

    expect($job->cache_hit)->toBeTrue()
        ->and($job->status)->toBe(RenderStatus::Completed)
        ->and($job->output_path)->toBe('cached.mp4');

    Queue::assertNothingPushed();
});

test('cache misses enqueue a render job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $reciter = Reciter::factory()->create();
    $background = Background::factory()->create();
    $surah = Surah::factory()->create(['number' => 98, 'ayah_count' => 2]);

    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 98, 'ayah' => 1]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 98, 'ayah' => 2]);

    $job = app(SubmitRender::class)->handle($user, renderPayload($reciter, $background, $surah));

    expect($job->cache_hit)->toBeFalse()
        ->and($job->status)->toBe(RenderStatus::Pending);

    Queue::assertPushed(RenderVideoJob::class);
});

test('daily limit blocks cache misses but not cache hits', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();
    $reciter = Reciter::factory()->create();
    $background = Background::factory()->create();
    $surah = Surah::factory()->create(['number' => 96, 'ayah_count' => 2]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 96, 'ayah' => 1]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 96, 'ayah' => 2]);

    RenderJob::factory()->count(5)->for($user)->create([
        'cache_hit' => false,
        'status' => RenderStatus::Completed,
    ]);

    $payload = renderPayload($reciter, $background, $surah);

    expect(fn () => app(SubmitRender::class)->handle($user, $payload))
        ->toThrow(ValidationException::class);

    RenderCache::factory()->create([
        'fingerprint' => Fingerprint::make($payload),
        'output_path' => 'existing.mp4',
        'duration_ms' => 4000,
    ]);

    $job = app(SubmitRender::class)->handle($user, $payload);

    expect($job->cache_hit)->toBeTrue()
        ->and($job->status)->toBe(RenderStatus::Completed);

    Queue::assertNothingPushed();
});

test('rejects when both text layers are off', function () {
    $user = User::factory()->create();
    $reciter = Reciter::factory()->create();
    $background = Background::factory()->create();
    $surah = Surah::factory()->create(['number' => 97, 'ayah_count' => 2]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 97, 'ayah' => 1]);
    AyahAudio::factory()->create(['reciter_id' => $reciter->id, 'surah' => 97, 'ayah' => 2]);

    $payload = renderPayload($reciter, $background, $surah);
    $payload['show_arabic'] = false;
    $payload['show_translation'] = false;

    app(SubmitRender::class)->handle($user, $payload);
})->throws(ValidationException::class);
