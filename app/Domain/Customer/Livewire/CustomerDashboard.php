<?php

declare(strict_types = 1);

namespace App\Domain\Customer\Livewire;

use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Models\Address;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Minha Conta')]
class CustomerDashboard extends Component
{
    public User $user;

    public int $addressCount;

    public ?Address $defaultAddress;

    /** @var Collection<int, Order> */
    public Collection $recentOrders;

    public int $ordersCount;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user                 = Auth::user();
        $this->user           = $user;
        $this->addressCount   = $this->user->addresses()->count();
        $this->defaultAddress = $this->user->defaultAddress();

        $this->recentOrders = Order::query()
            ->forUser($this->user->id)
            ->latest('placed_at')
            ->limit(3)
            ->get();

        $this->ordersCount = Order::query()
            ->forUser($this->user->id)
            ->count();
    }

    /**
     * Get the masked CPF for display.
     */
    public function getMaskedCpfProperty(): ?string
    {
        if (!$this->user->cpf) {
            return null;
        }

        // Format: ***.***.XXX-XX (show last 6 digits)
        $cpf = preg_replace('/\D/', '', $this->user->cpf);

        if (strlen($cpf) !== 11) {
            return $this->user->cpf;
        }

        return '***.' . '***' . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.customer.dashboard');
    }
}
