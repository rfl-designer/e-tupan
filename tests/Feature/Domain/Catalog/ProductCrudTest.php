<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product, Tag};

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('ProductController@index', function () {
    it('requires authentication', function () {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays products list', function () {
        Product::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertViewIs('admin.products.index');
    });
});

describe('ProductController@create', function () {
    it('requires authentication', function () {
        $this->get(route('admin.products.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays create form', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertViewIs('admin.products.create')
            ->assertViewHas('categories')
            ->assertViewHas('tags');
    });
});

describe('ProductController@store', function () {
    it('requires authentication', function () {
        $this->post(route('admin.products.store'), [])
            ->assertRedirect(route('admin.login'));
    });

    it('creates a simple product with valid data', function () {
        $category = Category::factory()->create();
        $tag      = Tag::factory()->create();

        $data = [
            'name'             => 'Test Product',
            'slug'             => 'test-product',
            'type'             => 'simple',
            'status'           => 'draft',
            'price'            => 99.90,
            'stock_quantity'   => 10,
            'manage_stock'     => true,
            'allow_backorders' => false,
            'categories'       => [$category->id],
            'tags'             => [$tag->id],
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name'  => 'Test Product',
            'slug'  => 'test-product',
            'price' => 9990, // in cents
        ]);

        $product = Product::where('slug', 'test-product')->first();
        expect($product->categories)->toHaveCount(1)
            ->and($product->tags)->toHaveCount(1);
    });

    it('creates a product with sale price', function () {
        $data = [
            'name'           => 'Sale Product',
            'type'           => 'simple',
            'status'         => 'active',
            'price'          => 100.00,
            'sale_price'     => 80.00,
            'stock_quantity' => 5,
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name'       => 'Sale Product',
            'price'      => 10000,
            'sale_price' => 8000,
        ]);
    });

    it('validates required name', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'type'           => 'simple',
                'status'         => 'draft',
                'price'          => 10.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates required price', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'Test Product',
                'type'           => 'simple',
                'status'         => 'draft',
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('price');
    });

    it('validates unique slug', function () {
        Product::factory()->create(['slug' => 'existing-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'New Product',
                'slug'           => 'existing-slug',
                'type'           => 'simple',
                'status'         => 'draft',
                'price'          => 10.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates unique sku', function () {
        Product::factory()->create(['sku' => 'EXIST-001']);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'New Product',
                'sku'            => 'EXIST-001',
                'type'           => 'simple',
                'status'         => 'draft',
                'price'          => 10.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('sku');
    });

    it('validates sale price is less than price', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'Test Product',
                'type'           => 'simple',
                'status'         => 'draft',
                'price'          => 50.00,
                'sale_price'     => 60.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('sale_price');
    });

    it('validates product type enum', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'Test Product',
                'type'           => 'invalid',
                'status'         => 'draft',
                'price'          => 10.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('type');
    });

    it('validates product status enum', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.store'), [
                'name'           => 'Test Product',
                'type'           => 'simple',
                'status'         => 'invalid',
                'price'          => 10.00,
                'stock_quantity' => 0,
            ])
            ->assertSessionHasErrors('status');
    });
});

describe('ProductController@edit', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();

        $this->get(route('admin.products.edit', $product))
            ->assertRedirect(route('admin.login'));
    });

    it('displays edit form', function () {
        $product = Product::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertViewIs('admin.products.edit')
            ->assertViewHas('product')
            ->assertViewHas('categories')
            ->assertViewHas('tags');
    });

    it('loads product with relationships', function () {
        $category = Category::factory()->create();
        $tag      = Tag::factory()->create();
        $product  = Product::factory()->create();
        $product->categories()->attach($category);
        $product->tags()->attach($tag);

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertViewHas('product', function ($viewProduct) use ($category, $tag) {
                return $viewProduct->categories->contains($category)
                    && $viewProduct->tags->contains($tag);
            });
    });
});

