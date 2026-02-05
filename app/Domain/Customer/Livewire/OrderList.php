<?php

declare(strict_types = 1);

namespace App\Domain\Customer\Livewire;

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, Layout, Title, Url};
use Livewire\{Component, WithPagination};

#[Layout('components.layouts.app')]
#[Title('Meus Pedidos')]
class OrderList extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    #[Url(as: 'search')]
    public string $search = '';

    protected int $perPage = 10;

    /**
     * Reset pagination when status filter changes.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Clear the status filter.
     */
    public function clearFilter(): void
    {
        $this->status = '';
        $this->resetPage();
    }

    /**
     * Clear the search.
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Get the orders for the authenticated user.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Order::query()
            ->with(['items.product.images'])
            ->forUser($user->id)
            ->latest('placed_at');

        if ($this->status !== '') {
            $orderStatus = OrderStatus::tryFrom($this->status);

            if ($orderStatus !== null) {
                $query->where('status', $orderStatus);
            }
        }

        if ($this->search !== '') {
            $query->where('order_number', 'LIKE', '%' . $this->search . '%');
        }

        return $query->paginate($this->perPage);
    }

    /**
     * Get the total count of orders (filtered).
     */
    #[Computed]
    public function ordersCount(): int
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Order::query()->forUser($user->id);

        if ($this->status !== '') {
            $orderStatus = OrderStatus::tryFrom($this->status);

            if ($orderStatus !== null) {
                $query->where('status', $orderStatus);
            }
        }

        if ($this->search !== '') {
            $query->where('order_number', 'LIKE', '%' . $this->search . '%');
        }

        return $query->count();
    }

    /**
     * Check if user has any orders at all (unfiltered).
     */
    #[Computed]
    public function hasAnyOrders(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return Order::query()->forUser($user->id)->exists();
    }

    /**
     * Get the filter options for status.
     *
     * @return array<string, string>
     */
    public function getStatusFilters(): array
    {
        return [
            ''           => 'Todos',
            'pending'    => 'Pendentes',
            'processing' => 'Em Andamento',
            'shipped'    => 'Enviados',
            'completed'  => 'Entregues',
            'cancelled'  => 'Cancelados',
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.customer.order-list', [
            'orders'        => $this->orders(),
            'ordersCount'   => $this->ordersCount(),
            'statusFilters' => $this->getStatusFilters(),
            'hasAnyOrders'  => $this->hasAnyOrders(),
        ]);
    }
}
