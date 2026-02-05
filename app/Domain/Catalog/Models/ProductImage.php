<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductImageFactory
    {
        return ProductImageFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'path',
        'alt_text',
        'position',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position'   => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the image.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to only include images without a variant (product-level images).
     */
    public function scopeProductLevel(Builder $query): Builder
    {
        return $query->whereNull('variant_id');
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrl(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Get the URL for a specific size variant.
     */
    public function getUrlForSize(string $size = 'medium'): string
    {
        $pathInfo  = pathinfo($this->path);
        $sizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $size . '.' . $pathInfo['extension'];

        return asset('storage/' . $sizedPath);
    }
}
