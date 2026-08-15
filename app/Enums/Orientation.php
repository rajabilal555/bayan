<?php

namespace App\Enums;

enum Orientation: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';

    public function width(): int
    {
        return $this === self::Vertical ? 1080 : 1920;
    }

    public function height(): int
    {
        return $this === self::Vertical ? 1920 : 1080;
    }
}
