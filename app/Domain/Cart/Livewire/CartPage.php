<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Livewire;

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Cart\Services\{CartService, CartValidationService};
use App\Domain\Shipping\Services\FreeShippingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;

class CartPage extends Component
{
    /**
     * Validation alerts to display.
     *
     * @var list<string>
     */
    public array $validationAlerts = [];

    public function mount(): void
    {
        $this->validateCartOnLoad();
    }

    /**
     * Validate cart when page loads.
     */
    protected function validateCartOnLoad(): void
    {
        $cart = $this->getCart();

        if ($cart === null || $cart->isEmpty()) {
            return;
        }

        $validationService = new CartValidationService();
        $result            = $validationService->validateCart($cart);

        $this->validationAlerts = $result['alerts'];
    }

    /**
     * Dismiss validation alerts.
     */
    public function dismissAlerts(): void
    {
        $this->validationAlerts = [];
    }

    /**
     * Get the current cart.
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
     * Get the total number of items in the cart.
     */
    #[Computed]
    public function itemCount(): int
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        return $cart->itemCount();
    }

    /**
     * Get the unique product count.
     */
    #[Computed]
    public function uniqueItemCount(): int
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        return $cart->uniqueItemCount();
    }

    /**
     * Get the cart items for display.
     *
     * @return Collection<int, CartItem>
     */
    #[Computed]
    public function items(): Collection
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
     * Get the cart subtotal.
     */
    #[Computed]
    public function subtotal(): int
    {
        $cart = $this->getCart();

        return $cart?->subtotal ?? 0;
    }

    /**
     * Get the cart discount.
     */
    #[Computed]
    public function discount(): int
    {
        $cart = $this->getCart();

        return $cart?->discount ?? 0;
    }

    /**
     * Get the shipping cost.
     */
    #[Computed]
    public function shippingCost(): ?int
    {
        $cart = $this->getCart();

        return $cart?->shipping_cost;
    }

    /**
     * Get the cart total.
     */
    #[Computed]
    public function total(): int
    {
        $cart = $this->getCart();

        return $cart?->total ?? 0;
    }

    /**
     * Check if cart is empty.
     */
    #[Computed]
    public function isEmpty(): bool
    {
        return $this->itemCount === 0;
    }

    /**
     * Get free shipping message.
     */
    #[Computed]
    public function freeShippingMessage(): ?string
    {
        $freeShippingService = new FreeShippingService();

        return $freeShippingService->formatRemainingAmount($this->subtotal);
    }

    /**
     * Check if eligible for free shipping.
     */
    #[Computed]
    public function isEligibleForFreeShipping(): bool
    {
        $freeShippingService = new FreeShippingService();

        return $freeShippingService->isEligible($this->subtotal);
    }

    /**
     * Refresh cart data when cart is updated.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->itemCount, $this->uniqueItemCount, $this->items, $this->subtotal, $this->discount, $this->shippingCost, $this->total, $this->isEmpty, $this->freeShippingMessage, $this->isEligibleForFreeShipping);
    }

    public function render(): View
    {
        return view('livewire.cart.cart-page');
    }
}
