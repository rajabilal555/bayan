<?php

namespace Database\Factories;

use App\Models\Surah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Surah>
 */
class SurahFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 114);

        return [
            'number' => $number,
            'name_en' => 'Al-Fatihah',
            'name_ar' => 'الفاتحة',
            'ayah_count' => 7,
            'bismillah_pre' => $number !== 1 && $number !== 9,
        ];
    }
}
