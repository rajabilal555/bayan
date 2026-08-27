<?php

namespace Database\Factories;

use App\Models\Reciter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reciter>
 */
class ReciterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'granularity' => 'ayah',
        ];
    }
}
