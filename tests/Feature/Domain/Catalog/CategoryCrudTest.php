<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('CategoryController@index', function () {
    it('requires authentication', function () {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays categories list', function () {
        $category = Category::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewIs('admin.categories.index')
            ->assertViewHas('categories');
    });
});

describe('CategoryController@create', function () {
    it('requires authentication', function () {
        $this->get(route('admin.categories.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays create form', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertViewIs('admin.categories.create')
            ->assertViewHas('parentCategories');
    });

    it('filters parent categories by max depth', function () {
        // Create a category at depth 2 (cannot have children at depth 3)
        $level1 = Category::factory()->create();
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertViewHas('parentCategories', function ($categories) use ($level1, $level2) {
                // Level 1 should be available (depth 0)
                // Level 2 should be available (depth 1)
                // Both can have children since MAX_DEPTH is 3
                return $categories->contains('id', $level1->id)
                    && $categories->contains('id', $level2->id);
            });
    });
});

describe('CategoryController@store', function () {
    it('requires authentication', function () {
        $this->post(route('admin.categories.store'), [])
            ->assertRedirect(route('admin.login'));
    });

    it('creates a category with valid data', function () {
        $data = [
            'name'        => 'Test Category',
            'slug'        => 'test-category',
            'description' => 'Test description',
            'is_active'   => true,
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), $data)
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    });

    it('creates a category with parent', function () {
        $parent = Category::factory()->create();

        $data = [
            'name'      => 'Child Category',
            'parent_id' => $parent->id,
            'is_active' => true,
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), $data)
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name'      => 'Child Category',
            'parent_id' => $parent->id,
        ]);
    });

    it('uploads category image', function () {
        Storage::fake('public');

        $data = [
            'name'      => 'Category with Image',
            'image'     => UploadedFile::fake()->image('category.jpg'),
            'is_active' => true,
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), $data)
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'Category with Image')->first();
        expect($category->image)->not->toBeNull();
        Storage::disk('public')->assertExists($category->image);
    });

    it('validates required name', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [])
            ->assertSessionHasErrors('name');
    });

    it('validates unique slug', function () {
        Category::factory()->create(['slug' => 'existing-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'New Category',
                'slug' => 'existing-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates parent exists', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'      => 'New Category',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('validates max depth', function () {
        $level1 = Category::factory()->create();
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'      => 'Level 4 Category',
                'parent_id' => $level3->id,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('validates meta title max length', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'       => 'Test Category',
                'meta_title' => str_repeat('a', 61),
            ])
            ->assertSessionHasErrors('meta_title');
    });

    it('validates meta description max length', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'             => 'Test Category',
                'meta_description' => str_repeat('a', 161),
            ])
            ->assertSessionHasErrors('meta_description');
    });

    it('validates image file type', function () {
        Storage::fake('public');

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'  => 'Test Category',
                'image' => UploadedFile::fake()->create('document.pdf', 100),
            ])
            ->assertSessionHasErrors('image');
    });

    it('validates image max size', function () {
        Storage::fake('public');

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.store'), [
                'name'  => 'Test Category',
                'image' => UploadedFile::fake()->image('large.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('image');
    });
});

describe('CategoryController@edit', function () {
    it('requires authentication', function () {
        $category = Category::factory()->create();

        $this->get(route('admin.categories.edit', $category))
            ->assertRedirect(route('admin.login'));
    });

    it('displays edit form', function () {
        $category = Category::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertViewIs('admin.categories.edit')
            ->assertViewHas('category')
            ->assertViewHas('parentCategories');
    });

    it('excludes self from parent options', function () {
        $category = Category::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.categories.edit', $category))
            ->assertViewHas('parentCategories', function ($categories) use ($category) {
                return !$categories->contains('id', $category->id);
            });
    });
});

describe('CategoryController@update', function () {
    it('requires authentication', function () {
        $category = Category::factory()->create();

        $this->put(route('admin.categories.update', $category), [])
            ->assertRedirect(route('admin.login'));
    });

    it('updates a category', function () {
        $category = Category::factory()->create(['name' => 'Old Name']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $category), [
                'name'      => 'New Name',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id'   => $category->id,
            'name' => 'New Name',
        ]);
    });

    it('allows same slug for same category', function () {
        $category = Category::factory()->create(['slug' => 'my-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $category), [
                'name'      => 'Updated Name',
                'slug'      => 'my-slug',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');
    });

    it('prevents duplicate slug from other category', function () {
        Category::factory()->create(['slug' => 'existing-slug']);
        $category = Category::factory()->create(['slug' => 'my-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Updated Name',
                'slug' => 'existing-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('prevents setting self as parent', function () {
        $category = Category::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $category), [
                'name'      => 'Updated Name',
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('prevents circular reference', function () {
        $parent = Category::factory()->create();
        $child  = Category::factory()->create(['parent_id' => $parent->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $parent), [
                'name'      => 'Updated Parent',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('changes parent category', function () {
        $oldParent = Category::factory()->create();
        $newParent = Category::factory()->create();
        $category  = Category::factory()->create(['parent_id' => $oldParent->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.categories.update', $category), [
                'name'      => $category->name,
                'parent_id' => $newParent->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id'        => $category->id,
            'parent_id' => $newParent->id,
        ]);
    });
});

describe('CategoryController@destroy', function () {
    it('requires authentication', function () {
        $category = Category::factory()->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.login'));
    });

    it('deletes a category without children or products', function () {
        $category = Category::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('prevents deleting category with children', function () {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    });

    it('prevents deleting category with products', function () {
        $category = Category::factory()->create();
        $product  = \App\Domain\Catalog\Models\Product::factory()->create();
        $category->products()->attach($product);

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    });
});

describe('CategoryController@reorder', function () {
    it('requires authentication', function () {
        $this->post(route('admin.categories.reorder'), [])
            ->assertRedirect(route('admin.login'));
    });

    it('reorders categories', function () {
        $cat1 = Category::factory()->create(['position' => 0]);
        $cat2 = Category::factory()->create(['position' => 1]);
        $cat3 = Category::factory()->create(['position' => 2]);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.reorder'), [
                'order' => [$cat3->id, $cat1->id, $cat2->id],
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        expect($cat3->fresh()->position)->toBe(0)
            ->and($cat1->fresh()->position)->toBe(1)
            ->and($cat2->fresh()->position)->toBe(2);
    });

    it('validates order is required', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.reorder'), [])
            ->assertSessionHasErrors('order');
    });

    it('validates order contains valid category ids', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.categories.reorder'), [
                'order' => [99999, 88888],
            ])
            ->assertSessionHasErrors('order.0');
    });
});
