<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Orders;

use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderDetails extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'user',
            'items.product',
            'payments',
            'coupon',
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-details');
    }
}
