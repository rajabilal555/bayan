<?php

namespace App\Rendering;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class Ffmpeg
{
    public function durationMs(string $path): int
    {
        $result = Process::timeout(30)->run([
            (string) config('bayan.ffprobe'),
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);

        if ($result->failed()) {
            throw new RuntimeException('ffprobe failed: '.$result->errorOutput());
        }

        return (int) round(((float) trim($result->output())) * 1000);
    }

    /**
     * @param  list<string>  $arguments
     */
    public function run(array $arguments, int $timeout = 1700): void
    {
        $result = Process::timeout($timeout)->run([
            (string) config('bayan.ffmpeg'),
            '-y',
            ...$arguments,
        ]);

        if ($result->failed()) {
            throw new RuntimeException('ffmpeg failed: '.$result->errorOutput().$result->output());
        }
    }
}
