<?php

use App\Models\AyahAudio;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\RenderJob;
use App\Rendering\Ffmpeg;
use App\Rendering\Renderer;

test('muxes recitation audio instead of the background clip soundtrack', function () {
    $commands = [];

    $this->mock(Ffmpeg::class, function ($mock) use (&$commands): void {
        $mock->shouldReceive('run')->andReturnUsing(function (array $args) use (&$commands): void {
            $commands[] = $args;
        });
    });

    $reciter = Reciter::factory()->create();
    $background = Background::factory()->create();
    AyahAudio::factory()->recycle($reciter)->create([
        'surah' => 1,
        'ayah' => 1,
        'duration_ms' => 1000,
    ]);

    $job = RenderJob::factory()->create([
        'reciter_id' => $reciter->id,
        'background_id' => $background->id,
        'surah' => 1,
        'ayah_start' => 1,
        'ayah_end' => 1,
        'show_arabic' => false,
        'show_translation' => false,
        'loop_background' => true,
    ]);

    app(Renderer::class)->render($job);

    expect($commands)->toHaveCount(2)
        ->and($commands[1])->toContain('-map')
        ->and($commands[1])->toContain('0:v:0')
        ->and($commands[1])->toContain('1:a:0');
});
