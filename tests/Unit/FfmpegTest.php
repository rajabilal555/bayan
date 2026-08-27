<?php

use App\Rendering\Ffmpeg;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;

test('logs the ffmpeg command line before running', function () {
    Process::fake();

    $logged = [];

    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
        if ($event->message === 'ffmpeg') {
            $logged[] = $event->context['command'] ?? '';
        }
    });

    app(Ffmpeg::class)->run([
        '-i',
        '/tmp/in.mp4',
        '-vf',
        'scale=1080:1920',
        '/tmp/out.mp4',
    ]);

    expect($logged)->toHaveCount(1)
        ->and($logged[0])->toContain('ffmpeg')
        ->and($logged[0])->toContain('-y')
        ->and($logged[0])->toContain('/tmp/in.mp4')
        ->and($logged[0])->toContain('scale=1080:1920')
        ->and($logged[0])->toContain('/tmp/out.mp4');
});
