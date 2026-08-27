<?php

namespace App\Data\Qul;

use Spatie\LaravelData\Data;

class TranslationVerseData extends Data
{
    public function __construct(
        public int $surah,
        public int $ayah,
        public string $text,
    ) {}
}
