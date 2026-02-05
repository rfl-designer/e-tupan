<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Models;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_sku',
        'variant_name',
        'variant_sku',
        'quantity',
        'unit_price',
        'sale_price',
        'subtotal',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'integer',
            'sale_price' => 'integer',
            'subtotal'   => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant for the item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the effective price (sale price if available, otherwise unit price).
     */
    public function getEffectivePrice(): int
    {
        return $this->sale_price ?? $this->unit_price;
    }

    /**
     * Get the unit price in reais (BRL).
     */
    protected function unitPriceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->unit_price / 100,
        );
    }

    /**
     * Get the sale price in reais (BRL).
     */
    protected function salePriceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->sale_price !== null ? $this->sale_price / 100 : null,
        );
    }

    /**
     * Get the effective price in reais (BRL).
     */
    protected function effectivePriceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->getEffectivePrice() / 100,
        );
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
     * Check if the item has a discount.
     */
    public function hasDiscount(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->unit_price;
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentage(): ?int
    {
        if (!$this->hasDiscount() || $this->unit_price <= 0) {
            return null;
        }

        return (int) round((($this->unit_price - $this->sale_price) / $this->unit_price) * 100);
    }

    /**
     * Get the full product name (product + variant).
     */
    public function getFullNameAttribute(): string
    {
        if ($this->variant_name) {
            return $this->product_name . ' - ' . $this->variant_name;
        }

        return $this->product_name;
    }

    /**
     * Get the display SKU (variant SKU if available, otherwise product SKU).
     */
    public function getDisplaySkuAttribute(): ?string
    {
        return $this->variant_sku ?? $this->product_sku;
    }
}
