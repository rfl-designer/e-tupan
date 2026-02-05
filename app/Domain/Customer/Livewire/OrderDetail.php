<?php

declare(strict_types = 1);

namespace App\Domain\Customer\Livewire;

use App\Domain\Admin\Models\OrderNote;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderDetail extends Component
{
    public Order $order;

    /** @var Collection<int, \App\Domain\Checkout\Models\OrderItem> */
    public Collection $items;

    /** @var Collection<int, OrderNote> */
    public Collection $customerNotes;

    /**
     * Mount the component.
     */
    public function mount(Order $order): void
    {
        $this->ensureUserCanViewOrder($order);
        $this->order = $order;
        $this->loadOrderItems();
        $this->loadCustomerNotes();
    }

    /**
     * Load order items with eager loading.
     */
    protected function loadOrderItems(): void
    {
        /** @var Collection<int, \App\Domain\Checkout\Models\OrderItem> $items */
        $items = $this->order->items()
            ->with(['product.images' => fn ($query) => $query->where('is_primary', true)])
            ->get();

        $this->items = $items;
    }

    /**
     * Load customer visible notes.
     */
    protected function loadCustomerNotes(): void
    {
        /** @var Collection<int, OrderNote> $notes */
        $notes = $this->order->notes()
            ->customerVisible()
            ->latest()
            ->get();

        $this->customerNotes = $notes;
    }

    /**
     * Ensure the authenticated user can view the order.
     */
    protected function ensureUserCanViewOrder(Order $order): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user === null || $order->user_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * Get the page title.
     */
    public function getTitle(): string
    {
        return "Pedido {$this->order->order_number}";
    }

    /**
     * Format price in BRL.
     */
    public function formatPrice(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }

    /**
     * Format shipping method name.
     */
    public function formatShippingMethod(?string $method): string
    {
        if ($method === null) {
            return '';
        }

        return match (strtolower($method)) {
            'pac'   => 'PAC',
            'sedex' => 'SEDEX',
            default => ucfirst($method),
        };
    }

    /**
     * Get formatted shipping days text.
     */
    public function getShippingDaysText(): string
    {
        $days = $this->order->shipping_days;

        if ($days === null) {
            return '';
        }

        return $days === 1 ? '1 dia útil' : "{$days} dias úteis";
    }

    /**
     * Check if the tracking section should be displayed.
     */
    public function shouldShowTrackingSection(): bool
    {
        /** @var OrderStatus $status */
        $status = $this->order->status;

        return $this->order->shipped_at !== null
            || $status === OrderStatus::Shipped
            || $status === OrderStatus::Completed;
    }

    /**
     * Get the public tracking URL.
     */
    public function getTrackingUrl(): ?string
    {
        if ($this->order->tracking_number === null) {
            return null;
        }

        return route('tracking.show', $this->order->tracking_number);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.customer.order-detail')
            ->title($this->getTitle());
    }
}
