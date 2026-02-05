<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Catalog\Models\{Category, Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};

class StockList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $stockStatus = '';

    #[Url(except: 'stock_quantity')]
    public string $sortBy = 'stock_quantity';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public bool $showFilters = false;

    // Adjust modal state
    public bool $adjustModal = false;

    public string $adjustStockableType = '';

    public int $adjustStockableId = 0;

    public string $adjustMovementType = 'manual_entry';

    public int $adjustQuantity = 0;

    public string $adjustNotes = '';

    public string $adjustItemName = '';

    public int $adjustCurrentStock = 0;

    /**
     * Update the search query.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update stock status filter.
     */
    public function updatedStockStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Update category filter.
     */
    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Sort by column.
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search      = '';
        $this->category    = '';
        $this->stockStatus = '';
        $this->resetPage();
    }

    /**
     * Open the adjust stock modal.
     */
    public function openAdjustModal(string $type, int $id): void
    {
        $stockable = app(StockService::class)->resolveStockable($type, $id);

        if ($stockable === null) {
            $this->dispatch('notify', type: 'error', message: 'Item nao encontrado.');

            return;
        }

        $this->adjustStockableType = $type;
        $this->adjustStockableId   = $id;
        $this->adjustMovementType  = 'manual_entry';
        $this->adjustQuantity      = 0;
        $this->adjustNotes         = '';
        $this->adjustCurrentStock  = $stockable->getStockQuantity();

        // Get item name
        if ($stockable instanceof Product) {
            $this->adjustItemName = $stockable->name;
        } elseif ($stockable instanceof ProductVariant) {
            $this->adjustItemName = $stockable->getName();
        }

        $this->adjustModal = true;
    }

    /**
     * Close the adjust stock modal.
     */
    public function closeAdjustModal(): void
    {
        $this->adjustModal        = false;
        $this->adjustQuantity     = 0;
        $this->adjustNotes        = '';
        $this->adjustItemName     = '';
        $this->adjustCurrentStock = 0;
    }

    /**
     * Submit the stock adjustment.
     */
    public function submitAdjustment(): void
    {
        $this->validate([
            'adjustQuantity'     => ['required', 'integer', 'not_in:0'],
            'adjustNotes'        => ['required', 'string', 'min:3', 'max:500'],
            'adjustMovementType' => ['required', 'string'],
        ], [
            'adjustQuantity.not_in' => 'A quantidade nao pode ser zero.',
            'adjustNotes.required'  => 'O motivo e obrigatorio.',
            'adjustNotes.min'       => 'O motivo deve ter pelo menos 3 caracteres.',
        ]);

        $stockService = app(StockService::class);
        $stockable    = $stockService->resolveStockable($this->adjustStockableType, $this->adjustStockableId);

        if ($stockable === null) {
            $this->dispatch('notify', type: 'error', message: 'Item nao encontrado.');

            return;
        }

        $movementType = MovementType::from($this->adjustMovementType);
        $quantity     = $this->adjustQuantity;

        // Ensure correct sign based on movement type
        if ($movementType === MovementType::ManualExit && $quantity > 0) {
            $quantity = -$quantity;
        } elseif ($movementType === MovementType::ManualEntry && $quantity < 0) {
            $quantity = abs($quantity);
        }

        try {
            $movement = $stockService->adjust(
                stockable: $stockable,
                quantity: $quantity,
                type: $movementType,
                notes: $this->adjustNotes,
            );

            $this->closeAdjustModal();
            $this->dispatch('notify', type: 'success', message: sprintf(
                'Estoque ajustado com sucesso! Novo saldo: %d unidades.',
                $movement->quantity_after,
            ));
        } catch (InsufficientStockException $e) {
            $this->dispatch('notify', type: 'error', message: sprintf(
                'Estoque insuficiente. Disponivel: %d unidades.',
                $e->getAvailableQuantity(),
            ));
        }
    }

    /**
     * Build the query for stock items.
     */
    private function buildQuery(): Builder
    {
        return Product::query()
            ->with(['categories', 'variants', 'activeReservations'])
            ->where('manage_stock', true)
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category, function (Builder $query) {
                $query->whereHas('categories', function (Builder $q) {
                    $q->where('categories.id', $this->category);
                });
            })
            ->when($this->stockStatus === 'out_of_stock', function (Builder $query) {
                $query->where('stock_quantity', 0);
            })
            ->when($this->stockStatus === 'low_stock', function (Builder $query) {
                $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('stock_quantity', '>', 0);
            })
            ->when($this->stockStatus === 'in_stock', function (Builder $query) {
                $query->where('stock_quantity', '>', 0);
            })
            ->orderBy($this->sortBy === 'name' ? 'name' : 'stock_quantity', $this->sortDirection);
    }

    /**
     * Get stock items with calculated available quantity.
     */
    public function getStockItemsProperty(): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate(15);
    }

    /**
     * Get categories for filter dropdown.
     */
    public function getCategoriesProperty(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('position')
            ->get();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.stock-list', [
            'stockItems'          => $this->stockItems,
            'categories'          => $this->categories,
            'movementTypeOptions' => MovementType::manualOptions(),
            'stockStatusOptions'  => [
                ''             => 'Todos',
                'in_stock'     => 'Em Estoque',
                'low_stock'    => 'Estoque Baixo',
                'out_of_stock' => 'Sem Estoque',
            ],
        ]);
    }
}
