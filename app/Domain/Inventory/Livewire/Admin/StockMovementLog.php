<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMovementLog extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $searchSku = '';

    #[Url(except: '')]
    public string $movementType = '';

    #[Url(except: '')]
    public string $createdBy = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public bool $showFilters = false;

    /**
     * Update search SKU.
     */
    public function updatedSearchSku(): void
    {
        $this->resetPage();
    }

    /**
     * Update movement type filter.
     */
    public function updatedMovementType(): void
    {
        $this->resetPage();
    }

    /**
     * Update created by filter.
     */
    public function updatedCreatedBy(): void
    {
        $this->resetPage();
    }

    /**
     * Update date from filter.
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Update date to filter.
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->searchSku    = '';
        $this->movementType = '';
        $this->createdBy    = '';
        $this->dateFrom     = '';
        $this->dateTo       = '';
        $this->resetPage();
    }

    /**
     * Build the query for stock movements.
     */
    private function buildQuery(): Builder
    {
        return StockMovement::query()
            ->with(['stockable', 'creator'])
            ->when($this->searchSku, function (Builder $query) {
                $query->where(function (Builder $q) {
                    // Search in products
                    $q->whereHasMorph('stockable', [Product::class], function (Builder $sq) {
                        $sq->where('sku', 'like', "%{$this->searchSku}%")
                            ->orWhere('name', 'like', "%{$this->searchSku}%");
                    })
                    // Search in variants
                        ->orWhereHasMorph('stockable', [ProductVariant::class], function (Builder $sq) {
                            $sq->where('sku', 'like', "%{$this->searchSku}%");
                        });
                });
            })
            ->when($this->movementType, function (Builder $query) {
                $query->where('movement_type', $this->movementType);
            })
            ->when($this->createdBy, function (Builder $query) {
                $query->where('created_by', $this->createdBy);
            })
            ->when($this->dateFrom, function (Builder $query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function (Builder $query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->latestFirst();
    }

    /**
     * Get stock movements.
     */
    public function getMovementsProperty(): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate(15);
    }

    /**
     * Get admins for filter dropdown.
     */
    public function getAdminsProperty(): Collection
    {
        return Admin::query()
            ->whereIn('id', StockMovement::query()->whereNotNull('created_by')->distinct()->pluck('created_by'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get movement type options for filter.
     *
     * @return array<string, string>
     */
    public function getMovementTypeOptionsProperty(): array
    {
        return MovementType::options();
    }

    /**
     * Get the SKU for a stockable item.
     */
    public function getStockableSku(StockMovement $movement): string
    {
        $stockable = $movement->stockable;

        if ($stockable === null) {
            return '-';
        }

        if ($stockable instanceof Product) {
            return $stockable->sku ?? '-';
        }

        if ($stockable instanceof ProductVariant) {
            return $stockable->sku ?? '-';
        }

        return '-';
    }

    /**
     * Get the name for a stockable item.
     */
    public function getStockableName(StockMovement $movement): string
    {
        $stockable = $movement->stockable;

        if ($stockable === null) {
            return 'Item removido';
        }

        if ($stockable instanceof Product) {
            return $stockable->name;
        }

        if ($stockable instanceof ProductVariant) {
            return $stockable->getName();
        }

        return '-';
    }

    /**
     * Export movements to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $movements = $this->buildQuery()->get();

        $filename = 'movimentacoes-estoque-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($movements) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($handle, [
                'Data/Hora',
                'SKU',
                'Produto',
                'Tipo',
                'Quantidade',
                'Saldo Anterior',
                'Saldo Posterior',
                'Usuario',
                'Observacao',
            ], ';');

            // Data rows
            foreach ($movements as $movement) {
                fputcsv($handle, [
                    $movement->created_at->format('d/m/Y H:i:s'),
                    $this->getStockableSku($movement),
                    $this->getStockableName($movement),
                    $movement->movement_type->label(),
                    $movement->quantity,
                    $movement->quantity_before,
                    $movement->quantity_after,
                    $movement->creator?->name ?? 'Sistema',
                    $movement->notes ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.stock-movement-log', [
            'movements'           => $this->movements,
            'admins'              => $this->admins,
            'movementTypeOptions' => $this->movementTypeOptions,
        ]);
    }
}
