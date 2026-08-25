<?php

namespace App\Models;

use App\Enums\Orientation;
use App\Enums\RenderStatus;
use Database\Factories\RenderJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * @property int $id
 * @property int $user_id
 * @property int $reciter_id
 * @property int $background_id
 * @property int $surah
 * @property int $ayah_start
 * @property int $ayah_end
 * @property Orientation $orientation
 * @property bool $show_arabic
 * @property bool $show_translation
 * @property int|null $translation_source_id
 * @property array<string, mixed> $arabic_style
 * @property array<string, mixed> $translation_style
 * @property bool $include_bismillah
 * @property bool $loop_background
 * @property string $fingerprint
 * @property bool $cache_hit
 * @property int $credits
 * @property RenderStatus $status
 * @property string|null $error
 * @property string|null $output_path
 * @property int|null $duration_ms
 */
#[Fillable([
    'user_id',
    'reciter_id',
    'background_id',
    'surah',
    'ayah_start',
    'ayah_end',
    'orientation',
    'show_arabic',
    'show_translation',
    'translation_source_id',
    'arabic_style',
    'translation_style',
    'include_bismillah',
    'loop_background',
    'fingerprint',
    'cache_hit',
    'credits',
    'status',
    'error',
    'output_path',
    'duration_ms',
])]
class RenderJob extends Model
{
    /** @use HasFactory<RenderJobFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'cache_hit' => false,
        'credits' => 1,
        'show_arabic' => true,
        'show_translation' => false,
        'include_bismillah' => false,
        'loop_background' => true,
    ];

    protected function casts(): array
    {
        return [
            'orientation' => Orientation::class,
            'status' => RenderStatus::class,
            'show_arabic' => 'boolean',
            'show_translation' => 'boolean',
            'include_bismillah' => 'boolean',
            'loop_background' => 'boolean',
            'cache_hit' => 'boolean',
            'arabic_style' => 'array',
            'translation_style' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Reciter, $this>
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class);
    }

    /**
     * @return BelongsTo<Background, $this>
     */
    public function background(): BelongsTo
    {
        return $this->belongsTo(Background::class);
    }

    /**
     * @return BelongsTo<TranslationSource, $this>
     */
    public function translationSource(): BelongsTo
    {
        return $this->belongsTo(TranslationSource::class);
    }

    public function isPlayable(): bool
    {
        return $this->status === RenderStatus::Completed && filled($this->output_path);
    }

    public function streamUrl(): ?string
    {
        if (! $this->isPlayable()) {
            return null;
        }

        return URL::temporarySignedRoute('renders.stream', now()->addMinutes(30), ['job' => $this]);
    }

    public function downloadUrl(): ?string
    {
        if (! $this->isPlayable()) {
            return null;
        }

        return URL::temporarySignedRoute('renders.download', now()->addMinutes(30), ['job' => $this]);
    }
}
