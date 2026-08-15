<?php

namespace App\Models;

use Database\Factories\AyahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $surah
 * @property int $ayah
 * @property string $uthmani_text
 */
#[Fillable(['surah', 'ayah', 'uthmani_text'])]
class Ayah extends Model
{
    /** @use HasFactory<AyahFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Surah, $this>
     */
    public function surahRecord(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah', 'number');
    }
}
