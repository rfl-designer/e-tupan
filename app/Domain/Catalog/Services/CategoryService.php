<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

class CategoryService
{
    /**
     * Cache key for the category tree.
     */
    private const CACHE_KEY = 'catalog.categories.tree';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the full category tree with caching.
     *
     * @return Collection<int, Category>
     */
    public function getTree(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Category::query()
                ->root()
                ->with(['children' => function ($query) {
                    $query->orderBy('position')
                        ->with(['children' => function ($query) {
                            $query->orderBy('position');
                        }]);
                }])
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * Get the category tree for active categories only.
     *
     * @return Collection<int, Category>
     */
    public function getActiveTree(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.active', self::CACHE_TTL, function () {
            return Category::query()
                ->root()
                ->active()
                ->with(['children' => function ($query) {
                    $query->active()
                        ->orderBy('position')
                        ->with(['children' => function ($query) {
                            $query->active()->orderBy('position');
                        }]);
                }])
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \InvalidArgumentException
     */
    public function create(array $data): Category
    {
        $this->validateHierarchy($data['parent_id'] ?? null);

        return DB::transaction(function () use ($data) {
            // Set position if not provided
            if (!isset($data['position'])) {
                $data['position'] = $this->getNextPosition($data['parent_id'] ?? null);
            }

            $category = Category::create($data);

            $this->invalidateCache();

            return $category;
        });
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \InvalidArgumentException
     */
    public function update(Category $category, array $data): Category
    {
        // Validate hierarchy if parent is being changed
        if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
            $this->validateHierarchyForUpdate($category, $data['parent_id']);
        }

        return DB::transaction(function () use ($category, $data) {
            $category->update($data);

            $this->invalidateCache();

            return $category->fresh();
        });
    }

    /**
     * Delete a category.
     *
     * @throws \InvalidArgumentException
     */
    public function delete(Category $category): bool
    {
        // Check if category has children
        if ($category->children()->exists()) {
            throw new \InvalidArgumentException(
                'Não é possível excluir uma categoria que possui subcategorias.',
            );
        }

        // Check if category has products
        if ($category->products()->exists()) {
            throw new \InvalidArgumentException(
                'Não é possível excluir uma categoria que possui produtos associados.',
            );
        }

        return DB::transaction(function () use ($category) {
            $result = $category->delete();

            $this->invalidateCache();

            return $result;
        });
    }

    /**
     * Reorder categories.
     *
     * @param  array<int, int>  $order  Array of category IDs in the desired order
     */
    public function reorder(array $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order as $position => $categoryId) {
                Category::where('id', $categoryId)->update(['position' => $position]);
            }

            $this->invalidateCache();
        });
    }

    /**
     * Move a category to a new parent.
     *
     * @throws \InvalidArgumentException
     */
    public function move(Category $category, ?int $newParentId): Category
    {
        $this->validateHierarchyForUpdate($category, $newParentId);

        return DB::transaction(function () use ($category, $newParentId) {
            $category->update([
                'parent_id' => $newParentId,
                'position'  => $this->getNextPosition($newParentId),
            ]);

            $this->invalidateCache();

            return $category->fresh();
        });
    }

    /**
     * Get all categories as a flat list for select dropdowns.
     *
     * @return Collection<int, array{id: int, name: string, depth: int}>
     */
    public function getFlatList(): Collection
    {
        $categories = $this->getTree();

        return $this->flattenTree($categories);
    }

    /**
     * Flatten the category tree into a list with depth information.
     *
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, array{id: int, name: string, depth: int}>
     */
    private function flattenTree(Collection $categories, int $depth = 0): Collection
    {
        $result = collect();

        foreach ($categories as $category) {
            $result->push([
                'id'    => $category->id,
                'name'  => str_repeat('— ', $depth) . $category->name,
                'depth' => $depth,
            ]);

            if ($category->children->isNotEmpty()) {
                $result = $result->merge($this->flattenTree($category->children, $depth + 1));
            }
        }

        return $result;
    }

    /**
     * Validate that the parent category allows children (max depth check).
     *
     * @throws \InvalidArgumentException
     */
    private function validateHierarchy(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Category::find($parentId);

        if ($parent === null) {
            throw new \InvalidArgumentException('Categoria pai não encontrada.');
        }

        if (!$parent->canHaveChildren()) {
            throw new \InvalidArgumentException(
                'Não é possível criar subcategorias além do nível ' . Category::MAX_DEPTH . '.',
            );
        }
    }

    /**
     * Validate hierarchy for an update operation.
     *
     * @throws \InvalidArgumentException
     */
    private function validateHierarchyForUpdate(Category $category, ?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        $newParent = Category::find($newParentId);

        if ($newParent === null) {
            throw new \InvalidArgumentException('Categoria pai não encontrada.');
        }

        if (!$category->canBeParent($newParent)) {
            throw new \InvalidArgumentException(
                'Não é possível mover a categoria para este destino. Verifique se não está criando uma referência circular ou excedendo o nível máximo de hierarquia.',
            );
        }
    }

    /**
     * Get the next position for a category at a given parent level.
     */
    private function getNextPosition(?int $parentId): int
    {
        return Category::where('parent_id', $parentId)->max('position') + 1;
    }

    /**
     * Invalidate the category cache.
     */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '.active');
    }
}
