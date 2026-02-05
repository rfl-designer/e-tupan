<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Models;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'sale_price',
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
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }

    /**
     * Get the cart that owns the item.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant for this item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the effective price for this item (sale_price if available, otherwise unit_price).
     */
    public function getEffectivePrice(): int
    {
        return $this->sale_price ?? $this->unit_price;
    }

    /**
     * Get the subtotal for this item (effective price * quantity).
     */
    public function getSubtotal(): int
    {
        return $this->getEffectivePrice() * $this->quantity;
    }

    /**
     * Get the subtotal in reais (BRL).
     */
    protected function subtotalInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->getSubtotal() / 100,
        );
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
     * Check if this item is on sale.
     */
    public function isOnSale(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->unit_price;
    }

    /**
     * Get the discount percentage for this item.
     */
    public function getDiscountPercentage(): ?int
    {
        if (!$this->isOnSale() || $this->unit_price <= 0) {
            return null;
        }

        return (int) round((($this->unit_price - $this->sale_price) / $this->unit_price) * 100);
    }

    /**
     * Get the display name for this item (product name + variant attributes if applicable).
     */
    public function getDisplayName(): string
    {
        if ($this->variant_id !== null && $this->variant !== null) {
            return $this->variant->getName();
        }

        return $this->product->name;
    }

    /**
     * Get the stockable model for this item (variant if available, otherwise product).
     */
    public function getStockable(): Product|ProductVariant
    {
        if ($this->variant_id !== null && $this->variant !== null) {
            return $this->variant;
        }

        return $this->product;
    }

    /**
     * Get the available stock for this item.
     */
    public function getAvailableStock(): int
    {
        $stockable = $this->getStockable();

        if ($stockable instanceof ProductVariant) {
            return $stockable->stock_quantity ?? 0;
        }

        if (!$stockable->manage_stock) {
            return PHP_INT_MAX; // Unlimited stock
        }

        return $stockable->stock_quantity ?? 0;
    }
}
