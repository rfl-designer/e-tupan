<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Orders;

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderTimeline extends Component
{
    public Order $order;

    /**
     * @return array<int, array{label: string, date: ?\Carbon\Carbon, icon: string, color: string, completed: bool}>
     */
    public function getTimelineEventsProperty(): array
    {
        $events = [];

        // Order placed
        $events[] = [
            'label'     => 'Pedido Criado',
            'date'      => $this->order->placed_at,
            'icon'      => 'shopping-cart',
            'color'     => 'sky',
            'completed' => $this->order->placed_at !== null,
        ];

        // Payment approved
        $events[] = [
            'label'     => 'Pagamento Aprovado',
            'date'      => $this->order->paid_at,
            'icon'      => 'credit-card',
            'color'     => 'lime',
            'completed' => $this->order->paid_at !== null,
        ];

        // Order shipped
        $events[] = [
            'label'     => 'Pedido Enviado',
            'date'      => $this->order->shipped_at,
            'icon'      => 'truck',
            'color'     => 'indigo',
            'completed' => $this->order->shipped_at !== null,
        ];

        // Order delivered or cancelled
        if ($this->order->status === OrderStatus::Cancelled) {
            $events[] = [
                'label'     => 'Pedido Cancelado',
                'date'      => $this->order->cancelled_at,
                'icon'      => 'x-circle',
                'color'     => 'red',
                'completed' => $this->order->cancelled_at !== null,
            ];
        } elseif ($this->order->status === OrderStatus::Refunded) {
            $events[] = [
                'label'     => 'Pedido Reembolsado',
                'date'      => null,
                'icon'      => 'arrow-uturn-left',
                'color'     => 'purple',
                'completed' => true,
            ];
        } else {
            $events[] = [
                'label'     => 'Pedido Entregue',
                'date'      => $this->order->delivered_at,
                'icon'      => 'check-circle',
                'color'     => 'lime',
                'completed' => $this->order->delivered_at !== null,
            ];
        }

        return $events;
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-timeline', [
            'timelineEvents' => $this->timelineEvents,
        ]);
    }
}
