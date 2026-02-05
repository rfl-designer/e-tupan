<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\AttributeType;
use App\Domain\Catalog\Traits\HasSlug;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;
    use HasSlug;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
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
            'type'     => AttributeType::class,
            'position' => 'integer',
        ];
    }

    /**
     * Get the values for the attribute.
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    /**
     * Get the products that use this attribute.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes')
            ->withPivot(['attribute_value_id', 'used_for_variations']);
    }

    /**
     * Check if this attribute is a color type.
     */
    public function isColor(): bool
    {
        return $this->type === AttributeType::Color;
    }

    /**
     * Check if this attribute is a select type.
     */
    public function isSelect(): bool
    {
        return $this->type === AttributeType::Select;
    }

    /**
     * Check if this attribute is a text type.
     */
    public function isText(): bool
    {
        return $this->type === AttributeType::Text;
    }
}
