<?php

namespace App\Models;

use Database\Factories\ReciterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $qul_resource_id
 * @property string $granularity
 */
#[Fillable(['name', 'qul_resource_id', 'granularity'])]
class Reciter extends Model
{
    /** @use HasFactory<ReciterFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'granularity' => 'ayah',
    ];

    /**
     * @return HasMany<AyahAudio, $this>
     */
    public function ayahAudio(): HasMany
    {
        return $this->hasMany(AyahAudio::class);
    }
}
