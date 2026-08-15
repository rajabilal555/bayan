<?php

namespace Database\Factories;

use App\Models\RenderCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenderCache>
 */
class RenderCacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fingerprint' => hash('sha256', fake()->unique()->uuid()),
            'output_path' => 'renders/test.mp4',
            'duration_ms' => 8000,
        ];
    }
}
