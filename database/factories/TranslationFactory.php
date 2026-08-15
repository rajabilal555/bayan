<?php

namespace Database\Factories;

use App\Models\Translation;
use App\Models\TranslationSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'translation_source_id' => TranslationSource::factory(),
            'surah' => 1,
            'ayah' => fake()->unique()->numberBetween(1, 7),
            'text' => 'In the name of Allah, the Entirely Merciful, the Especially Merciful.',
        ];
    }
}
