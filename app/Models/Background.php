<?php

namespace App\Models;

use Database\Factories\BackgroundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @property int $id
 * @property string $label
 * @property string $category
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string $source
 * @property string|null $attribution
 * @property bool $loop_default
 */
#[Fillable(['label', 'category', 'path', 'thumbnail_path', 'source', 'attribution', 'loop_default'])]
class Background extends Model
{
    /** @use HasFactory<BackgroundFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'loop_default' => true,
        'source' => 'pexels',
    ];

    protected function casts(): array
    {
        return [
            'loop_default' => 'boolean',
        ];
    }

    public function thumbnailUrl(): ?string
    {
        if (blank($this->thumbnail_path)) {
            return null;
        }

        $disk = Storage::disk((string) config('bayan.disks.library'));

        if (! $disk->exists($this->thumbnail_path)) {
            return null;
        }

        try {
            return $disk->temporaryUrl(
                $this->thumbnail_path,
                now()->addMinutes((int) config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour(),
            );
        } catch (RuntimeException) {
            return $disk->url($this->thumbnail_path);
        }
    }
}
