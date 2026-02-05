<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Orders;

use App\Domain\Admin\Services\OrderService;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\{Computed, Url};
use Livewire\{Component, WithPagination};

class OrderList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'payment')]
    public string $paymentStatusFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortField = 'placed_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'tab')]
    public string $activeTab = 'all';

    public int $perPage = 15;

    /** @var array<int, string> */
    public array $selectedOrders = [];

    public bool $selectAll = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
        $this->selectedOrders = [];
        $this->selectAll      = false;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab    = $tab;
        $this->statusFilter = $tab === 'all' ? '' : $tab;
        $this->resetPage();
        $this->selectedOrders = [];
        $this->selectAll      = false;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->search              = '';
        $this->statusFilter        = '';
        $this->paymentStatusFilter = '';
        $this->dateFrom            = '';
        $this->dateTo              = '';
        $this->activeTab           = 'all';
        $this->selectedOrders      = [];
        $this->selectAll           = false;
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedOrders = $this->orders->pluck('id')->toArray();
        } else {
            $this->selectedOrders = [];
        }
    }

    public function batchUpdateStatus(string $status): void
    {
        if (empty($this->selectedOrders)) {
            return;
        }

        $newStatus = OrderStatus::tryFrom($status);

        if ($newStatus === null) {
            return;
        }

        $orderService = new OrderService();
        $result       = $orderService->batchUpdateStatus($this->selectedOrders, $newStatus);

        $this->selectedOrders = [];
        $this->selectAll      = false;

        if ($result['success'] > 0) {
            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => __(':count pedido(s) atualizado(s) com sucesso.', ['count' => $result['success']]),
            ]);
        }

        if ($result['failed'] > 0) {
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => __(':count pedido(s) nao puderam ser atualizados.', ['count' => $result['failed']]),
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return (new OrderService())->getStatusCounts();
    }

    /**
     * @return LengthAwarePaginator<Order>
     */
    public function getOrdersProperty(): LengthAwarePaginator
    {
        $effectiveStatusFilter = $this->activeTab !== 'all' ? $this->activeTab : $this->statusFilter;

        return Order::query()
            ->with(['user'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_email', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($effectiveStatusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->paymentStatusFilter, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($this->dateFrom, function ($query, $date) {
                $query->whereDate('placed_at', '>=', $date);
            })
            ->when($this->dateTo, function ($query, $date) {
                $query->whereDate('placed_at', '<=', $date);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * @return array<string, string>
     */
    public function getStatusOptionsProperty(): array
    {
        return OrderStatus::options();
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentStatusOptionsProperty(): array
    {
        return PaymentStatus::options();
    }

    /**
     * Get tabs configuration.
     *
     * @return array<int, array{key: string, label: string, count: int}>
     */
    #[Computed]
    public function tabs(): array
    {
        $counts = $this->statusCounts;

        return [
            ['key' => 'all', 'label' => __('Todos'), 'count' => $counts['all']],
            ['key' => 'pending', 'label' => __('Pendentes'), 'count' => $counts['pending']],
            ['key' => 'processing', 'label' => __('Processando'), 'count' => $counts['processing']],
            ['key' => 'shipped', 'label' => __('Enviados'), 'count' => $counts['shipped']],
            ['key' => 'completed', 'label' => __('Entregues'), 'count' => $counts['completed']],
            ['key' => 'cancelled', 'label' => __('Cancelados'), 'count' => $counts['cancelled']],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-list', [
            'orders'               => $this->orders,
            'statusOptions'        => $this->statusOptions,
            'paymentStatusOptions' => $this->paymentStatusOptions,
            'tabs'                 => $this->tabs,
        ]);
    }
}
