<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Image sizes configuration.
     *
     * @var array<string, int>
     */
    private const SIZES = [
        'large'  => 1200,
        'medium' => 600,
        'thumb'  => 300,
    ];

    /**
     * WebP quality setting.
     */
    private const WEBP_QUALITY = 85;

    /**
     * Store an uploaded image, creating multiple size variants.
     *
     * @return array<string, string> Paths for each size variant
     */
    public function store(UploadedFile $file, string $directory = 'products'): array
    {
        $filename = uniqid() . '_' . time() . '.webp';
        $paths    = [];

        foreach (self::SIZES as $size => $width) {
            $image = Image::read($file->getRealPath())
                ->scaleDown(width: $width)
                ->toWebp(quality: self::WEBP_QUALITY);

            $path = "{$size}/{$filename}";
            Storage::disk('products')->put($path, $image->toString());
            $paths[$size] = "products/{$path}";
        }

        return $paths;
    }

    /**
     * Store an image from a path (for seeding/testing).
     *
     * @return array<string, string> Paths for each size variant
     */
    public function storeFromPath(string $sourcePath, string $directory = 'products'): array
    {
        $filename = uniqid() . '_' . time() . '.webp';
        $paths    = [];

        foreach (self::SIZES as $size => $width) {
            $image = Image::read($sourcePath)
                ->scaleDown(width: $width)
                ->toWebp(quality: self::WEBP_QUALITY);

            $path = "{$size}/{$filename}";
            Storage::disk('products')->put($path, $image->toString());
            $paths[$size] = "products/{$path}";
        }

        return $paths;
    }

    /**
     * Delete all size variants of an image.
     */
    public function delete(string $path): void
    {
        // Extract the filename from the path (e.g., "products/large/abc123.webp" -> "abc123.webp")
        $filename = basename($path);

        foreach (array_keys(self::SIZES) as $size) {
            Storage::disk('products')->delete("{$size}/{$filename}");
        }
    }

    /**
     * Delete a ProductImage and its files.
     */
    public function deleteProductImage(ProductImage $image): bool
    {
        $this->delete($image->path);

        return $image->delete();
    }

    /**
     * Reorder product images.
     *
     * @param  array<int, int>  $order  Array of image IDs in the desired order
     */
    public function reorder(array $order): void
    {
        foreach ($order as $position => $imageId) {
            ProductImage::where('id', $imageId)->update(['position' => $position]);
        }
    }

    /**
     * Set an image as primary for a product.
     */
    public function setPrimary(ProductImage $image): void
    {
        // Remove primary from all other images of the same product
        ProductImage::where('product_id', $image->product_id)
            ->where('id', '!=', $image->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $image->update(['is_primary' => true]);
    }

    /**
     * Get the URL for a specific size of an image path.
     */
    public function getUrlForSize(string $path, string $size = 'medium'): string
    {
        $filename = basename($path);

        return Storage::disk('products')->url("{$size}/{$filename}");
    }

    /**
     * Get all URLs for an image.
     *
     * @return array<string, string>
     */
    public function getAllUrls(string $path): array
    {
        $filename = basename($path);
        $urls     = [];

        foreach (array_keys(self::SIZES) as $size) {
            $urls[$size] = Storage::disk('products')->url("{$size}/{$filename}");
        }

        return $urls;
    }

    /**
     * Check if an image exists.
     */
    public function exists(string $path): bool
    {
        $filename = basename($path);

        return Storage::disk('products')->exists("large/{$filename}");
    }

    /**
     * Get available sizes.
     *
     * @return array<string, int>
     */
    public static function getSizes(): array
    {
        return self::SIZES;
    }
}
