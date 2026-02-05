<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RecentMovementsWidget extends Component
{
    /**
     * Get recent stock movements.
     *
     * @return Collection<int, StockMovement>
     */
    #[Computed]
    public function movements(): Collection
    {
        return StockMovement::query()
            ->with(['stockable', 'creator'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get the stockable name for display.
     */
    public function getStockableName(StockMovement $movement): string
    {
        $stockable = $movement->stockable;

        if ($stockable === null) {
            return __('Item removido');
        }

        if ($stockable instanceof Product) {
            return $stockable->name;
        }

        if ($stockable instanceof ProductVariant) {
            return $stockable->getName();
        }

        return __('Desconhecido');
    }

    /**
     * Format the quantity with sign.
     */
    public function formatQuantity(int $quantity): string
    {
        if ($quantity > 0) {
            return '+' . $quantity;
        }

        return (string) $quantity;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.recent-movements-widget', [
            'movements' => $this->movements,
        ]);
    }
}
