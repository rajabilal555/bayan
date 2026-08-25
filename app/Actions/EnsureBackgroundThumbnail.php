<?php

namespace App\Actions;

use App\Models\Background;
use App\Rendering\Ffmpeg;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EnsureBackgroundThumbnail
{
    public function __construct(private Ffmpeg $ffmpeg) {}

    public function handle(Background $background): void
    {
        if (filled($background->thumbnail_path)) {
            return;
        }

        if (blank($background->path)) {
            return;
        }

        $disk = Storage::disk((string) config('bayan.disks.library'));

        if (! $disk->exists($background->path)) {
            return;
        }

        $thumb = Str::of($background->path)->beforeLast('.').'.jpg';

        try {
            $this->ffmpeg->run([
                '-i',
                $disk->path($background->path),
                '-frames:v',
                '1',
                $disk->path((string) $thumb),
            ], 60);
        } catch (RuntimeException $exception) {
            Log::warning('Background thumbnail failed', [
                'background_id' => $background->id,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $background->update(['thumbnail_path' => (string) $thumb]);
    }
}
