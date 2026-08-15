<?php

namespace Database\Factories;

use App\Enums\Orientation;
use App\Enums\RenderStatus;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\RenderJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenderJob>
 */
class RenderJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reciter_id' => Reciter::factory(),
            'background_id' => Background::factory(),
            'surah' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'orientation' => Orientation::Vertical,
            'show_arabic' => true,
            'show_translation' => true,
            'translation_source_id' => null,
            'arabic_style' => config('bayan.default_styles.arabic'),
            'translation_style' => config('bayan.default_styles.translation'),
            'include_bismillah' => false,
            'loop_background' => true,
            'fingerprint' => hash('sha256', fake()->unique()->uuid()),
            'cache_hit' => false,
            'status' => RenderStatus::Pending,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RenderStatus::Completed,
            'output_path' => 'renders/test.mp4',
            'duration_ms' => 8000,
        ]);
    }
}
