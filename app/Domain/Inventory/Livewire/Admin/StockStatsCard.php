<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StockStatsCard extends Component
{
    /**
     * Get total count of SKUs with managed stock.
     */
    #[Computed]
    public function totalManagedSkus(): int
    {
        return Product::query()
            ->where('manage_stock', true)
            ->count();
    }

    /**
     * Get count of products out of stock.
     */
    #[Computed]
    public function outOfStockCount(): int
    {
        return Product::query()
            ->where('manage_stock', true)
            ->where('stock_quantity', 0)
            ->count();
    }

    /**
     * Get count of products with low stock.
     */
    #[Computed]
    public function lowStockCount(): int
    {
        $defaultThreshold = config('inventory.default_low_stock_threshold', 5);

        return Product::query()
            ->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where(function ($query) use ($defaultThreshold) {
                $query->where(function ($q) {
                    $q->whereNotNull('low_stock_threshold')
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                })->orWhere(function ($q) use ($defaultThreshold) {
                    $q->whereNull('low_stock_threshold')
                        ->where('stock_quantity', '<=', $defaultThreshold);
                });
            })
            ->count();
    }

    /**
     * Get total stock value in centavos.
     */
    #[Computed]
    public function totalStockValue(): int
    {
        return (int) Product::query()
            ->where('manage_stock', true)
            ->whereNotNull('cost')
            ->selectRaw('SUM(stock_quantity * cost) as total')
            ->value('total') ?? 0;
    }

    /**
     * Format currency value in BRL.
     */
    public function formatCurrency(int $valueInCents): string
    {
        return number_format($valueInCents / 100, 2, ',', '.');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.stock-stats-card');
    }
}
