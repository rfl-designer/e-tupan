<?php

declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use App\Domain\Inventory\Contracts\StockableInterface;
use App\Domain\Inventory\Traits\HasStock;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class ProductVariant extends Model implements StockableInterface
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;
    use HasStock;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock_quantity',
        'weight',
        'length',
        'width',
        'height',
        'is_active',
        'low_stock_threshold',
        'low_stock_notified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'                 => 'integer',
            'stock_quantity'        => 'integer',
            'weight'                => 'decimal:3',
            'length'                => 'decimal:2',
            'width'                 => 'decimal:2',
            'height'                => 'decimal:2',
            'is_active'             => 'boolean',
            'low_stock_threshold'   => 'integer',
            'low_stock_notified_at' => 'datetime',
        ];
    }

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attribute values for the variant.
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values', 'variant_id', 'attribute_value_id');
    }

    /**
     * Get the images for the variant.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id')->orderBy('position');
    }

    /**
     * Get the effective price (variant price or parent product price).
     */
    public function getEffectivePrice(): int
    {
        return $this->price ?? $this->product->getCurrentPrice();
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
     * Get the price in reais (BRL).
     */
    protected function priceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->price !== null ? $this->price / 100 : null,
        );
    }

    /**
     * Check if the variant is in stock.
     */
    public function isInStock(): bool
    {
        $product = $this->product;

        if (!$product->manage_stock) {
            return true;
        }

        if ($product->allow_backorders) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Get the primary image for the variant (or fallback to product image).
     */
    public function getPrimaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first()
            ?? $this->product->primaryImage();
    }

    /**
     * Get the variant name based on attribute values.
     */
    public function getName(): string
    {
        $values = $this->attributeValues->pluck('value')->toArray();

        if (empty($values)) {
            return $this->product->name;
        }

        return $this->product->name . ' - ' . implode(' / ', $values);
    }

    /**
     * Get the variant description based on attribute values.
     */
    public function getAttributeDescription(): string
    {
        return $this->attributeValues
            ->map(fn (AttributeValue $value) => $value->attribute->name . ': ' . $value->value)
            ->implode(', ');
    }
}
