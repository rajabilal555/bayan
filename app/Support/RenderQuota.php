<?php

namespace App\Support;

class RenderQuota
{
    public static function ayahsPerRender(): int
    {
        return max(1, (int) config('bayan.limits.ayahs_per_render', 7));
    }

    public static function ayahCount(int $start, int $end, bool $includeBismillah = false, int $surah = 1): int
    {
        $count = max(0, $end - $start + 1);

        if ($includeBismillah && $surah !== 1) {
            $count++;
        }

        return $count;
    }

    public static function creditsFor(int $ayahCount): int
    {
        if ($ayahCount < 1) {
            return 1;
        }

        return (int) ceil($ayahCount / self::ayahsPerRender());
    }

    public static function maxAyahs(int $remainingCredits): int
    {
        return max(0, $remainingCredits) * self::ayahsPerRender();
    }
}
