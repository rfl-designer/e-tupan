<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Models;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Marketing\Models\Coupon;
use App\Models\User;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'shipping_zipcode',
        'shipping_method',
        'shipping_cost',
        'shipping_days',
        'shipping_quote_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'last_activity_at',
        'abandoned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'           => CartStatus::class,
            'shipping_cost'    => 'integer',
            'shipping_days'    => 'integer',
            'subtotal'         => 'integer',
            'discount'         => 'integer',
            'total'            => 'integer',
            'last_activity_at' => 'datetime',
            'abandoned_at'     => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    /**
     * Get the user that owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the coupon applied to the cart.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Check if the cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Get the total number of items in the cart.
     */
    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    /**
     * Get the number of unique products in the cart.
     */
    public function uniqueItemCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Calculate and update cart totals.
     */
    public function calculateTotals(): self
    {
        $subtotal = 0;

        foreach ($this->items as $item) {
            $subtotal += $item->getSubtotal();
        }

        $this->subtotal = $subtotal;
        $this->total    = $subtotal - $this->discount + ($this->shipping_cost ?? 0);

        return $this;
    }

    /**
     * Recalculate and save cart totals.
     */
    public function recalculateTotals(): self
    {
        $this->load('items');
        $this->calculateTotals();
        $this->save();

        return $this;
    }

    /**
     * Get the subtotal in reais (BRL).
     */
    protected function subtotalInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->subtotal / 100,
        );
    }

    /**
     * Get the discount in reais (BRL).
     */
    protected function discountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->discount / 100,
        );
    }

    /**
     * Get the total in reais (BRL).
     */
    protected function totalInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->total / 100,
        );
    }

    /**
     * Get the shipping cost in reais (BRL).
     */
    protected function shippingCostInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->shipping_cost !== null ? $this->shipping_cost / 100 : null,
        );
    }

    /**
     * Check if the cart is active.
     */
    public function isActive(): bool
    {
        return $this->status === CartStatus::Active;
    }

    /**
     * Check if the cart is abandoned.
     */
    public function isAbandoned(): bool
    {
        return $this->status === CartStatus::Abandoned;
    }

    /**
     * Check if the cart is converted.
     */
    public function isConverted(): bool
    {
        return $this->status === CartStatus::Converted;
    }

    /**
     * Scope a query to only include active carts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Cart>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Cart>
     */
    public function scopeActive($query)
    {
        return $query->where('status', CartStatus::Active);
    }

    /**
     * Scope a query to only include abandoned carts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Cart>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Cart>
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', CartStatus::Abandoned);
    }

    /**
     * Scope a query to only include carts for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Cart>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Cart>
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include carts for a specific session.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Cart>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Cart>
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Touch the last activity timestamp.
     */
    public function touchLastActivity(): self
    {
        $this->last_activity_at = now();
        $this->save();

        return $this;
    }
}
