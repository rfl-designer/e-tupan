<?php declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductImage};
use App\Domain\Catalog\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('products');
    $this->service = new ImageService();
});

describe('ImageService', function () {
    describe('store', function () {
        it('stores an uploaded image in multiple sizes', function () {
            $file = UploadedFile::fake()->image('product.jpg', 1500, 1500);

            $paths = $this->service->store($file);

            expect($paths)->toHaveKeys(['large', 'medium', 'thumb'])
                ->and($paths['large'])->toStartWith('products/large/')
                ->and($paths['large'])->toEndWith('.webp')
                ->and($paths['medium'])->toStartWith('products/medium/')
                ->and($paths['thumb'])->toStartWith('products/thumb/');

            // Verify files exist
            $filename = basename($paths['large']);
            Storage::disk('products')->assertExists("large/{$filename}");
            Storage::disk('products')->assertExists("medium/{$filename}");
            Storage::disk('products')->assertExists("thumb/{$filename}");
        });

        it('converts images to webp format', function () {
            $file = UploadedFile::fake()->image('product.png', 800, 600);

            $paths = $this->service->store($file);

            expect($paths['large'])->toEndWith('.webp');
        });
    });

    describe('delete', function () {
        it('deletes all size variants of an image', function () {
            $file  = UploadedFile::fake()->image('product.jpg', 1000, 1000);
            $paths = $this->service->store($file);

            $this->service->delete($paths['large']);

            $filename = basename($paths['large']);
            Storage::disk('products')->assertMissing("large/{$filename}");
            Storage::disk('products')->assertMissing("medium/{$filename}");
            Storage::disk('products')->assertMissing("thumb/{$filename}");
        });
    });

    describe('deleteProductImage', function () {
        it('deletes a ProductImage and its files', function () {
            $file  = UploadedFile::fake()->image('product.jpg', 1000, 1000);
            $paths = $this->service->store($file);

            $product = Product::factory()->create();
            $image   = ProductImage::create([
                'product_id' => $product->id,
                'path'       => $paths['large'],
                'position'   => 0,
            ]);

            $result = $this->service->deleteProductImage($image);

            expect($result)->toBeTrue()
                ->and(ProductImage::find($image->id))->toBeNull();

            $filename = basename($paths['large']);
            Storage::disk('products')->assertMissing("large/{$filename}");
        });
    });

    describe('reorder', function () {
        it('reorders product images', function () {
            $product = Product::factory()->create();

            $image1 = ProductImage::create([
                'product_id' => $product->id,
                'path'       => 'products/large/img1.webp',
                'position'   => 0,
            ]);
            $image2 = ProductImage::create([
                'product_id' => $product->id,
                'path'       => 'products/large/img2.webp',
                'position'   => 1,
            ]);
            $image3 = ProductImage::create([
                'product_id' => $product->id,
                'path'       => 'products/large/img3.webp',
                'position'   => 2,
            ]);

            $this->service->reorder([$image3->id, $image1->id, $image2->id]);

            expect($image3->fresh()->position)->toBe(0)
                ->and($image1->fresh()->position)->toBe(1)
                ->and($image2->fresh()->position)->toBe(2);
        });
    });

    describe('setPrimary', function () {
        it('sets an image as primary and unsets others', function () {
            $product = Product::factory()->create();

            $image1 = ProductImage::create([
                'product_id' => $product->id,
                'path'       => 'products/large/img1.webp',
                'position'   => 0,
                'is_primary' => true,
            ]);
            $image2 = ProductImage::create([
                'product_id' => $product->id,
                'path'       => 'products/large/img2.webp',
                'position'   => 1,
                'is_primary' => false,
            ]);

            $this->service->setPrimary($image2);

            expect($image1->fresh()->is_primary)->toBeFalse()
                ->and($image2->fresh()->is_primary)->toBeTrue();
        });
    });

    describe('exists', function () {
        it('returns true when image exists', function () {
            $file  = UploadedFile::fake()->image('product.jpg', 800, 600);
            $paths = $this->service->store($file);

            expect($this->service->exists($paths['large']))->toBeTrue();
        });

        it('returns false when image does not exist', function () {
            expect($this->service->exists('products/large/nonexistent.webp'))->toBeFalse();
        });
    });

    describe('getSizes', function () {
        it('returns available image sizes', function () {
            $sizes = ImageService::getSizes();

            expect($sizes)->toHaveKeys(['large', 'medium', 'thumb'])
                ->and($sizes['large'])->toBe(1200)
                ->and($sizes['medium'])->toBe(600)
                ->and($sizes['thumb'])->toBe(300);
        });
    });
});

describe('Intervention/Image Integration', function () {
    it('can read and process an image', function () {
        $file = UploadedFile::fake()->image('test.jpg', 2000, 1500);

        $paths = $this->service->store($file);

        // Verify all sizes were created
        expect($paths)->toHaveCount(3);

        // Verify files exist and have content
        $filename      = basename($paths['large']);
        $largeContent  = Storage::disk('products')->get("large/{$filename}");
        $mediumContent = Storage::disk('products')->get("medium/{$filename}");
        $thumbContent  = Storage::disk('products')->get("thumb/{$filename}");

        expect($largeContent)->not->toBeEmpty()
            ->and($mediumContent)->not->toBeEmpty()
            ->and($thumbContent)->not->toBeEmpty();

        // Large should be bigger than medium, medium bigger than thumb
        expect(strlen($largeContent))->toBeGreaterThan(strlen($thumbContent));
    });

    it('handles various image formats', function () {
        $formats = ['jpg', 'png', 'gif'];

        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("test.{$format}", 800, 600);

            $paths = $this->service->store($file);

            expect($paths['large'])->toEndWith('.webp');

            $filename = basename($paths['large']);
            Storage::disk('products')->assertExists("large/{$filename}");

            // Clean up for next iteration
            $this->service->delete($paths['large']);
        }
    });
});
