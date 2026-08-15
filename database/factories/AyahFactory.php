<?php

namespace Database\Factories;

use App\Models\Ayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ayah>
 */
class AyahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'surah' => 1,
            'ayah' => fake()->unique()->numberBetween(1, 7),
            'uthmani_text' => 'بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ',
        ];
    }
}
