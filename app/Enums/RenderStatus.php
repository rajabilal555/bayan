<?php

namespace App\Enums;

enum RenderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued',
            self::Processing => 'Rendering',
            self::Completed => 'Ready',
            self::Failed => 'Failed',
        };
    }
}
