<?php

namespace App\Rendering;

use App\Enums\SubtitlePosition;
use Illuminate\Support\Arr;

final readonly class AssLayout
{
    public int $wrapStyle;

    public int $marginX;

    public int $marginTop;

    public int $marginCenter;

    public int $marginBottom;

    public function __construct()
    {
        $this->wrapStyle = (int) config('bayan.subtitles.wrap_style');
        $this->marginX = (int) config('bayan.subtitles.margin_x');
        $margins = config('bayan.subtitles.margin_v');
        $this->marginTop = (int) Arr::get($margins, 'top', 120);
        $this->marginCenter = (int) Arr::get($margins, 'center', 0);
        $this->marginBottom = (int) Arr::get($margins, 'bottom', 160);
    }

    public function wrapOverride(): string
    {
        return '{\\q'.$this->wrapStyle.'}';
    }

    public function marginV(SubtitlePosition $position): int
    {
        return match ($position) {
            SubtitlePosition::Top => $this->marginTop,
            SubtitlePosition::Center => $this->marginCenter,
            SubtitlePosition::Bottom => $this->marginBottom,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function sizeOptions(): array
    {
        $options = [];

        foreach (config('bayan.subtitles.sizes', []) as $size) {
            $size = (int) $size;
            $options[$size] = (string) $size;
        }

        return $options;
    }
}
