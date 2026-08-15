<?php

namespace App\Console\Commands;

use App\Enums\Orientation;
use App\Enums\RenderStatus;
use App\Models\Ayah;
use App\Models\AyahAudio;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\RenderJob;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Models\User;
use App\Rendering\Fingerprint;
use App\Rendering\RenderPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BayanRenderSampleCommand extends Command
{
    protected $signature = 'bayan:render-sample
        {--surah=1}
        {--from=1}
        {--to=7}
        {--orientation=vertical}';

    protected $description = 'Render a sample MP4 for the first imported reciter/background';

    public function handle(RenderPipeline $pipeline): int
    {
        $surah = (int) $this->option('surah');
        $from = (int) $this->option('from');
        $to = (int) $this->option('to');
        $orientation = Orientation::from($this->option('orientation'));

        $reciter = Reciter::query()->first();
        $background = Background::query()->first();
        $source = TranslationSource::query()->first();

        if (! $reciter || ! $background) {
            $this->error('Import a reciter, ayah audio, and a background first.');

            return self::FAILURE;
        }

        $missingAudio = AyahAudio::query()
            ->where('reciter_id', $reciter->id)
            ->where('surah', $surah)
            ->whereBetween('ayah', [$from, $to])
            ->count();

        if ($missingAudio < ($to - $from + 1) || Ayah::query()->where('surah', $surah)->whereBetween('ayah', [$from, $to])->count() < ($to - $from + 1)) {
            $this->error('Ayah text or audio is incomplete for that range.');

            return self::FAILURE;
        }

        if ($source && Translation::query()->where('translation_source_id', $source->id)->where('surah', $surah)->whereBetween('ayah', [$from, $to])->count() < ($to - $from + 1)) {
            $source = null;
        }

        $user = User::query()->first() ?? User::factory()->create(['email' => 'bayan@localhost']);

        $payload = [
            'reciter_id' => $reciter->id,
            'background_id' => $background->id,
            'surah' => $surah,
            'ayah_start' => $from,
            'ayah_end' => $to,
            'orientation' => $orientation->value,
            'show_arabic' => true,
            'show_translation' => $source !== null,
            'translation_source_id' => $source?->id,
            'include_bismillah' => false,
            'loop_background' => $background->loop_default,
            'arabic_style' => config('bayan.default_styles.arabic'),
            'translation_style' => config('bayan.default_styles.translation'),
        ];

        $job = RenderJob::query()->create([
            ...$payload,
            'user_id' => $user->id,
            'fingerprint' => Fingerprint::make($payload),
            'status' => RenderStatus::Pending,
        ]);

        $pipeline->process($job->fresh());
        $job->refresh();

        if ($job->status !== RenderStatus::Completed) {
            $this->error($job->error ?? 'Render failed.');

            return self::FAILURE;
        }

        $this->info('Wrote '.Storage::disk(config('bayan.disks.renders'))->path($job->output_path));

        return self::SUCCESS;
    }
}
