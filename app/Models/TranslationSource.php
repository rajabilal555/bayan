<?php

namespace App\Models;

use Database\Factories\TranslationSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $qul_resource_id
 */
#[Fillable(['slug', 'name', 'qul_resource_id'])]
class TranslationSource extends Model
{
    /** @use HasFactory<TranslationSourceFactory> */
    use HasFactory;

    /**
     * @return HasMany<Translation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }
}
