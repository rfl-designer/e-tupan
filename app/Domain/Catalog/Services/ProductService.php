<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\{Product, ProductImage};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private ImageService $imageService,
    ) {
    }

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Set created_by if authenticated as user (not admin)
            if (Auth::guard('web')->check()) {
                $data['created_by'] = Auth::guard('web')->id();
                $data['updated_by'] = Auth::guard('web')->id();
            }

            // Extract relationships data
            $categories = $data['categories'] ?? [];
            $tags       = $data['tags'] ?? [];
            $images     = $data['images'] ?? [];

            unset($data['categories'], $data['tags'], $data['images']);

            // Create the product
            $product = Product::create($data);

            // Sync relationships
            if (!empty($categories)) {
                $product->categories()->sync($categories);
            }

            if (!empty($tags)) {
                $product->tags()->sync($tags);
            }

            // Handle images
            if (!empty($images)) {
                $this->syncImages($product, $images);
            }

            return $product->load(['categories', 'tags', 'images']);
        });
    }

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // Set updated_by if authenticated as user (not admin)
            if (Auth::guard('web')->check()) {
                $data['updated_by'] = Auth::guard('web')->id();
            }

            // Extract relationships data
            $categories = $data['categories'] ?? null;
            $tags       = $data['tags'] ?? null;
            $images     = $data['images'] ?? null;

            unset($data['categories'], $data['tags'], $data['images']);

            // Update the product
            $product->update($data);

            // Sync relationships if provided
            if ($categories !== null) {
                $product->categories()->sync($categories);
            }

            if ($tags !== null) {
                $product->tags()->sync($tags);
            }

            // Handle images if provided
            if ($images !== null) {
                $this->syncImages($product, $images);
            }

            return $product->fresh(['categories', 'tags', 'images']);
        });
    }

    /**
     * Soft delete a product.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            return $product->delete();
        });
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->restore();

            return $product->fresh();
        });
    }

    /**
     * Permanently delete a product and its images.
     */
    public function forceDelete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete all images from storage
            foreach ($product->images as $image) {
                $this->imageService->delete($image->path);
            }

            // Delete variant images
            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    $this->imageService->delete($image->path);
                }
            }

            // Force delete the product (cascades to related records)
            return $product->forceDelete();
        });
    }

    /**
     * Duplicate a product.
     */
    public function duplicate(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            // Load all relationships
            $product->load(['categories', 'tags', 'productAttributes', 'images']);

            // Create new product data
            $newData = $product->toArray();

            // Remove fields that shouldn't be duplicated
            unset(
                $newData['id'],
                $newData['created_at'],
                $newData['updated_at'],
                $newData['deleted_at'],
                $newData['categories'],
                $newData['tags'],
                $newData['product_attributes'],
                $newData['images'],
            );

            // Modify name and slug
            $newData['name']   = $product->name . ' (Cópia)';
            $newData['slug']   = Str::slug($newData['name']) . '-' . uniqid();
            $newData['sku']    = $product->sku . '-COPY-' . strtoupper(Str::random(4));
            $newData['status'] = 'draft'; // New products start as draft

            // Set creator if authenticated as user (not admin)
            if (Auth::guard('web')->check()) {
                $newData['created_by'] = Auth::guard('web')->id();
                $newData['updated_by'] = Auth::guard('web')->id();
            }

            // Create the new product
            $newProduct = Product::create($newData);

            // Sync categories
            $newProduct->categories()->sync($product->categories->pluck('id'));

            // Sync tags
            $newProduct->tags()->sync($product->tags->pluck('id'));

            // Note: Images are NOT duplicated - user should upload new ones

            return $newProduct->load(['categories', 'tags']);
        });
    }

    /**
     * Sync images for a product.
     *
     * @param  array<int, array{id?: int, path?: string, alt_text?: string, position?: int, is_primary?: bool}>  $images
     */
    private function syncImages(Product $product, array $images): void
    {
        $existingIds = [];

        foreach ($images as $position => $imageData) {
            if (isset($imageData['id'])) {
                // Update existing image
                $image = ProductImage::find($imageData['id']);

                if ($image && $image->product_id === $product->id) {
                    $image->update([
                        'alt_text'   => $imageData['alt_text'] ?? $image->alt_text,
                        'position'   => $imageData['position'] ?? $position,
                        'is_primary' => $imageData['is_primary'] ?? false,
                    ]);
                    $existingIds[] = $image->id;
                }
            } elseif (isset($imageData['path'])) {
                // Create new image
                $image = $product->images()->create([
                    'path'       => $imageData['path'],
                    'alt_text'   => $imageData['alt_text'] ?? null,
                    'position'   => $imageData['position'] ?? $position,
                    'is_primary' => $imageData['is_primary'] ?? false,
                ]);
                $existingIds[] = $image->id;
            }
        }

        // Delete images that are no longer in the list
        $imagesToDelete = $product->images()->whereNotIn('id', $existingIds)->get();

        foreach ($imagesToDelete as $image) {
            $this->imageService->deleteProductImage($image);
        }
    }

    /**
     * Update product stock.
     */
    public function updateStock(Product $product, int $quantity, bool $absolute = true): Product
    {
        return DB::transaction(function () use ($product, $quantity, $absolute) {
            if ($absolute) {
                $product->stock_quantity = $quantity;
            } else {
                $product->stock_quantity += $quantity;
            }

            $product->save();

            return $product;
        });
    }

    /**
     * Bulk update product status.
     *
     * @param  array<int>  $productIds
     */
    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        return Product::whereIn('id', $productIds)->update([
            'status' => $status,
        ]);
    }

    /**
     * Bulk delete products (soft delete).
     *
     * @param  array<int>  $productIds
     */
    public function bulkDelete(array $productIds): int
    {
        return Product::whereIn('id', $productIds)->delete();
    }

    /**
     * Bulk restore products.
     *
     * @param  array<int>  $productIds
     */
    public function bulkRestore(array $productIds): int
    {
        return Product::onlyTrashed()->whereIn('id', $productIds)->restore();
    }
}
