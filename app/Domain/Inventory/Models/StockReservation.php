<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Models;

use App\Domain\Cart\Models\Cart;
use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};

class StockReservation extends Model
{
    /** @use HasFactory<StockReservationFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockReservationFactory
    {
        return StockReservationFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'quantity',
        'cart_id',
        'expires_at',
        'converted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity'     => 'integer',
            'expires_at'   => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * Get the stockable model (Product or ProductVariant).
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the cart that owns the reservation.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Check if the reservation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the reservation is converted (order placed).
     */
    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    /**
     * Check if the reservation is active (not expired and not converted).
     */
    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->isConverted();
    }

    /**
     * Scope a query to only include active reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<StockReservation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StockReservation>
     */
    public function scopeActive($query)
    {
        return $query
            ->whereNull('converted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include expired reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<StockReservation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StockReservation>
     */
    public function scopeExpired($query)
    {
        return $query
            ->whereNull('converted_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include converted reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<StockReservation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StockReservation>
     */
    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }

    /**
     * Scope a query to only include reservations for a specific cart.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<StockReservation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StockReservation>
     */
    public function scopeForCart($query, string $cartId)
    {
        return $query->where('cart_id', $cartId);
    }

    /**
     * Scope a query to only include reservations for a specific stockable.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<StockReservation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StockReservation>
     */
    public function scopeForStockable($query, Model|string $stockableOrType, int|string|null $stockableId = null)
    {
        if ($stockableOrType instanceof Model) {
            return $query
                ->where('stockable_type', get_class($stockableOrType))
                ->where('stockable_id', $stockableOrType->getKey());
        }

        return $query
            ->where('stockable_type', $stockableOrType)
            ->where('stockable_id', $stockableId);
    }
}
