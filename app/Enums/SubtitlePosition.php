<?php

namespace App\Enums;

enum SubtitlePosition: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';

    public function assAlignment(): int
    {
        return match ($this) {
            self::Top => 8,
            self::Center => 5,
            self::Bottom => 2,
        };
    }
}
