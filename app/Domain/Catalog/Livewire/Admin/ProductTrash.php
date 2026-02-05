<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};

class ProductTrash extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    /** @var array<int> */
    public array $selectedProducts = [];

    public bool $selectAll = false;

    /**
     * Update the search query.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle select all products.
     */
    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedProducts = $this->getProductIds();
        } else {
            $this->selectedProducts = [];
        }
    }

    /**
     * Restore a product.
     */
    public function restore(int $productId): void
    {
        $product = Product::onlyTrashed()->find($productId);

        if ($product === null) {
            $this->dispatch('notify', type: 'error', message: 'Produto não encontrado.');

            return;
        }

        app(ProductService::class)->restore($product);

        $this->dispatch('notify', type: 'success', message: 'Produto restaurado com sucesso!');
    }

    /**
     * Force delete a product.
     */
    public function forceDelete(int $productId): void
    {
        $product = Product::onlyTrashed()->find($productId);

        if ($product === null) {
            $this->dispatch('notify', type: 'error', message: 'Produto não encontrado.');

            return;
        }

        app(ProductService::class)->forceDelete($product);

        $this->dispatch('notify', type: 'success', message: 'Produto excluído permanentemente!');
    }

    /**
     * Perform bulk action on selected products.
     */
    public function bulkAction(string $action): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('notify', type: 'error', message: 'Selecione pelo menos um produto.');

            return;
        }

        $productService = app(ProductService::class);

        $count = match ($action) {
            'restore'      => $productService->bulkRestore($this->selectedProducts),
            'force_delete' => $this->bulkForceDelete($this->selectedProducts),
            default        => 0,
        };

        $actionLabels = [
            'restore'      => 'restaurados',
            'force_delete' => 'excluídos permanentemente',
        ];

        $this->selectedProducts = [];
        $this->selectAll        = false;

        $this->dispatch('notify', type: 'success', message: "{$count} produtos {$actionLabels[$action]}!");
    }

    /**
     * Empty the entire trash.
     */
    public function emptyTrash(): void
    {
        $productService = app(ProductService::class);
        $products       = Product::onlyTrashed()->get();
        $count          = 0;

        foreach ($products as $product) {
            $productService->forceDelete($product);
            $count++;
        }

        $this->dispatch('notify', type: 'success', message: "{$count} produtos excluídos permanentemente!");
    }

    /**
     * Bulk force delete products.
     *
     * @param  array<int>  $productIds
     */
    private function bulkForceDelete(array $productIds): int
    {
        $count          = 0;
        $productService = app(ProductService::class);
        $products       = Product::onlyTrashed()->whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $productService->forceDelete($product);
            $count++;
        }

        return $count;
    }

    /**
     * Get the product IDs for the current query.
     *
     * @return array<int>
     */
    private function getProductIds(): array
    {
        return $this->buildQuery()->pluck('id')->toArray();
    }

    /**
     * Build the query for trashed products.
     */
    private function buildQuery(): Builder
    {
        return Product::onlyTrashed()
            ->with(['images'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('deleted_at', 'desc');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        $products = $this->buildQuery()->paginate(15);

        return view('livewire.admin.product-trash', [
            'products' => $products,
        ]);
    }
}
