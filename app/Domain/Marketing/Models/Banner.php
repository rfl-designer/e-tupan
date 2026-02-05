<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Models;

use App\Domain\Admin\Models\Admin;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'image_desktop',
        'image_mobile',
        'link',
        'alt_text',
        'position',
        'is_active',
        'starts_at',
        'ends_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position'  => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Banner $banner) {
            if (!$banner->isDirty('position') || $banner->position === 0) {
                $maxPosition      = static::query()->max('position') ?? 0;
                $banner->position = $maxPosition + 1;
            }
        });
    }

    /**
     * Get the admin who created this banner.
     *
     * @return BelongsTo<Admin, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get the admin who last updated this banner.
     *
     * @return BelongsTo<Admin, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Check if the banner is within its display period.
     */
    public function isWithinPeriod(): bool
    {
        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the banner is scheduled (starts_at is in the future).
     */
    public function isScheduled(): bool
    {
        return $this->starts_at !== null && now()->lt($this->starts_at);
    }

    /**
     * Check if the banner is expired (ends_at is in the past).
     */
    public function isExpired(): bool
    {
        return $this->ends_at !== null && now()->gt($this->ends_at);
    }

    /**
     * Get the period status of the banner.
     *
     * @return 'scheduled'|'active'|'expired'
     */
    public function getPeriodStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isScheduled()) {
            return 'scheduled';
        }

        return 'active';
    }

    /**
     * Get the translated period status label.
     */
    public function getPeriodStatusLabel(): string
    {
        return match ($this->getPeriodStatus()) {
            'scheduled' => 'Agendado',
            'expired'   => 'Expirado',
            'active'    => 'Ativo',
        };
    }

    /**
     * Get the color for the period status badge.
     */
    public function getPeriodStatusColor(): string
    {
        return match ($this->getPeriodStatus()) {
            'scheduled' => 'yellow',
            'expired'   => 'red',
            'active'    => 'green',
        };
    }

    /**
     * Check if the banner should be displayed.
     */
    public function shouldDisplay(): bool
    {
        return $this->is_active && $this->isWithinPeriod();
    }

    /**
     * Get the effective mobile image (fallback to desktop if not set).
     */
    public function getEffectiveMobileImage(): string
    {
        return $this->image_mobile ?? $this->image_desktop;
    }

    /**
     * Check if the link is external.
     */
    public function isExternalLink(): bool
    {
        if ($this->link === null) {
            return false;
        }

        return str_starts_with($this->link, 'http://') || str_starts_with($this->link, 'https://');
    }

    /**
     * Scope a query to only include active banners.
     *
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include banners within display period.
     *
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeWithinPeriod(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope a query to only include displayable banners.
     *
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeDisplayable(Builder $query): Builder
    {
        return $query->active()->withinPeriod();
    }

    /**
     * Scope a query to order by position.
     *
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
