<?php

namespace App\Rendering;

use App\Enums\Orientation;
use App\Models\Ayah;
use App\Models\AyahAudio;
use App\Models\Background;
use App\Models\RenderJob;
use App\Models\Translation;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Renderer
{
    public function __construct(
        private Ffmpeg $ffmpeg,
        private AssGenerator $ass,
    ) {}

    /**
     * @return array{path: string, duration_ms: int}
     */
    public function render(RenderJob $job): array
    {
        $job->load(['reciter', 'background', 'translationSource']);

        $workdir = storage_path('app/tmp/render-'.$job->id);
        mkdir($workdir, 0755, true);

        try {
            $audioFiles = $this->audioFiles($job);
            $durationMs = array_sum(array_column($audioFiles, 'duration_ms'));
            $concatList = $workdir.'/concat.txt';
            $audioPath = $workdir.'/audio.m4a';
            $assPath = $workdir.'/subs.ass';
            $outputRelative = $job->fingerprint.'.mp4';
            $outputAbsolute = Storage::disk(config('bayan.disks.renders'))->path($outputRelative);

            $this->writeConcatList($concatList, array_column($audioFiles, 'absolute'));
            $this->ffmpeg->run([
                '-f', 'concat',
                '-safe', '0',
                '-i', $concatList,
                '-c:a', 'aac',
                '-b:a', '128k',
                $audioPath,
            ]);

            file_put_contents($assPath, $this->ass->generate(
                $this->cues($job, $audioFiles),
                $job->orientation,
                $job->arabic_style,
                $job->translation_style,
                $job->show_arabic,
                $job->show_translation,
            ));

            $background = $job->background;
            if (! $background instanceof Background) {
                throw new RuntimeException('Missing background.');
            }

            $this->composite(
                $background,
                $job->orientation,
                $job->loop_background,
                $audioPath,
                $assPath,
                $durationMs,
                $outputAbsolute,
            );

            return ['path' => $outputRelative, 'duration_ms' => $durationMs];
        } finally {
            $this->cleanup($workdir);
        }
    }

    /**
     * @return list<array{surah: int, ayah: int, duration_ms: int, absolute: string}>
     */
    public function audioFiles(RenderJob $job): array
    {
        $files = [];

        if ($job->include_bismillah && $job->surah !== 1) {
            $files[] = $this->ayahAudio($job->reciter_id, 1, 1);
        }

        $audios = AyahAudio::query()
            ->where('reciter_id', $job->reciter_id)
            ->where('surah', $job->surah)
            ->whereBetween('ayah', [$job->ayah_start, $job->ayah_end])
            ->orderBy('ayah')
            ->get();

        if ($audios->count() !== ($job->ayah_end - $job->ayah_start + 1)) {
            throw new RuntimeException('Missing ayah audio for the requested range.');
        }

        foreach ($audios as $audio) {
            $files[] = [
                'surah' => $audio->surah,
                'ayah' => $audio->ayah,
                'duration_ms' => $audio->duration_ms,
                'absolute' => Storage::disk(config('bayan.disks.library'))->path($audio->path),
            ];
        }

        return $files;
    }

    /**
     * @return array{surah: int, ayah: int, duration_ms: int, absolute: string}
     */
    private function ayahAudio(int $reciterId, int $surah, int $ayah): array
    {
        $audio = AyahAudio::query()
            ->where('reciter_id', $reciterId)
            ->where('surah', $surah)
            ->where('ayah', $ayah)
            ->firstOrFail();

        return [
            'surah' => $audio->surah,
            'ayah' => $audio->ayah,
            'duration_ms' => $audio->duration_ms,
            'absolute' => Storage::disk(config('bayan.disks.library'))->path($audio->path),
        ];
    }

    /**
     * @param  list<array{surah: int, ayah: int, duration_ms: int, absolute: string}>  $audioFiles
     * @return list<array{start_ms: int, end_ms: int, arabic: string|null, translation: string|null}>
     */
    private function cues(RenderJob $job, array $audioFiles): array
    {
        $cursor = 0;
        $cues = [];

        foreach ($audioFiles as $file) {
            $end = $cursor + $file['duration_ms'];
            $isBismillah = $job->include_bismillah && $job->surah !== 1 && $file['surah'] === 1 && $file['ayah'] === 1;

            $arabic = null;
            $translation = null;

            if ($isBismillah) {
                $arabic = Ayah::query()->where('surah', 1)->where('ayah', 1)->value('uthmani_text');
                if ($job->translation_source_id) {
                    $translation = Translation::query()
                        ->where('translation_source_id', $job->translation_source_id)
                        ->where('surah', 1)
                        ->where('ayah', 1)
                        ->value('text');
                }
            } else {
                $arabic = Ayah::query()->where('surah', $file['surah'])->where('ayah', $file['ayah'])->value('uthmani_text');
                if ($job->translation_source_id) {
                    $translation = Translation::query()
                        ->where('translation_source_id', $job->translation_source_id)
                        ->where('surah', $file['surah'])
                        ->where('ayah', $file['ayah'])
                        ->value('text');
                }
            }

            $cues[] = [
                'start_ms' => $cursor,
                'end_ms' => $end,
                'arabic' => $arabic,
                'translation' => $translation,
            ];
            $cursor = $end;
        }

        return $cues;
    }

    /**
     * @param  list<string>  $paths
     */
    private function writeConcatList(string $listPath, array $paths): void
    {
        $lines = array_map(
            fn (string $path) => 'file '.$this->concatEscape($path),
            $paths,
        );

        file_put_contents($listPath, implode("\n", $lines)."\n");
    }

    private function concatEscape(string $path): string
    {
        return "'".str_replace("'", "'\\''", $path)."'";
    }

    private function composite(
        Background $background,
        Orientation $orientation,
        bool $loop,
        string $audioPath,
        string $assPath,
        int $durationMs,
        string $outputAbsolute,
    ): void {
        $bg = Storage::disk(config('bayan.disks.library'))->path($background->path);
        $fontsDir = public_path((string) config('bayan.fonts.directory'));
        $duration = number_format($durationMs / 1000, 3, '.', '');
        $filter = sprintf(
            'scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d,ass=%s:fontsdir=%s',
            $orientation->width(),
            $orientation->height(),
            $orientation->width(),
            $orientation->height(),
            $this->escapeFilterPath($assPath),
            $this->escapeFilterPath($fontsDir),
        );

        $args = $loop
            ? ['-stream_loop', '-1', '-i', $bg, '-i', $audioPath]
            : ['-loop', '1', '-i', $this->stillFrame($bg, dirname($assPath)), '-i', $audioPath];

        $this->ffmpeg->run([
            ...$args,
            '-t', $duration,
            '-map', '0:v:0',
            '-map', '1:a:0',
            '-vf', $filter,
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-preset', 'veryfast',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-shortest',
            $outputAbsolute,
        ]);
    }

    private function stillFrame(string $background, string $workdir): string
    {
        $frame = $workdir.'/still.png';
        $this->ffmpeg->run(['-i', $background, '-frames:v', '1', $frame], 60);

        return $frame;
    }

    private function escapeFilterPath(string $path): string
    {
        return str_replace(['\\', ':', "'"], ['\\\\', '\\:', "\\'"], $path);
    }

    private function cleanup(string $workdir): void
    {
        foreach (glob($workdir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($workdir);
    }
}
