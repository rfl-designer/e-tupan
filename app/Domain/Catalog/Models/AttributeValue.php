<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use Database\Factories\AttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

class AttributeValue extends Model
{
    /** @use HasFactory<AttributeValueFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttributeValueFactory
    {
        return AttributeValueFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attribute_id',
        'value',
        'color_hex',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Get the attribute that owns this value.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Get the variants that use this attribute value.
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_attribute_values', 'attribute_value_id', 'variant_id');
    }

    /**
     * Get the display value (with color swatch if applicable).
     */
    public function getDisplayValue(): string
    {
        return $this->value;
    }

    /**
     * Check if this value has a color hex code.
     */
    public function hasColor(): bool
    {
        return $this->color_hex !== null && $this->color_hex !== '';
    }
}
