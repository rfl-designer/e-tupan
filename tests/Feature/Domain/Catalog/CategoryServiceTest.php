<?php declare(strict_types = 1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Services\CategoryService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = new CategoryService();
});

describe('getTree', function () {
    it('returns empty collection when no categories exist', function () {
        $tree = $this->service->getTree();

        expect($tree)->toBeEmpty();
    });

    it('returns root categories with children', function () {
        $root  = Category::factory()->create(['position' => 0]);
        $child = Category::factory()->create(['parent_id' => $root->id, 'position' => 0]);

        $tree = $this->service->getTree();

        expect($tree)->toHaveCount(1)
            ->and($tree->first()->id)->toBe($root->id)
            ->and($tree->first()->children)->toHaveCount(1)
            ->and($tree->first()->children->first()->id)->toBe($child->id);
    });

    it('caches the category tree', function () {
        Category::factory()->create();

        // First call should cache
        $this->service->getTree();

        // Verify cache was set
        expect(Cache::has('catalog.categories.tree'))->toBeTrue();
    });
});

describe('getActiveTree', function () {
    it('only returns active categories', function () {
        Category::factory()->create(['is_active' => true, 'position' => 0]);
        Category::factory()->create(['is_active' => false, 'position' => 1]);

        $tree = $this->service->getActiveTree();

        expect($tree)->toHaveCount(1);
    });
});

describe('create', function () {
    it('creates a category', function () {
        $data = [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'is_active' => true,
        ];

        $category = $this->service->create($data);

        expect($category)->toBeInstanceOf(Category::class)
            ->and($category->name)->toBe('Test Category')
            ->and($category->slug)->toBe('test-category');
    });

    it('sets position automatically', function () {
        Category::factory()->create(['parent_id' => null, 'position' => 5]);

        $category = $this->service->create([
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        expect($category->position)->toBe(6);
    });

    it('validates max depth', function () {
        $level1 = Category::factory()->create();
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);

        expect(fn () => $this->service->create([
            'name'      => 'Level 4',
            'slug'      => 'level-4',
            'parent_id' => $level3->id,
        ]))->toThrow(\InvalidArgumentException::class);
    });

    it('invalidates cache after creation', function () {
        Cache::put('catalog.categories.tree', collect(['cached']));

        $this->service->create([
            'name' => 'Test',
            'slug' => 'test',
        ]);

        expect(Cache::has('catalog.categories.tree'))->toBeFalse();
    });
});

describe('update', function () {
    it('updates a category', function () {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($category, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
    });

    it('validates hierarchy when changing parent', function () {
        $parent = Category::factory()->create();
        $child  = Category::factory()->create(['parent_id' => $parent->id]);

        // Try to make parent a child of its own child (circular reference)
        expect(fn () => $this->service->update($parent, ['parent_id' => $child->id]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('invalidates cache after update', function () {
        $category = Category::factory()->create();
        Cache::put('catalog.categories.tree', collect(['cached']));

        $this->service->update($category, ['name' => 'Updated']);

        expect(Cache::has('catalog.categories.tree'))->toBeFalse();
    });
});

describe('delete', function () {
    it('deletes a category without children or products', function () {
        $category = Category::factory()->create();

        $result = $this->service->delete($category);

        expect($result)->toBeTrue()
            ->and(Category::find($category->id))->toBeNull();
    });

    it('throws exception when category has children', function () {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        expect(fn () => $this->service->delete($parent))
            ->toThrow(\InvalidArgumentException::class, 'subcategorias');
    });

    it('throws exception when category has products', function () {
        $category = Category::factory()->create();
        $product  = \App\Domain\Catalog\Models\Product::factory()->create();
        $category->products()->attach($product);

        expect(fn () => $this->service->delete($category))
            ->toThrow(\InvalidArgumentException::class, 'produtos');
    });

    it('invalidates cache after deletion', function () {
        $category = Category::factory()->create();
        Cache::put('catalog.categories.tree', collect(['cached']));

        $this->service->delete($category);

        expect(Cache::has('catalog.categories.tree'))->toBeFalse();
    });
});

describe('reorder', function () {
    it('reorders categories', function () {
        $cat1 = Category::factory()->create(['position' => 0]);
        $cat2 = Category::factory()->create(['position' => 1]);
        $cat3 = Category::factory()->create(['position' => 2]);

        $this->service->reorder([$cat3->id, $cat1->id, $cat2->id]);

        expect($cat3->fresh()->position)->toBe(0)
            ->and($cat1->fresh()->position)->toBe(1)
            ->and($cat2->fresh()->position)->toBe(2);
    });
});

describe('move', function () {
    it('moves a category to a new parent', function () {
        $oldParent = Category::factory()->create();
        $newParent = Category::factory()->create();
        $child     = Category::factory()->create(['parent_id' => $oldParent->id]);

        $moved = $this->service->move($child, $newParent->id);

        expect($moved->parent_id)->toBe($newParent->id);
    });

    it('moves a category to root', function () {
        $parent = Category::factory()->create();
        $child  = Category::factory()->create(['parent_id' => $parent->id]);

        $moved = $this->service->move($child, null);

        expect($moved->parent_id)->toBeNull();
    });
});

describe('getFlatList', function () {
    it('returns a flat list with depth information', function () {
        $root  = Category::factory()->create(['name' => 'Root', 'position' => 0]);
        $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $root->id, 'position' => 0]);

        $list = $this->service->getFlatList();

        expect($list)->toHaveCount(2)
            ->and($list[0]['name'])->toBe('Root')
            ->and($list[0]['depth'])->toBe(0)
            ->and($list[1]['name'])->toBe('— Child')
            ->and($list[1]['depth'])->toBe(1);
    });
});
