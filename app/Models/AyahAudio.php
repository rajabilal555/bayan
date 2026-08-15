<?php

namespace App\Models;

use Database\Factories\AyahAudioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reciter_id
 * @property int $surah
 * @property int $ayah
 * @property string $path
 * @property int $duration_ms
 */
#[Fillable(['reciter_id', 'surah', 'ayah', 'path', 'duration_ms'])]
class AyahAudio extends Model
{
    /** @use HasFactory<AyahAudioFactory> */
    use HasFactory;

    protected $table = 'ayah_audio';

    /**
     * @return BelongsTo<Reciter, $this>
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class);
    }
}
