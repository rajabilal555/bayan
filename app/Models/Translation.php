<?php

namespace App\Models;

use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $translation_source_id
 * @property int $surah
 * @property int $ayah
 * @property string $text
 */
#[Fillable(['translation_source_id', 'surah', 'ayah', 'text'])]
class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TranslationSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(TranslationSource::class, 'translation_source_id');
    }
}
