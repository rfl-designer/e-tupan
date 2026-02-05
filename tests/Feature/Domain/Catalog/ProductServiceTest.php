<?php declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product, Tag};
use App\Domain\Catalog\Services\{ImageService, ProductService};
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->imageService = Mockery::mock(ImageService::class);
    $this->service      = new ProductService($this->imageService);
});

describe('create', function () {
    it('creates a product', function () {
        $data = [
            'name'  => 'Test Product',
            'slug'  => 'test-product',
            'price' => 10000,
            'sku'   => 'TEST-001',
        ];

        $product = $this->service->create($data);

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->name)->toBe('Test Product')
            ->and($product->price)->toBe(10000);
    });

    it('sets created_by when authenticated', function () {
        $user = User::factory()->create();
        Auth::login($user);

        $product = $this->service->create([
            'name'  => 'Test Product',
            'slug'  => 'test-product',
            'price' => 10000,
            'sku'   => 'TEST-002',
        ]);

        expect($product->created_by)->toBe($user->id)
            ->and($product->updated_by)->toBe($user->id);
    });

    it('syncs categories', function () {
        $categories = Category::factory()->count(2)->create();

        $product = $this->service->create([
            'name'       => 'Test Product',
            'slug'       => 'test-product',
            'price'      => 10000,
            'sku'        => 'TEST-003',
            'categories' => $categories->pluck('id')->toArray(),
        ]);

        expect($product->categories)->toHaveCount(2);
    });

    it('syncs tags', function () {
        $tags = Tag::factory()->count(3)->create();

        $product = $this->service->create([
            'name'  => 'Test Product',
            'slug'  => 'test-product',
            'price' => 10000,
            'sku'   => 'TEST-004',
            'tags'  => $tags->pluck('id')->toArray(),
        ]);

        expect($product->tags)->toHaveCount(3);
    });
});

describe('update', function () {
    it('updates a product', function () {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($product, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
    });

    it('sets updated_by when authenticated', function () {
        $user = User::factory()->create();
        Auth::login($user);

        $product = Product::factory()->create();

        $updated = $this->service->update($product, ['name' => 'Updated']);

        expect($updated->updated_by)->toBe($user->id);
    });

    it('syncs categories when provided', function () {
        $product       = Product::factory()->create();
        $oldCategories = Category::factory()->count(2)->create();
        $product->categories()->sync($oldCategories->pluck('id'));

        $newCategories = Category::factory()->count(3)->create();

        $updated = $this->service->update($product, [
            'categories' => $newCategories->pluck('id')->toArray(),
        ]);

        expect($updated->categories)->toHaveCount(3);
    });

    it('does not change categories when not provided', function () {
        $product    = Product::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $product->categories()->sync($categories->pluck('id'));

        $updated = $this->service->update($product, ['name' => 'Updated']);

        expect($updated->categories)->toHaveCount(2);
    });
});

describe('delete', function () {
    it('soft deletes a product', function () {
        $product = Product::factory()->create();

        $result = $this->service->delete($product);

        expect($result)->toBeTrue()
            ->and(Product::find($product->id))->toBeNull()
            ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
    });
});

describe('restore', function () {
    it('restores a soft-deleted product', function () {
        $product = Product::factory()->create();
        $product->delete();

        $restored = $this->service->restore($product);

        expect($restored->deleted_at)->toBeNull()
            ->and(Product::find($product->id))->not->toBeNull();
    });
});

describe('forceDelete', function () {
    it('permanently deletes a product', function () {
        $product = Product::factory()->create();

        $this->imageService->shouldReceive('delete')->never();

        $result = $this->service->forceDelete($product);

        expect($result)->toBeTrue()
            ->and(Product::withTrashed()->find($product->id))->toBeNull();
    });

    it('deletes associated images from storage', function () {
        $product = Product::factory()->create();
        $product->images()->create([
            'path'     => 'products/large/test.webp',
            'position' => 0,
        ]);

        $this->imageService->shouldReceive('delete')
            ->once()
            ->with('products/large/test.webp');

        $this->service->forceDelete($product);
    });
});

describe('duplicate', function () {
    it('creates a copy of the product', function () {
        $product = Product::factory()->create([
            'name'   => 'Original Product',
            'status' => ProductStatus::Active,
        ]);

        $duplicate = $this->service->duplicate($product);

        expect($duplicate->id)->not->toBe($product->id)
            ->and($duplicate->name)->toBe('Original Product (Cópia)')
            ->and($duplicate->status)->toBe(ProductStatus::Draft);
    });

    it('copies categories and tags', function () {
        $product    = Product::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $tags       = Tag::factory()->count(2)->create();
        $product->categories()->sync($categories->pluck('id'));
        $product->tags()->sync($tags->pluck('id'));

        $duplicate = $this->service->duplicate($product);

        expect($duplicate->categories)->toHaveCount(2)
            ->and($duplicate->tags)->toHaveCount(2);
    });

    it('generates unique sku', function () {
        $product = Product::factory()->create(['sku' => 'ORIG-001']);

        $duplicate = $this->service->duplicate($product);

        expect($duplicate->sku)->toStartWith('ORIG-001-COPY-')
            ->and($duplicate->sku)->not->toBe($product->sku);
    });
});

describe('updateStock', function () {
    it('sets absolute stock quantity', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->service->updateStock($product, 50, true);

        expect($updated->stock_quantity)->toBe(50);
    });

    it('adjusts stock quantity relatively', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->service->updateStock($product, -3, false);

        expect($updated->stock_quantity)->toBe(7);
    });
});

describe('bulkUpdateStatus', function () {
    it('updates status for multiple products', function () {
        $products = Product::factory()->count(3)->create(['status' => ProductStatus::Draft]);

        $count = $this->service->bulkUpdateStatus(
            $products->pluck('id')->toArray(),
            ProductStatus::Active->value,
        );

        expect($count)->toBe(3);

        foreach ($products as $product) {
            expect($product->fresh()->status)->toBe(ProductStatus::Active);
        }
    });
});

describe('bulkDelete', function () {
    it('soft deletes multiple products', function () {
        $products = Product::factory()->count(3)->create();

        $count = $this->service->bulkDelete($products->pluck('id')->toArray());

        expect($count)->toBe(3)
            ->and(Product::count())->toBe(0)
            ->and(Product::withTrashed()->count())->toBe(3);
    });
});

describe('bulkRestore', function () {
    it('restores multiple soft-deleted products', function () {
        $products = Product::factory()->count(3)->create();
        Product::whereIn('id', $products->pluck('id'))->delete();

        $count = $this->service->bulkRestore($products->pluck('id')->toArray());

        expect($count)->toBe(3)
            ->and(Product::count())->toBe(3);
    });
});
