<?php

namespace App\Data\Qul;

use Spatie\LaravelData\Data;

class RecitationAudioFileData extends Data
{
    public function __construct(public string $audio_url) {}
}
