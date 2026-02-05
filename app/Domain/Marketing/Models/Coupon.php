<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Models;

use App\Domain\Cart\Models\Cart;
use App\Domain\Marketing\Enums\CouponType;
use App\Models\User;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order_value',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_user',
        'starts_at',
        'expires_at',
        'is_active',
        'times_used',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'                 => CouponType::class,
            'value'                => 'integer',
            'minimum_order_value'  => 'integer',
            'maximum_discount'     => 'integer',
            'usage_limit'          => 'integer',
            'usage_limit_per_user' => 'integer',
            'times_used'           => 'integer',
            'starts_at'            => 'datetime',
            'expires_at'           => 'datetime',
            'is_active'            => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Coupon $coupon) {
            $coupon->code = strtoupper($coupon->code);
        });

        static::updating(function (Coupon $coupon) {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    /**
     * Get the usages of this coupon.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Get the carts using this coupon.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the user who created this coupon.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the coupon is currently valid (within date range).
     */
    public function isWithinDateRange(): bool
    {
        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon has reached its usage limit.
     */
    public function hasReachedUsageLimit(): bool
    {
        if ($this->usage_limit === null) {
            return false;
        }

        return $this->times_used >= $this->usage_limit;
    }

    /**
     * Check if a user has reached their usage limit for this coupon.
     */
    public function hasUserReachedLimit(?int $userId): bool
    {
        if ($userId === null || $this->usage_limit_per_user === null) {
            return false;
        }

        $userUsageCount = $this->usages()
            ->where('user_id', $userId)
            ->count();

        return $userUsageCount >= $this->usage_limit_per_user;
    }

    /**
     * Check if the order meets the minimum value requirement.
     */
    public function meetsMinimumOrderValue(int $orderValue): bool
    {
        if ($this->minimum_order_value === null) {
            return true;
        }

        return $orderValue >= $this->minimum_order_value;
    }

    /**
     * Check if the coupon can be applied.
     */
    public function canBeApplied(int $orderValue, ?int $userId = null): bool
    {
        return $this->is_active
            && $this->isWithinDateRange()
            && !$this->hasReachedUsageLimit()
            && !$this->hasUserReachedLimit($userId)
            && $this->meetsMinimumOrderValue($orderValue);
    }

    /**
     * Calculate the discount for a given order value.
     */
    public function calculateDiscount(int $orderValue, ?int $shippingCost = null): int
    {
        $discount = match ($this->type) {
            CouponType::Percentage   => (int) ($orderValue * ($this->value / 100)),
            CouponType::Fixed        => $this->value ?? 0,
            CouponType::FreeShipping => $shippingCost ?? 0,
        };

        // Apply maximum discount limit for percentage type
        if ($this->type === CouponType::Percentage && $this->maximum_discount !== null) {
            $discount = min($discount, $this->maximum_discount);
        }

        // Discount cannot exceed order value (except for free shipping)
        if ($this->type !== CouponType::FreeShipping) {
            $discount = min($discount, $orderValue);
        }

        return max(0, $discount);
    }

    /**
     * Increment usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    /**
     * Decrement usage counter.
     */
    public function decrementUsage(): void
    {
        if ($this->times_used > 0) {
            $this->decrement('times_used');
        }
    }

    /**
     * Get value in reais for display.
     */
    protected function valueInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->value !== null && $this->type === CouponType::Fixed
                ? $this->value / 100
                : null,
        );
    }

    /**
     * Get minimum order value in reais for display.
     */
    protected function minimumOrderValueInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->minimum_order_value !== null
                ? $this->minimum_order_value / 100
                : null,
        );
    }

    /**
     * Get maximum discount in reais for display.
     */
    protected function maximumDiscountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->maximum_discount !== null
                ? $this->maximum_discount / 100
                : null,
        );
    }

    /**
     * Scope a query to only include active coupons.
     *
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include valid coupons (within date range).
     *
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeValid(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Scope a query to find by code.
     *
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }
}
