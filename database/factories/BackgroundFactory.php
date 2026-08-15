<?php

namespace Database\Factories;

use App\Models\Background;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Background>
 */
class BackgroundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Ocean',
            'category' => 'ocean',
            'path' => 'backgrounds/ocean.mp4',
            'thumbnail_path' => 'backgrounds/ocean.jpg',
            'source' => 'pexels',
            'attribution' => 'Pexels',
            'loop_default' => true,
        ];
    }
}
