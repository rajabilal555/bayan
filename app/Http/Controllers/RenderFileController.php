<?php

namespace App\Http\Controllers;

use App\Enums\RenderStatus;
use App\Models\RenderJob;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderFileController extends Controller
{
    public function stream(RenderJob $job): BinaryFileResponse
    {
        $this->authorizeAccess($job);

        return response()->file(
            Storage::disk(config('bayan.disks.renders'))->path($job->output_path),
            ['Content-Type' => 'video/mp4'],
        );
    }

    public function download(RenderJob $job): StreamedResponse
    {
        $this->authorizeAccess($job);

        return Storage::disk(config('bayan.disks.renders'))->download(
            $job->output_path,
            'bayan-'.$job->id.'.mp4',
        );
    }

    private function authorizeAccess(RenderJob $job): void
    {
        abort_unless(auth()->id() === $job->user_id || auth()->user()?->is_admin, 403);
        abort_unless($job->status === RenderStatus::Completed && filled($job->output_path), 404);
    }
}