describe('ProductController@update', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();

        $this->put(route('admin.products.update', $product), [])
            ->assertRedirect(route('admin.login'));
    });

    it('updates a product', function () {
        $product = Product::factory()->create(['name' => 'Old Name', 'price' => 5000]);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.products.update', $product), [
                'name'           => 'New Name',
                'type'           => 'simple',
                'status'         => 'active',
                'price'          => 75.00,
                'stock_quantity' => 20,
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'New Name',
            'price' => 7500,
        ]);
    });

    it('allows same slug for same product', function () {
        $product = Product::factory()->create(['slug' => 'my-product']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.products.update', $product), [
                'name'           => 'Updated Name',
                'slug'           => 'my-product',
                'type'           => 'simple',
                'status'         => 'active',
                'price'          => 50.00,
                'stock_quantity' => 10,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    });

    it('prevents duplicate slug from other product', function () {
        Product::factory()->create(['slug' => 'existing-slug']);
        $product = Product::factory()->create(['slug' => 'my-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.products.update', $product), [
                'name'           => 'Updated Name',
                'slug'           => 'existing-slug',
                'type'           => 'simple',
                'status'         => 'active',
                'price'          => 50.00,
                'stock_quantity' => 10,
            ])
            ->assertSessionHasErrors('slug');
    });

    it('updates product categories', function () {
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();
        $product     = Product::factory()->create();
        $product->categories()->attach($oldCategory);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.products.update', $product), [
                'name'           => $product->name,
                'type'           => 'simple',
                'status'         => 'active',
                'price'          => 50.00,
                'stock_quantity' => 10,
                'categories'     => [$newCategory->id],
            ])
            ->assertRedirect();

        $product->refresh();
        expect($product->categories)->toHaveCount(1)
            ->and($product->categories->first()->id)->toBe($newCategory->id);
    });
});

describe('ProductController@destroy', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.login'));
    });

    it('soft deletes a product', function () {
        $product = Product::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    });
});

describe('ProductController@trash', function () {
    it('requires authentication', function () {
        $this->get(route('admin.products.trash'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays trash page', function () {
        Product::factory()->create()->delete();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.trash'))
            ->assertOk()
            ->assertViewIs('admin.products.trash');
    });
});

describe('ProductController@restore', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();
        $product->delete();

        $this->post(route('admin.products.restore', $product))
            ->assertRedirect(route('admin.login'));
    });

    it('restores a soft-deleted product', function () {
        $product = Product::factory()->create();
        $product->delete();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.restore', $product))
            ->assertRedirect(route('admin.products.trash'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    });
});

describe('ProductController@forceDelete', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();
        $product->delete();

        $this->delete(route('admin.products.force-delete', $product))
            ->assertRedirect(route('admin.login'));
    });

    it('permanently deletes a product', function () {
        $product = Product::factory()->create();
        $product->delete();

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.products.force-delete', $product))
            ->assertRedirect(route('admin.products.trash'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });
});

describe('ProductController@duplicate', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();

        $this->post(route('admin.products.duplicate', $product))
            ->assertRedirect(route('admin.login'));
    });

    it('duplicates a product', function () {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['name' => 'Original Product']);
        $product->categories()->attach($category);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.duplicate', $product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name'   => 'Original Product (Cópia)',
            'status' => 'draft',
        ]);

        $duplicated = Product::where('name', 'Original Product (Cópia)')->first();
        expect($duplicated->categories)->toHaveCount(1);
    });
});

describe('ProductController@bulkAction', function () {
    it('requires authentication', function () {
        $this->post(route('admin.products.bulk-action'), [])
            ->assertRedirect(route('admin.login'));
    });

    it('activates multiple products', function () {
        $products = Product::factory()->count(3)->inactive()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.bulk-action'), [
                'action'   => 'activate',
                'products' => $products->pluck('id')->toArray(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($products as $product) {
            expect($product->fresh()->status)->toBe(ProductStatus::Active);
        }
    });

    it('deactivates multiple products', function () {
        $products = Product::factory()->count(3)->active()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.bulk-action'), [
                'action'   => 'deactivate',
                'products' => $products->pluck('id')->toArray(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($products as $product) {
            expect($product->fresh()->status)->toBe(ProductStatus::Inactive);
        }
    });

    it('deletes multiple products', function () {
        $products = Product::factory()->count(3)->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.bulk-action'), [
                'action'   => 'delete',
                'products' => $products->pluck('id')->toArray(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertSoftDeleted('products', ['id' => $product->id]);
        }
    });

    it('validates action is required', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.bulk-action'), [
                'products' => [1, 2, 3],
            ])
            ->assertSessionHasErrors('action');
    });

    it('validates products are required', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.products.bulk-action'), [
                'action' => 'activate',
            ])
            ->assertSessionHasErrors('products');
    });
});
