<?php

namespace App\Rendering;

use Illuminate\Support\Facades\Log;
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
        $command = [
            (string) config('bayan.ffmpeg'),
            '-y',
            ...$arguments,
        ];
        $line = $this->commandLine($command);

        Log::info('ffmpeg', ['command' => $line]);

        $result = Process::timeout($timeout)->run($command);

        if ($result->failed()) {
            throw new RuntimeException('ffmpeg failed: '.$line."\n".$result->errorOutput().$result->output());
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function commandLine(array $command): string
    {
        return implode(' ', array_map(escapeshellarg(...), $command));
    }
}
