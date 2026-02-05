<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Livewire\Admin;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};

class AbandonedCarts extends Component
{
    use WithPagination;

    #[Url(as: 's')]
    public string $search = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public ?Cart $selectedCart = null;

    public bool $showModal = false;

    /**
     * Show cart details in modal.
     */
    public function showDetails(string $cartId): void
    {
        $this->selectedCart = Cart::with(['items.product.images', 'items.variant', 'user'])
            ->find($cartId);

        $this->showModal = true;
    }

    /**
     * Close the details modal.
     */
    public function closeModal(): void
    {
        $this->showModal    = false;
        $this->selectedCart = null;
    }

    /**
     * Reset filters.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /**
     * Updated search property.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Updated dateFrom property.
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Updated dateTo property.
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Get abandoned carts query.
     *
     * @return LengthAwarePaginator<Cart>
     */
    public function getCartsProperty(): LengthAwarePaginator
    {
        return Cart::query()
            ->with(['user', 'items.product'])
            ->where('status', CartStatus::Abandoned)
            ->whereHas('items')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('abandoned_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('abandoned_at', '<=', $this->dateTo);
            })
            ->orderByDesc('abandoned_at')
            ->paginate(15);
    }

    /**
     * Get summary statistics.
     *
     * @return array{total_carts: int, total_value: int, avg_value: float}
     */
    public function getSummaryProperty(): array
    {
        $query = Cart::query()
            ->where('status', CartStatus::Abandoned)
            ->whereHas('items');

        $totalCarts = $query->count();
        $totalValue = (int) $query->sum('total');
        $avgValue   = $totalCarts > 0 ? $totalValue / $totalCarts : 0;

        return [
            'total_carts' => $totalCarts,
            'total_value' => $totalValue,
            'avg_value'   => $avgValue,
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.abandoned-carts', [
            'carts'   => $this->carts,
            'summary' => $this->summary,
        ]);
    }
}
