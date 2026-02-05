<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Enums\{ProductStatus, ProductType};
use App\Domain\Catalog\Models\{Category, Product};
use App\Domain\Catalog\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};

class ProductList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /** @var array<int> */
    public array $selectedProducts = [];

    public bool $selectAll = false;

    public bool $showFilters = false;

    /**
     * Update the search query.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update filters and reset pagination.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Update type filter and reset pagination.
     */
    public function updatedType(): void
    {
        $this->resetPage();
    }

    /**
     * Update category filter and reset pagination.
     */
    public function updatedCategory(): void
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
     * Sort products by a column.
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
        $this->search   = '';
        $this->status   = '';
        $this->type     = '';
        $this->category = '';
        $this->resetPage();
    }

    /**
     * Delete a product.
     */
    public function delete(int $productId): void
    {
        $product = Product::find($productId);

        if ($product === null) {
            $this->dispatch('notify', type: 'error', message: 'Produto não encontrado.');

            return;
        }

        app(ProductService::class)->delete($product);

        $this->dispatch('notify', type: 'success', message: 'Produto movido para a lixeira!');
    }

    /**
     * Duplicate a product.
     */
    public function duplicate(int $productId): void
    {
        $product = Product::find($productId);

        if ($product === null) {
            $this->dispatch('notify', type: 'error', message: 'Produto não encontrado.');

            return;
        }

        $newProduct = app(ProductService::class)->duplicate($product);

        $this->dispatch('notify', type: 'success', message: 'Produto duplicado! Editando cópia...');

        $this->redirect(route('admin.products.edit', $newProduct), navigate: true);
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
            'activate'   => $productService->bulkUpdateStatus($this->selectedProducts, 'active'),
            'deactivate' => $productService->bulkUpdateStatus($this->selectedProducts, 'inactive'),
            'delete'     => $productService->bulkDelete($this->selectedProducts),
            default      => 0,
        };

        $actionLabels = [
            'activate'   => 'ativados',
            'deactivate' => 'desativados',
            'delete'     => 'movidos para a lixeira',
        ];

        $this->selectedProducts = [];
        $this->selectAll        = false;

        $this->dispatch('notify', type: 'success', message: "{$count} produtos {$actionLabels[$action]}!");
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
     * Build the query for products.
     */
    private function buildQuery(): Builder
    {
        return Product::query()
            ->with(['categories', 'images'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, function (Builder $query) {
                $query->where('status', $this->status);
            })
            ->when($this->type, function (Builder $query) {
                $query->where('type', $this->type);
            })
            ->when($this->category, function (Builder $query) {
                $query->whereHas('categories', function (Builder $q) {
                    $q->where('categories.id', $this->category);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        $products = $this->buildQuery()->paginate(15);

        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('position')
            ->get();

        return view('livewire.admin.product-list', [
            'products'      => $products,
            'categories'    => $categories,
            'statusOptions' => ProductStatus::options(),
            'typeOptions'   => ProductType::options(),
        ]);
    }
}
