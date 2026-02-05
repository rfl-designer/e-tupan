<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Orders;

use App\Domain\Admin\Models\OrderNote;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\{Computed, Validate};
use Livewire\Component;

class OrderNotes extends Component
{
    public Order $order;

    #[Validate('required|string|min:3|max:1000')]
    public string $note = '';

    public bool $isCustomerVisible = false;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    /**
     * @return Collection<int, OrderNote>
     */
    #[Computed]
    public function notes(): Collection
    {
        return OrderNote::query()
            ->where('order_id', $this->order->id)
            ->with('admin')
            ->orderByDesc('created_at')
            ->get();
    }

    public function addNote(): void
    {
        $this->validate();

        OrderNote::create([
            'order_id'            => $this->order->id,
            'admin_id'            => auth('admin')->id(),
            'note'                => $this->note,
            'is_customer_visible' => $this->isCustomerVisible,
        ]);

        $this->reset(['note', 'isCustomerVisible']);
        unset($this->notes);

        $this->dispatch('note-added');
    }

    public function deleteNote(int $noteId): void
    {
        $note = OrderNote::query()
            ->where('order_id', $this->order->id)
            ->findOrFail($noteId);

        $note->delete();
        unset($this->notes);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-notes', [
            'notes' => $this->notes,
        ]);
    }
}
