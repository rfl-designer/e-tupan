<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Orders;

use App\Domain\Admin\Services\OrderService;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class OrderActions extends Component
{
    public Order $order;

    #[Validate('required|string|min:5|max:50')]
    public string $trackingNumber = '';

    public function __construct(
        private readonly OrderService $orderService = new OrderService(),
    ) {
    }

    public function mount(Order $order): void
    {
        $this->order          = $order;
        $this->trackingNumber = $order->tracking_number ?? '';
    }

    public function updateStatus(string $status): void
    {
        $orderStatus = OrderStatus::tryFrom($status);

        if ($orderStatus === null) {
            return;
        }

        if (!$this->orderService->updateStatus($this->order, $orderStatus)) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Nao foi possivel atualizar o status do pedido.'),
            ]);

            return;
        }

        $this->order->refresh();
        $this->dispatch('order-updated');
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => __('Status do pedido atualizado com sucesso.'),
        ]);
    }

    public function cancelOrder(): void
    {
        if (!$this->order->canBeCancelled()) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Este pedido nao pode ser cancelado.'),
            ]);

            return;
        }

        if (!$this->orderService->cancelOrder($this->order)) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Nao foi possivel cancelar o pedido.'),
            ]);

            return;
        }

        $this->order->refresh();
        $this->dispatch('order-updated');
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => __('Pedido cancelado com sucesso.'),
        ]);
    }

    public function refundOrder(): void
    {
        if (!$this->order->canBeRefunded()) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Este pedido nao pode ser reembolsado.'),
            ]);

            return;
        }

        $this->order->status = OrderStatus::Refunded;
        $this->order->save();
        $this->dispatch('order-updated');
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => __('Pedido marcado como reembolsado.'),
        ]);
    }

    public function markAsShipped(): void
    {
        $this->validate();

        if (!$this->orderService->markAsShipped($this->order, $this->trackingNumber)) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Nao foi possivel marcar o pedido como enviado.'),
            ]);

            return;
        }

        $this->order->refresh();
        $this->dispatch('order-updated');
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => __('Pedido marcado como enviado.'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableStatusesProperty(): array
    {
        return $this->orderService->getAvailableTransitions($this->order);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-actions', [
            'availableStatuses' => $this->availableStatuses,
        ]);
    }
}
