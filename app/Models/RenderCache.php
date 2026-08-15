<?php

namespace App\Models;

use Database\Factories\RenderCacheFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $fingerprint
 * @property string $output_path
 * @property int $duration_ms
 */
#[Fillable(['fingerprint', 'output_path', 'duration_ms'])]
class RenderCache extends Model
{
    /** @use HasFactory<RenderCacheFactory> */
    use HasFactory;
}
