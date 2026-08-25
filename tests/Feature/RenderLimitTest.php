<?php

use App\Enums\RenderStatus;
use App\Models\RenderJob;
use App\Models\User;

test('unverified users have a lower daily miss cap', function () {
    $user = User::factory()->unverified()->create();

    expect($user->dailyRenderLimit())->toBe(5)
        ->and($user->remainingRenders())->toBe(5);
});

test('verified users have a higher daily miss cap', function () {
    $user = User::factory()->create();

    expect($user->dailyRenderLimit())->toBe(20);
});

test('cache hits do not consume the daily limit', function () {
    $user = User::factory()->unverified()->create();

    RenderJob::factory()->count(3)->for($user)->create([
        'cache_hit' => true,
        'status' => RenderStatus::Completed,
    ]);

    RenderJob::factory()->count(2)->for($user)->create([
        'cache_hit' => false,
        'status' => RenderStatus::Completed,
    ]);

    expect($user->creditsUsedToday())->toBe(2)
        ->and($user->remainingRenders())->toBe(3);
});

test('failed jobs do not consume the daily limit', function () {
    $user = User::factory()->unverified()->create();

    RenderJob::factory()->for($user)->create([
        'cache_hit' => false,
        'status' => RenderStatus::Failed,
    ]);

    expect($user->creditsUsedToday())->toBe(0);
});

test('long ranges consume extra render credits', function () {
    $user = User::factory()->unverified()->create();

    RenderJob::factory()->for($user)->create([
        'cache_hit' => false,
        'status' => RenderStatus::Completed,
        'credits' => 2,
    ]);

    expect($user->creditsUsedToday())->toBe(2)
        ->and($user->remainingRenders())->toBe(3);
});

test('admin override replaces the default daily cap', function () {
    $user = User::factory()->unverified()->create([
        'daily_render_limit' => 1,
    ]);

    expect($user->dailyRenderLimit())->toBe(1);
});
