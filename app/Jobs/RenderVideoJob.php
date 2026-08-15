<?php

namespace App\Jobs;

use App\Models\RenderJob;
use App\Rendering\RenderPipeline;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RenderVideoJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1700;

    public int $tries = 1;

    public int $uniqueFor = 1800;

    public function __construct(public int $renderJobId) {}

    public function uniqueId(): string
    {
        return (string) RenderJob::query()->findOrFail($this->renderJobId)->fingerprint;
    }

    public function handle(RenderPipeline $pipeline): void
    {
        $job = RenderJob::query()->findOrFail($this->renderJobId);
        $pipeline->process($job);
    }

    public function failed(?Throwable $exception): void
    {
        RenderJob::query()->whereKey($this->renderJobId)->update([
            'status' => 'failed',
            'error' => $exception?->getMessage(),
        ]);
    }
}
