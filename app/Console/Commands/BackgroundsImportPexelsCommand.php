<?php

namespace App\Console\Commands;

use App\Models\Background;
use App\Rendering\Ffmpeg;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackgroundsImportPexelsCommand extends Command
{
    protected $signature = 'backgrounds:import-pexels {--limit=8} {--placeholder : Generate local color clips instead of calling Pexels}';

    protected $description = 'Download a curated set of scenery clips from Pexels (or generate placeholders)';

    /**
     * @var array<int, array{query: string, category: string, loop: bool}>
     */
    private array $queries = [
        ['query' => 'rain forest', 'category' => 'rain', 'loop' => true],
        ['query' => 'ocean waves', 'category' => 'ocean', 'loop' => true],
        ['query' => 'mountain landscape', 'category' => 'mountains', 'loop' => false],
        ['query' => 'night sky stars', 'category' => 'night sky', 'loop' => true],
        ['query' => 'desert dunes', 'category' => 'desert', 'loop' => false],
    ];

    public function handle(Ffmpeg $ffmpeg): int
    {
        if ($this->option('placeholder') || blank(config('bayan.pexels.key'))) {
            return $this->placeholders($ffmpeg);
        }

        $disk = Storage::disk(config('bayan.disks.library'));
        $limit = max(1, (int) $this->option('limit'));
        $imported = 0;

        foreach ($this->queries as $query) {
            if ($imported >= $limit) {
                break;
            }

            $videos = Http::withHeaders(['Authorization' => config('bayan.pexels.key')])
                ->get('https://api.pexels.com/videos/search', [
                    'query' => $query['query'],
                    'per_page' => 1,
                    'orientation' => 'landscape',
                ])
                ->throw()
                ->json('videos');

            if (! is_array($videos)) {
                continue;
            }

            foreach ($videos as $video) {
                if ($imported >= $limit || ! is_array($video)) {
                    break;
                }

                $videoFiles = [];
                foreach ($video['video_files'] ?? [] as $candidate) {
                    if (is_array($candidate)) {
                        $videoFiles[] = $candidate;
                    }
                }

                $file = collect($videoFiles)
                    ->filter(fn (array $f) => ($f['width'] ?? 0) >= 1280)
                    ->sortBy('width')
                    ->first() ?? $videoFiles[0] ?? null;

                if (! $file) {
                    continue;
                }

                $slug = Str::slug($query['category']).'-'.$video['id'];
                $path = "backgrounds/{$slug}.mp4";
                $thumb = "backgrounds/{$slug}.jpg";
                $disk->put($path, Http::timeout(120)->get($file['link'])->throw()->body());
                $ffmpeg->run(['-i', $disk->path($path), '-frames:v', '1', $disk->path($thumb)], 60);

                Background::query()->updateOrCreate(
                    ['path' => $path],
                    [
                        'label' => ucfirst($query['category']).' #'.$video['id'],
                        'category' => $query['category'],
                        'thumbnail_path' => $thumb,
                        'source' => 'pexels',
                        'attribution' => 'Video by '.($video['user']['name'] ?? 'Pexels').' on Pexels',
                        'loop_default' => $query['loop'],
                    ],
                );
                $imported++;
                $this->line($path);
            }
        }

        $this->info("Imported {$imported} backgrounds.");

        return self::SUCCESS;
    }

    private function placeholders(Ffmpeg $ffmpeg): int
    {
        $disk = Storage::disk(config('bayan.disks.library'));
        $colors = [
            'rain' => '0x1b3a4b',
            'ocean' => '0x0b4f6c',
            'mountains' => '0x4a5d4e',
            'night sky' => '0x0b1026',
            'desert' => '0xc4a574',
        ];

        foreach ($colors as $category => $color) {
            $slug = Str::slug($category).'-placeholder';
            $path = "backgrounds/{$slug}.mp4";
            $thumb = "backgrounds/{$slug}.jpg";
            $disk->makeDirectory('backgrounds');
            $ffmpeg->run([
                '-f', 'lavfi',
                '-i', "color=c={$color}:s=1920x1080:d=8",
                '-c:v', 'libx264',
                '-pix_fmt', 'yuv420p',
                $disk->path($path),
            ], 60);
            $ffmpeg->run(['-i', $disk->path($path), '-frames:v', '1', $disk->path($thumb)], 30);

            Background::query()->updateOrCreate(
                ['path' => $path],
                [
                    'label' => ucfirst($category).' (placeholder)',
                    'category' => $category,
                    'thumbnail_path' => $thumb,
                    'source' => 'placeholder',
                    'attribution' => null,
                    'loop_default' => in_array($category, ['rain', 'ocean', 'night sky'], true),
                ],
            );
        }

        $this->info('Seeded placeholder backgrounds. Pass a PEXELS_API_KEY to download real clips.');

        return self::SUCCESS;
    }
}
