<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $disposable_check_passed
 * @property bool $is_admin
 * @property int|null $daily_render_limit
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'disposable_check_passed', 'is_admin', 'daily_render_limit'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'disposable_check_passed' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'app' || $this->is_admin;
    }

    /**
     * @return HasMany<RenderJob, $this>
     */
    public function renderJobs(): HasMany
    {
        return $this->hasMany(RenderJob::class);
    }

    public function dailyRenderLimit(): int
    {
        if ($this->daily_render_limit !== null) {
            return $this->daily_render_limit;
        }

        return $this->hasVerifiedEmail()
            ? (int) config('bayan.limits.verified')
            : (int) config('bayan.limits.unverified');
    }

    public function cacheMissesToday(): int
    {
        return $this->renderJobs()
            ->where('cache_hit', false)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now('UTC')->startOfDay())
            ->count();
    }

    public function remainingRenders(): int
    {
        return max(0, $this->dailyRenderLimit() - $this->cacheMissesToday());
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
