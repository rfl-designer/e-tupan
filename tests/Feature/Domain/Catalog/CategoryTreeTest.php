<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Livewire\Admin\CategoryTree;
use App\Domain\Catalog\Models\{Category, Product};
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('CategoryTree Component', function () {
    it('renders the component', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(CategoryTree::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.category-tree');
    });

    it('displays categories', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create(['name' => 'Test Category']);

        Livewire::test(CategoryTree::class)
            ->assertSee('Test Category');
    });

    it('displays nested categories', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $parent = Category::factory()->create(['name' => 'Parent Category']);
        $child  = Category::factory()->create([
            'name'      => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        Livewire::test(CategoryTree::class)
            ->assertSee('Parent Category')
            ->assertSee('Child Category');
    });

    it('shows empty state when no categories exist', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(CategoryTree::class)
            ->assertSee('Nenhuma categoria encontrada');
    });

    it('expands all categories by default', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $parent = Category::factory()->create();
        $child  = Category::factory()->create(['parent_id' => $parent->id]);

        $component = Livewire::test(CategoryTree::class);

        expect($component->get('expandedCategories'))
            ->toContain($parent->id)
            ->toContain($child->id);
    });
});

describe('CategoryTree Toggle Expand', function () {
    it('toggles category expansion', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create();

        $component = Livewire::test(CategoryTree::class);

        // Initially expanded
        expect($component->get('expandedCategories'))->toContain($category->id);

        // Toggle to collapse
        $component->call('toggleExpand', $category->id);
        expect($component->get('expandedCategories'))->not->toContain($category->id);

        // Toggle to expand again
        $component->call('toggleExpand', $category->id);
        expect($component->get('expandedCategories'))->toContain($category->id);
    });

    it('expands all categories', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $component = Livewire::test(CategoryTree::class)
            ->call('collapseAll')
            ->call('expandAll');

        expect($component->get('expandedCategories'))
            ->toContain($cat1->id)
            ->toContain($cat2->id);
    });

    it('collapses all categories', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Category::factory()->count(3)->create();

        $component = Livewire::test(CategoryTree::class)
            ->call('collapseAll');

        expect($component->get('expandedCategories'))->toBeEmpty();
    });
});

describe('CategoryTree Delete', function () {
    it('deletes a category without children or products', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create();

        Livewire::test(CategoryTree::class)
            ->call('delete', $category->id)
            ->assertDispatched('notify', type: 'success', message: 'Categoria excluída com sucesso!');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('prevents deleting category with children', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        Livewire::test(CategoryTree::class)
            ->call('delete', $parent->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    });

    it('prevents deleting category with products', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create();
        $product  = Product::factory()->create();
        $category->products()->attach($product);

        Livewire::test(CategoryTree::class)
            ->call('delete', $category->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    });

    it('handles deleting non-existent category', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(CategoryTree::class)
            ->call('delete', 99999)
            ->assertDispatched('notify', type: 'error', message: 'Categoria não encontrada.');
    });
});

describe('CategoryTree Reorder', function () {
    it('reorders categories at root level', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $cat1 = Category::factory()->create(['position' => 0]);
        $cat2 = Category::factory()->create(['position' => 1]);
        $cat3 = Category::factory()->create(['position' => 2]);

        $newOrder = [
            ['id' => $cat3->id, 'children' => []],
            ['id' => $cat1->id, 'children' => []],
            ['id' => $cat2->id, 'children' => []],
        ];

        Livewire::test(CategoryTree::class)
            ->call('reorder', $newOrder, null)
            ->assertDispatched('notify', type: 'success');

        expect($cat3->fresh()->position)->toBe(0)
            ->and($cat1->fresh()->position)->toBe(1)
            ->and($cat2->fresh()->position)->toBe(2);
    });

    it('reorders nested categories', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $parent = Category::factory()->create(['position' => 0]);
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'position' => 0]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'position' => 1]);

        $newOrder = [
            [
                'id'       => $parent->id,
                'children' => [
                    ['id' => $child2->id, 'children' => []],
                    ['id' => $child1->id, 'children' => []],
                ],
            ],
        ];

        Livewire::test(CategoryTree::class)
            ->call('reorder', $newOrder, null);

        expect($child2->fresh()->position)->toBe(0)
            ->and($child1->fresh()->position)->toBe(1);
    });

    it('moves category to different parent', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $parent1 = Category::factory()->create(['position' => 0]);
        $parent2 = Category::factory()->create(['position' => 1]);
        $child   = Category::factory()->create(['parent_id' => $parent1->id, 'position' => 0]);

        $newOrder = [
            ['id' => $parent1->id, 'children' => []],
            [
                'id'       => $parent2->id,
                'children' => [
                    ['id' => $child->id, 'children' => []],
                ],
            ],
        ];

        Livewire::test(CategoryTree::class)
            ->call('reorder', $newOrder, null);

        expect($child->fresh()->parent_id)->toBe($parent2->id);
    });

    it('prevents moving category beyond max depth', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $level1         = Category::factory()->create();
        $level2         = Category::factory()->create(['parent_id' => $level1->id]);
        $level3         = Category::factory()->create(['parent_id' => $level2->id]);
        $categoryToMove = Category::factory()->create();

        // Try to move categoryToMove under level3 (would be level 4)
        $newOrder = [
            [
                'id'       => $level1->id,
                'children' => [
                    [
                        'id'       => $level2->id,
                        'children' => [
                            [
                                'id'       => $level3->id,
                                'children' => [
                                    ['id' => $categoryToMove->id, 'children' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Livewire::test(CategoryTree::class)
            ->call('reorder', $newOrder, null)
            ->assertDispatched('notify', type: 'error');

        // Category should remain at root level
        expect($categoryToMove->fresh()->parent_id)->toBeNull();
    });
});

describe('CategoryTree Display', function () {
    it('shows category status badge', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $activeCategory   = Category::factory()->create(['is_active' => true, 'name' => 'Active Cat']);
        $inactiveCategory = Category::factory()->create(['is_active' => false, 'name' => 'Inactive Cat']);

        Livewire::test(CategoryTree::class)
            ->assertSee('Active Cat')
            ->assertSee('Inactive Cat')
            ->assertSee('Ativa')
            ->assertSee('Inativa');
    });

    it('shows products count', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create();
        $category->products()->attach($products->pluck('id'));

        Livewire::test(CategoryTree::class)
            ->assertSee('3');
    });

    it('shows edit and delete buttons', function () {
        actingAsAdminWith2FA($this, $this->admin);

        $category = Category::factory()->create();

        Livewire::test(CategoryTree::class)
            ->assertSeeHtml(route('admin.categories.edit', $category))
            ->assertSeeHtml('wire:click="delete(' . $category->id . ')"');
    });
});
