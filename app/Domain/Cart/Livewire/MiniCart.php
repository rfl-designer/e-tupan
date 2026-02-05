<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Livewire;

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Cart\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class MiniCart extends Component
{
    /**
     * Dropdown open state.
     */
    public bool $isOpen = false;

    /**
     * Number of items to display in the dropdown.
     */
    public int $maxItems = 5;

    /**
     * Toggle dropdown open/closed.
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    /**
     * Close the dropdown.
     */
    public function close(): void
    {
        $this->isOpen = false;
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
    public function getItemCountProperty(): int
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        return $cart->itemCount();
    }

    /**
     * Get the cart items for display.
     *
     * @return Collection<int, CartItem>
     */
    public function getItemsProperty(): Collection
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return collect();
        }

        return $cart->items()
            ->with(['product.images', 'variant'])
            ->latest()
            ->limit($this->maxItems)
            ->get();
    }

    /**
     * Get the cart subtotal.
     */
    public function getSubtotalProperty(): int
    {
        $cart = $this->getCart();

        return $cart?->subtotal ?? 0;
    }

    /**
     * Check if cart is empty.
     */
    public function getIsEmptyProperty(): bool
    {
        return $this->itemCount === 0;
    }

    /**
     * Refresh cart data when cart is updated.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Properties are computed, so they will automatically refresh
    }

    public function render(): View
    {
        return view('livewire.cart.mini-cart');
    }
}
