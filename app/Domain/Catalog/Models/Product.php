<?php

declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\{ProductStatus, ProductType};
use App\Domain\Catalog\Traits\HasSlug;
use App\Domain\Inventory\Contracts\StockableInterface;
use App\Domain\Inventory\Traits\HasStock;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Support\Carbon;

class Product extends Model implements StockableInterface
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use HasSlug;
    use HasStock;
    use SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'type',
        'status',
        'price',
        'sale_price',
        'sale_start_at',
        'sale_end_at',
        'cost',
        'sku',
        'stock_quantity',
        'manage_stock',
        'allow_backorders',
        'low_stock_threshold',
        'low_stock_notified_at',
        'notify_low_stock',
        'weight',
        'length',
        'width',
        'height',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'                  => ProductType::class,
            'status'                => ProductStatus::class,
            'sale_start_at'         => 'datetime',
            'sale_end_at'           => 'datetime',
            'manage_stock'          => 'boolean',
            'allow_backorders'      => 'boolean',
            'low_stock_threshold'   => 'integer',
            'low_stock_notified_at' => 'datetime',
            'notify_low_stock'      => 'boolean',
            'price'                 => 'integer',
            'sale_price'            => 'integer',
            'cost'                  => 'integer',
            'stock_quantity'        => 'integer',
            'weight'                => 'decimal:3',
            'length'                => 'decimal:2',
            'width'                 => 'decimal:2',
            'height'                => 'decimal:2',
        ];
    }

    /**
     * Get the categories for the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the attributes for the product.
     */
    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\Catalog\Models\Attribute::class, 'product_attributes')
            ->withPivot(['attribute_value_id', 'used_for_variations']);
    }

    /**
     * Get the attribute values for the product.
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attributes')
            ->withPivot(['attribute_id', 'used_for_variations']);
    }

    /**
     * Get the tags for the product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    /**
     * Get the order items for the product.
     *
     * @return HasMany<\App\Domain\Checkout\Models\OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(\App\Domain\Checkout\Models\OrderItem::class);
    }

    /**
     * Get the user who created the product.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the product.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    /**
     * Scope a query to only include products below their stock threshold.
     * Returns products where stock is above 0 but at or below the threshold.
     */
    public function scopeBelowThreshold(Builder $query): Builder
    {
        $defaultThreshold = config('inventory.default_low_stock_threshold', 5);

        return $query->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where(function (Builder $q) {
                // Products with custom threshold
                $q->whereNotNull('low_stock_threshold')
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            })
            ->orWhere(function (Builder $q) use ($defaultThreshold) {
                // Products using global default threshold
                $q->where('manage_stock', true)
                    ->where('stock_quantity', '>', 0)
                    ->whereNull('low_stock_threshold')
                    ->where('stock_quantity', '<=', $defaultThreshold);
            });
    }

    /**
     * Scope a query to only include products on sale.
     */
    public function scopeOnSale(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->whereNotNull('sale_price')
            ->where('sale_price', '>', 0)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('sale_start_at')
                    ->orWhere('sale_start_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('sale_end_at')
                    ->orWhere('sale_end_at', '>=', $now);
            });
    }

    /**
     * Check if the product is currently on sale.
     */
    public function isOnSale(): bool
    {
        if ($this->sale_price === null || $this->sale_price <= 0) {
            return false;
        }

        $now = Carbon::now();

        if ($this->sale_start_at !== null && $this->sale_start_at > $now) {
            return false;
        }

        if ($this->sale_end_at !== null && $this->sale_end_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * Get the current effective price (sale price if on sale, otherwise regular price).
     */
    public function getCurrentPrice(): int
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    /**
     * Get the price in reais (BRL).
     */
    protected function priceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->price / 100,
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
     * Get the cost in reais (BRL).
     */
    protected function costInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->cost !== null ? $this->cost / 100 : null,
        );
    }

    /**
     * Get the current price in reais (BRL).
     */
    protected function currentPriceInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->getCurrentPrice() / 100,
        );
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        if ($this->allow_backorders) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is a simple product.
     */
    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    /**
     * Check if the product is a variable product.
     */
    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }

    /**
     * Get the discount percentage if on sale.
     */
    public function getDiscountPercentage(): ?int
    {
        if (!$this->isOnSale() || $this->price <= 0) {
            return null;
        }

        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }
}
