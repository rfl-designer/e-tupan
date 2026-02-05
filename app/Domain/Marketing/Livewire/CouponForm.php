<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Livewire;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Marketing\Exceptions\CouponException;
use App\Domain\Marketing\Models\Coupon;
use App\Domain\Marketing\Services\CouponService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;

class CouponForm extends Component
{
    public string $couponCode = '';

    public string $errorMessage = '';

    public string $successMessage = '';

    public bool $isApplying = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        // Load existing coupon code if cart has one
        $cart = $this->getCart();

        if ($cart?->coupon !== null) {
            $this->couponCode = $cart->coupon->code;
        }
    }

    /**
     * Apply coupon to cart.
     */
    public function applyCoupon(): void
    {
        $this->resetMessages();

        if (trim($this->couponCode) === '') {
            $this->errorMessage = 'Digite um codigo de cupom.';

            return;
        }

        $cart = $this->getCart();

        if ($cart === null || $cart->isEmpty()) {
            $this->errorMessage = 'Adicione itens ao carrinho primeiro.';

            return;
        }

        $this->isApplying = true;

        try {
            $couponService = app(CouponService::class);
            $couponService->apply(
                code: $this->couponCode,
                cart: $cart,
                userId: Auth::id(),
            );

            $this->successMessage = 'Cupom aplicado com sucesso!';
            $this->dispatch('cart-updated');
            $this->dispatch('coupon-applied');
        } catch (CouponException $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isApplying = false;
        }
    }

    /**
     * Remove coupon from cart.
     */
    public function removeCoupon(): void
    {
        $this->resetMessages();

        $cart = $this->getCart();

        if ($cart === null) {
            return;
        }

        try {
            $couponService = app(CouponService::class);
            $couponService->remove($cart);

            $this->couponCode     = '';
            $this->successMessage = 'Cupom removido com sucesso.';
            $this->dispatch('cart-updated');
            $this->dispatch('coupon-removed');
        } catch (CouponException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Get the applied coupon.
     */
    #[Computed]
    public function appliedCoupon(): ?Coupon
    {
        $cart = $this->getCart();

        return $cart?->coupon;
    }

    /**
     * Get the discount amount.
     */
    #[Computed]
    public function discountAmount(): int
    {
        $cart = $this->getCart();

        return $cart?->discount ?? 0;
    }

    /**
     * Check if a coupon is applied.
     */
    #[Computed]
    public function hasCoupon(): bool
    {
        return $this->appliedCoupon !== null;
    }

    /**
     * Refresh when cart is updated (e.g., shipping changes).
     */
    #[On('cart-updated')]
    public function refreshCoupon(): void
    {
        unset($this->appliedCoupon, $this->discountAmount, $this->hasCoupon);

        // Recalculate discount if coupon is applied
        $cart = $this->getCart();

        if ($cart?->coupon_id !== null) {
            $couponService = app(CouponService::class);
            $couponService->recalculateDiscount($cart);
        }
    }

    /**
     * Refresh when shipping is selected.
     */
    #[On('shipping-selected')]
    public function onShippingSelected(): void
    {
        $this->refreshCoupon();
    }

    /**
     * Refresh when shipping is cleared.
     */
    #[On('shipping-cleared')]
    public function onShippingCleared(): void
    {
        // If free shipping coupon is applied, it may need to be removed
        $cart = $this->getCart();

        if ($cart?->coupon?->type?->value === 'free_shipping') {
            try {
                $couponService = app(CouponService::class);
                $couponService->remove($cart);
                $this->couponCode   = '';
                $this->errorMessage = 'O cupom de frete gratis foi removido porque o frete foi alterado.';
                $this->dispatch('cart-updated');
            } catch (CouponException) {
                // Ignore
            }
        }
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
     * Reset messages.
     */
    protected function resetMessages(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';
    }

    public function render(): View
    {
        return view('livewire.marketing.coupon-form');
    }
}
