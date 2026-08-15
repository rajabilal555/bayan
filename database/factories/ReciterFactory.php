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
            'name' => 'Mishari Rashid al-Afasy',
            'qul_resource_id' => (string) fake()->unique()->numberBetween(1, 9999),
            'granularity' => 'ayah',
        ];
    }
}
