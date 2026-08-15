<?php

namespace Database\Factories;

use App\Models\AyahAudio;
use App\Models\Reciter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AyahAudio>
 */
class AyahAudioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reciter_id' => Reciter::factory(),
            'surah' => 1,
            'ayah' => fake()->unique()->numberBetween(1, 7),
            'path' => 'audio/test.mp3',
            'duration_ms' => 2000,
        ];
    }
}
