<?php

namespace App\Jobs;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class RunImportCommand implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1700;

    public int $tries = 1;

    public int $uniqueFor = 1800;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $command,
        public array $parameters = [],
    ) {}

    public function uniqueId(): string
    {
        return hash('sha256', $this->command.json_encode($this->parameters));
    }

    public function handle(): void
    {
        $exit = Artisan::call($this->command, $this->parameters);

        if ($exit !== Command::SUCCESS) {
            throw new RuntimeException(trim(Artisan::output()) ?: "{$this->command} failed.");
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Library import failed', [
            'command' => $this->command,
            'parameters' => $this->parameters,
            'error' => $exception?->getMessage(),
        ]);
    }
}
