<?php

declare(strict_types = 1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductSearchService
{
    /**
     * Apply search filter to a product query.
     * Searches in name, short_description, and SKU.
     * Results are ordered by relevance (name matches first).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function applySearch(Builder $query, string $searchTerm): Builder
    {
        $searchTerm = trim($searchTerm);

        if ($searchTerm === '') {
            return $query;
        }

        // Search in name, short_description, and SKU
        // MySQL with utf8mb4_unicode_ci is case-insensitive and accent-insensitive
        $termPattern = '%' . $searchTerm . '%';
        $query->where(function (Builder $q) use ($termPattern): void {
            $q->where('name', 'LIKE', $termPattern)
                ->orWhere('short_description', 'LIKE', $termPattern)
                ->orWhere('sku', 'LIKE', $termPattern);
        });

        // Order by relevance: name matches first, then SKU, then description
        $query->orderByRaw('
            CASE
                WHEN name LIKE ? THEN 1
                WHEN sku LIKE ? THEN 2
                WHEN short_description LIKE ? THEN 3
                ELSE 4
            END ASC
        ', [$termPattern, $termPattern, $termPattern]);

        return $query;
    }

    /**
     * Search for products and return a query builder.
     * Filters only active products.
     *
     * @return Builder<Product>
     */
    public function search(string $searchTerm): Builder
    {
        $query = Product::query()
            ->where('status', ProductStatus::Active);

        return $this->applySearch($query, $searchTerm);
    }
}
