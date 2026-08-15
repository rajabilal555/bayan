<?php

namespace App\Rendering;

use App\Enums\RenderStatus;
use App\Models\RenderCache;
use App\Models\RenderJob;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RenderPipeline
{
    public function __construct(private Renderer $renderer) {}

    public function process(RenderJob $job): void
    {
        $job->update(['status' => RenderStatus::Processing]);

        $cached = RenderCache::query()->where('fingerprint', $job->fingerprint)->first();

        if ($cached) {
            $this->attachCache($job, $cached, hit: true);

            return;
        }

        $lock = Cache::lock('render:'.$job->fingerprint, 1800);

        try {
            $lock->block(1800);

            $cached = RenderCache::query()->where('fingerprint', $job->fingerprint)->first();

            if ($cached) {
                $this->attachCache($job, $cached, hit: true);

                return;
            }

            $result = $this->renderer->render($job);

            $cache = RenderCache::query()->create([
                'fingerprint' => $job->fingerprint,
                'output_path' => $result['path'],
                'duration_ms' => $result['duration_ms'],
            ]);

            $this->attachCache($job, $cache, hit: false);
        } catch (Throwable $e) {
            $job->update([
                'status' => RenderStatus::Failed,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function attachCache(RenderJob $job, RenderCache $cache, bool $hit): void
    {
        $job->update([
            'status' => RenderStatus::Completed,
            'cache_hit' => $hit,
            'output_path' => $cache->output_path,
            'duration_ms' => $cache->duration_ms,
            'error' => null,
        ]);
    }
}
