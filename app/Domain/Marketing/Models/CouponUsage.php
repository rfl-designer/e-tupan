<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Models;

use App\Models\User;
use Database\Factories\CouponUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    /** @use HasFactory<CouponUsageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CouponUsageFactory
    {
        return CouponUsageFactory::new();
    }

    /**
     * Get the coupon that was used.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the user who used the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
