<?php

namespace App\Support;

class SurahRange
{
    /**
     * @return list<int>|null
     */
    public static function parse(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $ids = [];

        foreach (explode(',', $value) as $part) {
            $part = trim($part);

            if (str_contains($part, '-')) {
                [$from, $to] = array_map('intval', explode('-', $part, 2));
                $ids = [...$ids, ...range($from, $to)];

                continue;
            }

            $ids[] = (int) $part;
        }

        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);

        return $ids;
    }
}
