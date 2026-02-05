<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Customers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CustomerDetails extends Component
{
    public User $customer;

    public function mount(User $customer): void
    {
        $this->customer = $customer->load([
            'addresses',
            'orders' => fn ($q) => $q->latest('placed_at')->limit(10),
        ]);
    }

    public function getOrdersCountProperty(): int
    {
        return $this->customer->orders()->count();
    }

    public function getTotalSpentProperty(): int
    {
        return (int) $this->customer->orders()->sum('total');
    }

    public function getAverageOrderValueProperty(): float
    {
        $count = $this->ordersCount;

        if ($count === 0) {
            return 0;
        }

        return $this->totalSpent / $count;
    }

    public function render(): View
    {
        return view('livewire.admin.customers.customer-details', [
            'ordersCount'       => $this->ordersCount,
            'totalSpent'        => $this->totalSpent,
            'averageOrderValue' => $this->averageOrderValue,
        ]);
    }
}
