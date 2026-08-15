@php
    /** @var \App\Models\RenderJob $job */
    $job = $getRecord();
    $aspect = $job->orientation === \App\Enums\Orientation::Vertical ? '9 / 16' : '16 / 9';
@endphp

<div
    class="overflow-hidden rounded-xl bg-gray-950"
    style="aspect-ratio: {{ $aspect }}"
>
    @if ($job->isPlayable())
        <div class="h-full w-full" wire:ignore>
            <video
                class="h-full w-full object-contain"
                controls
                preload="metadata"
                playsinline
                src="{{ $job->streamUrl() }}"
            ></video>
        </div>
    @else
        <div class="flex h-full flex-col items-center justify-center gap-1 px-4 text-center text-sm text-white/70">
            @if ($job->status === \App\Enums\RenderStatus::Failed)
                <span>Couldn’t finish this one.</span>
                <span>Try creating it again.</span>
            @elseif ($job->status === \App\Enums\RenderStatus::Processing)
                <span>Rendering…</span>
            @else
                <span>Queued</span>
            @endif
        </div>
    @endif
</div>
