<?php declare(strict_types = 1);

use App\Domain\Catalog\Enums\{ProductStatus, ProductType};
use App\Domain\Catalog\Models\{Category, Product, ProductImage, ProductVariant, Tag};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Product Model', function () {
    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $product = new Product();

            expect($product->getFillable())->toBe([
                'name',
                'slug',
                'short_description',
                'description',
                'type',
                'status',
                'price',
                'sale_price',
                'sale_start_at',
                'sale_end_at',
                'cost',
                'sku',
                'stock_quantity',
                'manage_stock',
                'allow_backorders',
                'low_stock_threshold',
                'low_stock_notified_at',
                'notify_low_stock',
                'weight',
                'length',
                'width',
                'height',
                'meta_title',
                'meta_description',
                'created_by',
                'updated_by',
            ]);
        });
    });

    describe('casts', function () {
        it('casts type to ProductType enum', function () {
            $product = Product::factory()->create(['type' => 'simple']);

            expect($product->type)->toBeInstanceOf(ProductType::class)
                ->and($product->type)->toBe(ProductType::Simple);
        });

        it('casts status to ProductStatus enum', function () {
            $product = Product::factory()->create(['status' => 'active']);

            expect($product->status)->toBeInstanceOf(ProductStatus::class)
                ->and($product->status)->toBe(ProductStatus::Active);
        });

        it('casts sale_start_at to datetime', function () {
            $date    = now()->addDay();
            $product = Product::factory()->create(['sale_start_at' => $date]);

            expect($product->sale_start_at)->toBeInstanceOf(Carbon::class);
        });

        it('casts manage_stock to boolean', function () {
            $product = Product::factory()->create(['manage_stock' => 1]);

            expect($product->manage_stock)->toBeBool()
                ->and($product->manage_stock)->toBeTrue();
        });

        it('casts price to integer', function () {
            $product = Product::factory()->create(['price' => 9999]);

            expect($product->price)->toBeInt()
                ->and($product->price)->toBe(9999);
        });
    });

    describe('slug generation', function () {
        it('generates slug automatically from name', function () {
            $product = Product::create(['name' => 'Smartphone Samsung Galaxy', 'price' => 100000]);

            expect($product->slug)->toBe('smartphone-samsung-galaxy');
        });

        it('generates unique slug when duplicate exists', function () {
            Product::create(['name' => 'iPhone', 'slug' => 'iphone', 'price' => 100000]);
            $product = Product::create(['name' => 'iPhone', 'price' => 100000]);

            expect($product->slug)->toBe('iphone-1');
        });
    });

    describe('relationships', function () {
        it('belongs to many categories', function () {
            $product    = Product::factory()->create();
            $categories = Category::factory()->count(2)->create();
            $product->categories()->attach($categories);

            expect($product->categories)->toHaveCount(2)
                ->and($product->categories->first())->toBeInstanceOf(Category::class);
        });

        it('has many images', function () {
            $product = Product::factory()->create();
            ProductImage::factory()->count(3)->create(['product_id' => $product->id]);

            expect($product->images)->toHaveCount(3)
                ->and($product->images->first())->toBeInstanceOf(ProductImage::class);
        });

        it('has many variants', function () {
            $product = Product::factory()->create(['type' => 'variable']);
            ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

            expect($product->variants)->toHaveCount(2)
                ->and($product->variants->first())->toBeInstanceOf(ProductVariant::class);
        });

        it('belongs to many tags', function () {
            $product = Product::factory()->create();
            $tags    = Tag::factory()->count(3)->create();
            $product->tags()->attach($tags);

            expect($product->tags)->toHaveCount(3)
                ->and($product->tags->first())->toBeInstanceOf(Tag::class);
        });

        it('belongs to many product attributes', function () {
            $product        = Product::factory()->create();
            $attribute      = \App\Domain\Catalog\Models\Attribute::factory()->create();
            $attributeValue = \App\Domain\Catalog\Models\AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

            $product->productAttributes()->attach($attribute->id, [
                'attribute_value_id'  => $attributeValue->id,
                'used_for_variations' => true,
            ]);

            expect($product->productAttributes)->toHaveCount(1)
                ->and($product->productAttributes->first())->toBeInstanceOf(\App\Domain\Catalog\Models\Attribute::class);
        });

        it('belongs to creator user', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->create(['created_by' => $user->id]);

            expect($product->creator)->toBeInstanceOf(User::class)
                ->and($product->creator->id)->toBe($user->id);
        });

        it('belongs to updater user', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->create(['updated_by' => $user->id]);

            expect($product->updater)->toBeInstanceOf(User::class)
                ->and($product->updater->id)->toBe($user->id);
        });
    });

    describe('scopes', function () {
        it('filters active products', function () {
            Product::factory()->count(2)->create(['status' => ProductStatus::Active]);
            Product::factory()->create(['status' => ProductStatus::Draft]);
            Product::factory()->create(['status' => ProductStatus::Inactive]);

            expect(Product::active()->count())->toBe(2);
        });

        it('filters products on sale', function () {
            // Product on sale (no date restrictions)
            Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 8000,
                'sale_start_at' => null,
                'sale_end_at'   => null,
            ]);

            // Product on sale (within date range)
            Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 7000,
                'sale_start_at' => now()->subDay(),
                'sale_end_at'   => now()->addDay(),
            ]);

            // Product not on sale (future start)
            Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 6000,
                'sale_start_at' => now()->addDay(),
                'sale_end_at'   => now()->addWeek(),
            ]);

            // Product not on sale (past end)
            Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 5000,
                'sale_start_at' => now()->subWeek(),
                'sale_end_at'   => now()->subDay(),
            ]);

            // Product without sale price
            Product::factory()->create([
                'price'      => 10000,
                'sale_price' => null,
            ]);

            expect(Product::onSale()->count())->toBe(2);
        });
    });

    describe('price accessors', function () {
        it('returns price in reais', function () {
            $product = Product::factory()->create(['price' => 9999]);

            expect($product->price_in_reais)->toBe(99.99);
        });

        it('returns sale price in reais', function () {
            $product = Product::factory()->create(['sale_price' => 7999]);

            expect($product->sale_price_in_reais)->toBe(79.99);
        });

        it('returns null for sale price in reais when null', function () {
            $product = Product::factory()->create(['sale_price' => null]);

            expect($product->sale_price_in_reais)->toBeNull();
        });

        it('returns cost in reais', function () {
            $product = Product::factory()->create(['cost' => 5000]);

            expect($product->cost_in_reais)->toBe(50.00);
        });

        it('returns current price in reais', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => 8000,
            ]);

            expect($product->current_price_in_reais)->toBe(80.00);
        });
    });

    describe('sale detection', function () {
        it('detects product on sale without date restrictions', function () {
            $product = Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 8000,
                'sale_start_at' => null,
                'sale_end_at'   => null,
            ]);

            expect($product->isOnSale())->toBeTrue();
        });

        it('detects product on sale within date range', function () {
            $product = Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 8000,
                'sale_start_at' => now()->subDay(),
                'sale_end_at'   => now()->addDay(),
            ]);

            expect($product->isOnSale())->toBeTrue();
        });

        it('detects product not on sale when future start', function () {
            $product = Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 8000,
                'sale_start_at' => now()->addDay(),
                'sale_end_at'   => now()->addWeek(),
            ]);

            expect($product->isOnSale())->toBeFalse();
        });

        it('detects product not on sale when past end', function () {
            $product = Product::factory()->create([
                'price'         => 10000,
                'sale_price'    => 8000,
                'sale_start_at' => now()->subWeek(),
                'sale_end_at'   => now()->subDay(),
            ]);

            expect($product->isOnSale())->toBeFalse();
        });

        it('detects product not on sale when no sale price', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => null,
            ]);

            expect($product->isOnSale())->toBeFalse();
        });

        it('detects product not on sale when sale price is zero', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => 0,
            ]);

            expect($product->isOnSale())->toBeFalse();
        });
    });

    describe('current price', function () {
        it('returns sale price when on sale', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => 8000,
            ]);

            expect($product->getCurrentPrice())->toBe(8000);
        });

        it('returns regular price when not on sale', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => null,
            ]);

            expect($product->getCurrentPrice())->toBe(10000);
        });
    });

    describe('discount percentage', function () {
        it('calculates discount percentage correctly', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => 7500,
            ]);

            expect($product->getDiscountPercentage())->toBe(25);
        });

        it('returns null when not on sale', function () {
            $product = Product::factory()->create([
                'price'      => 10000,
                'sale_price' => null,
            ]);

            expect($product->getDiscountPercentage())->toBeNull();
        });

        it('returns null when price is zero', function () {
            $product = Product::factory()->create([
                'price'      => 0,
                'sale_price' => 0,
            ]);

            expect($product->getDiscountPercentage())->toBeNull();
        });
    });

    describe('stock management', function () {
        it('is in stock when manage_stock is false', function () {
            $product = Product::factory()->create([
                'manage_stock'   => false,
                'stock_quantity' => 0,
            ]);

            expect($product->isInStock())->toBeTrue();
        });

        it('is in stock when allow_backorders is true', function () {
            $product = Product::factory()->create([
                'manage_stock'     => true,
                'allow_backorders' => true,
                'stock_quantity'   => 0,
            ]);

            expect($product->isInStock())->toBeTrue();
        });

        it('is in stock when stock quantity is positive', function () {
            $product = Product::factory()->create([
                'manage_stock'     => true,
                'allow_backorders' => false,
                'stock_quantity'   => 10,
            ]);

            expect($product->isInStock())->toBeTrue();
        });

        it('is out of stock when stock quantity is zero', function () {
            $product = Product::factory()->create([
                'manage_stock'     => true,
                'allow_backorders' => false,
                'stock_quantity'   => 0,
            ]);

            expect($product->isInStock())->toBeFalse();
        });
    });

    describe('product type helpers', function () {
        it('detects simple product', function () {
            $product = Product::factory()->create(['type' => ProductType::Simple]);

            expect($product->isSimple())->toBeTrue()
                ->and($product->isVariable())->toBeFalse();
        });

        it('detects variable product', function () {
            $product = Product::factory()->create(['type' => ProductType::Variable]);

            expect($product->isVariable())->toBeTrue()
                ->and($product->isSimple())->toBeFalse();
        });
    });

    describe('soft deletes', function () {
        it('soft deletes product', function () {
            $product = Product::factory()->create();
            $product->delete();

            expect(Product::count())->toBe(0)
                ->and(Product::withTrashed()->count())->toBe(1);
        });

        it('restores soft deleted product', function () {
            $product = Product::factory()->create();
            $product->delete();
            $product->restore();

            expect(Product::count())->toBe(1);
        });
    });

    describe('primary image', function () {
        it('returns primary image when exists', function () {
            $product = Product::factory()->create();
            ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => false]);
            $primary = ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);

            expect($product->primaryImage()->id)->toBe($primary->id);
        });

        it('returns first image when no primary exists', function () {
            $product = Product::factory()->create();
            $first   = ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => false, 'position' => 0]);
            ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => false, 'position' => 1]);

            expect($product->primaryImage()->id)->toBe($first->id);
        });

        it('returns null when no images exist', function () {
            $product = Product::factory()->create();

            expect($product->primaryImage())->toBeNull();
        });
    });
});
