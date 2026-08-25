<?php

namespace App\Actions;

use App\Enums\Orientation;
use App\Enums\RenderStatus;
use App\Jobs\RenderVideoJob;
use App\Models\AyahAudio;
use App\Models\RenderCache;
use App\Models\RenderJob;
use App\Models\Surah;
use App\Models\User;
use App\Rendering\Fingerprint;
use App\Rendering\RenderPipeline;
use App\Support\RenderQuota;
use Illuminate\Validation\ValidationException;

class SubmitRender
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): RenderJob
    {
        if (! $data['show_arabic'] && ! $data['show_translation']) {
            throw ValidationException::withMessages([
                'show_arabic' => 'Turn on Arabic text, translation, or both.',
            ]);
        }

        $surah = Surah::query()->where('number', $data['surah'])->firstOrFail();

        if ($data['ayah_start'] < 1 || $data['ayah_end'] < $data['ayah_start'] || $data['ayah_end'] > $surah->ayah_count) {
            throw ValidationException::withMessages([
                'ayah_end' => "Choose a range within 1–{$surah->ayah_count}.",
            ]);
        }

        $audioCount = AyahAudio::query()
            ->where('reciter_id', $data['reciter_id'])
            ->where('surah', $data['surah'])
            ->whereBetween('ayah', [$data['ayah_start'], $data['ayah_end']])
            ->count();

        if ($audioCount !== ($data['ayah_end'] - $data['ayah_start'] + 1)) {
            throw ValidationException::withMessages([
                'reciter_id' => 'This reciter is missing audio for that range.',
            ]);
        }

        $fingerprint = Fingerprint::make($data);
        $cached = RenderCache::query()->where('fingerprint', $fingerprint)->first();
        $ayahCount = RenderQuota::ayahCount(
            (int) $data['ayah_start'],
            (int) $data['ayah_end'],
            (bool) $data['include_bismillah'],
            (int) $data['surah'],
        );
        $credits = $cached ? 0 : RenderQuota::creditsFor($ayahCount);

        if (! $cached && $user->remainingRenders() < $credits) {
            $remaining = $user->remainingRenders();
            $maxAyahs = RenderQuota::maxAyahs($remaining);

            throw ValidationException::withMessages([
                'ayah_end' => $remaining === 0
                    ? 'Daily render limit reached.'.($user->hasVerifiedEmail() ? '' : ' Verify your email for a higher limit.')
                    : "This range is {$ayahCount} ayahs and costs {$credits} renders. You have {$remaining} left ({$maxAyahs} ayahs).",
            ]);
        }

        $job = RenderJob::query()->create([
            'user_id' => $user->id,
            'reciter_id' => $data['reciter_id'],
            'background_id' => $data['background_id'],
            'surah' => $data['surah'],
            'ayah_start' => $data['ayah_start'],
            'ayah_end' => $data['ayah_end'],
            'orientation' => Orientation::from($data['orientation']),
            'show_arabic' => $data['show_arabic'],
            'show_translation' => $data['show_translation'],
            'translation_source_id' => $data['show_translation'] ? $data['translation_source_id'] : null,
            'arabic_style' => Fingerprint::normalizeStyle($data['arabic_style'] ?? []),
            'translation_style' => Fingerprint::normalizeStyle($data['translation_style'] ?? []),
            'include_bismillah' => $data['include_bismillah'],
            'loop_background' => $data['loop_background'],
            'fingerprint' => $fingerprint,
            'credits' => $credits,
            'status' => RenderStatus::Pending,
        ]);

        if ($cached) {
            app(RenderPipeline::class)->process($job);
        } else {
            RenderVideoJob::dispatch($job->id);
        }

        return $job->refresh();
    }
}
