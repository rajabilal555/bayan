<?php

namespace App\Data\Qul;

use Spatie\LaravelData\Data;

class ChapterData extends Data
{
    public function __construct(
        public int $id,
        public string $name_simple,
        public string $name_arabic,
        public int $verses_count,
        public bool $bismillah_pre,
    ) {}
}
