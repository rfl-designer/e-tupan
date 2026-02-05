<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;

class CheckoutSummary extends Component
{
    /**
     * Shipping cost in cents.
     */
    public int $shippingCost = 0;

    /**
     * Shipping method name.
     */
    public ?string $shippingMethod = null;

    /**
     * Delivery days estimate.
     */
    public ?int $deliveryDays = null;

    /**
     * Address data for display.
     *
     * @var array<string, string|null>
     */
    public array $addressData = [];

    /**
     * Mount the component.
     *
     * @param  array<string, string|null>  $addressData
     */
    public function mount(
        int $shippingCost = 0,
        ?string $shippingMethod = null,
        ?int $deliveryDays = null,
        array $addressData = [],
    ): void {
        $this->shippingCost   = $shippingCost;
        $this->shippingMethod = $shippingMethod;
        $this->deliveryDays   = $deliveryDays;
        $this->addressData    = $addressData;
    }

    /**
     * Get current cart.
     */
    protected function getCart(): ?Cart
    {
        $cartService = new CartService();

        if (Auth::check()) {
            return $cartService->getForUser(Auth::id());
        }

        $sessionId = session()->getId();

        if ($sessionId !== '') {
            return $cartService->getForSession($sessionId);
        }

        return null;
    }

    /**
     * Get cart items for display.
     *
     * @return Collection<int, \App\Domain\Cart\Models\CartItem>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return collect();
        }

        return $cart->items()
            ->with(['product.images', 'variant'])
            ->get();
    }

    /**
     * Get cart subtotal.
     */
    #[Computed]
    public function subtotal(): int
    {
        return $this->getCart()?->subtotal ?? 0;
    }

    /**
     * Get cart discount.
     */
    #[Computed]
    public function discount(): int
    {
        return $this->getCart()?->discount ?? 0;
    }

    /**
     * Get cart total including shipping.
     */
    #[Computed]
    public function total(): int
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        return $cart->subtotal - $cart->discount + $this->shippingCost;
    }

    /**
     * Get item count.
     */
    #[Computed]
    public function itemCount(): int
    {
        return $this->getCart()?->itemCount() ?? 0;
    }

    /**
     * Refresh cart data when updated.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cartItems, $this->subtotal, $this->discount, $this->total, $this->itemCount);
    }

    /**
     * Update shipping data.
     *
     * @param  array<string, string|int|null>  $data
     */
    #[On('shipping-method-selected')]
    public function updateShipping(array $data): void
    {
        $this->shippingCost   = (int) ($data['shipping_cost'] ?? 0);
        $this->shippingMethod = $data['shipping_method'] ?? null;
        $this->deliveryDays   = isset($data['shipping_days']) ? (int) $data['shipping_days'] : null;

        unset($this->total);
    }

    /**
     * Update address data.
     *
     * @param  array<string, string|null>  $data
     */
    #[On('address-data-submitted')]
    public function updateAddress(array $data): void
    {
        $this->addressData = $data;
    }

    /**
     * Get formatted address.
     */
    #[Computed]
    public function formattedAddress(): ?string
    {
        if (empty($this->addressData['shipping_street'])) {
            return null;
        }

        $address = $this->addressData['shipping_street'];
        $address .= ', ' . ($this->addressData['shipping_number'] ?? '');

        if (!empty($this->addressData['shipping_complement'])) {
            $address .= ' - ' . $this->addressData['shipping_complement'];
        }

        $address .= ', ' . ($this->addressData['shipping_neighborhood'] ?? '');
        $address .= ', ' . ($this->addressData['shipping_city'] ?? '');
        $address .= ' - ' . ($this->addressData['shipping_state'] ?? '');
        $address .= ', ' . ($this->addressData['shipping_zipcode'] ?? '');

        return $address;
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-summary');
    }
}
