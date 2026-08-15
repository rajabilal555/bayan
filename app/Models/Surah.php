<?php

namespace App\Models;

use Database\Factories\SurahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $number
 * @property string $name_en
 * @property string $name_ar
 * @property int $ayah_count
 * @property bool $bismillah_pre
 */
#[Fillable(['number', 'name_en', 'name_ar', 'ayah_count', 'bismillah_pre'])]
class Surah extends Model
{
    /** @use HasFactory<SurahFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'bismillah_pre' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Ayah, $this>
     */
    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class, 'surah', 'number');
    }
}
