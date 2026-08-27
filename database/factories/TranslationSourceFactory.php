<?php

namespace Database\Factories;

use App\Models\TranslationSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationSource>
 */
class TranslationSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => 'Saheeh International',
        ];
    }
}
