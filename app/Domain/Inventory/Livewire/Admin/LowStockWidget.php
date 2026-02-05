<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LowStockWidget extends Component
{
    /**
     * Get products with low stock or out of stock.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function lowStockItems(): Collection
    {
        $defaultThreshold = config('inventory.default_low_stock_threshold', 5);

        return Product::query()
            ->with('images')
            ->where('manage_stock', true)
            ->where(function ($query) use ($defaultThreshold) {
                $query->where('stock_quantity', 0)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('low_stock_threshold')
                            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                            ->where('stock_quantity', '>', 0);
                    })
                    ->orWhere(function ($q) use ($defaultThreshold) {
                        $q->whereNull('low_stock_threshold')
                            ->where('stock_quantity', '<=', $defaultThreshold)
                            ->where('stock_quantity', '>', 0);
                    });
            })
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.low-stock-widget', [
            'lowStockItems' => $this->lowStockItems,
        ]);
    }
}
