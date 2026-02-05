<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Traits\HasSlug;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;
    use HasSlug;

    public const MAX_DEPTH = 3;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'meta_title',
        'meta_description',
        'position',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position'  => 'integer',
        ];
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products in this category.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories (no parent).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the current depth level of this category.
     */
    public function getDepth(): int
    {
        $depth    = 1;
        $category = $this;

        while ($category->parent_id !== null) {
            $depth++;
            $category = $category->parent;

            if ($category === null) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Check if this category can have children (not at max depth).
     */
    public function canHaveChildren(): bool
    {
        return $this->getDepth() < self::MAX_DEPTH;
    }

    /**
     * Check if a category can be set as parent of this category.
     * Prevents circular references and max depth violations.
     */
    public function canBeParent(?Category $potentialParent): bool
    {
        if ($potentialParent === null) {
            return true;
        }

        // Cannot be its own parent
        if ($this->exists && $potentialParent->id === $this->id) {
            return false;
        }

        // Check if potential parent is a descendant of this category
        if ($this->exists && $this->isAncestorOf($potentialParent)) {
            return false;
        }

        // Check max depth
        return $potentialParent->getDepth() < self::MAX_DEPTH;
    }

    /**
     * Check if this category is an ancestor of the given category.
     */
    public function isAncestorOf(Category $category): bool
    {
        $current = $category->parent;

        while ($current !== null) {
            if ($current->id === $this->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Get all ancestors of this category.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $category  = $this->parent;

        while ($category !== null) {
            $ancestors->prepend($category);
            $category = $category->parent;
        }

        return $ancestors;
    }

    /**
     * Get the breadcrumb path for this category.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function getBreadcrumb(): \Illuminate\Support\Collection
    {
        return $this->getAncestors()->push($this);
    }
}
