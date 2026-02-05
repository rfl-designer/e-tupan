<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Checkout\Enums\PaymentMethod;
use App\Domain\Checkout\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CheckoutReview extends Component
{
    /**
     * Guest data.
     *
     * @var array<string, string|null>
     */
    public array $guestData = [];

    /**
     * Address data.
     *
     * @var array<string, string|null>
     */
    public array $addressData = [];

    /**
     * Shipping data.
     *
     * @var array<string, string|int|null>
     */
    public array $shippingData = [];

    /**
     * Payment method.
     */
    public ?string $paymentMethod = null;

    /**
     * Whether user is authenticated.
     */
    public bool $isAuthenticated = false;

    /**
     * Whether terms are accepted.
     */
    public bool $termsAccepted = false;

    /**
     * Processing state.
     */
    public bool $isProcessing = false;

    /**
     * Error message.
     */
    public ?string $error = null;

    /**
     * Mount the component.
     *
     * @param  array<string, string|null>  $guestData
     * @param  array<string, string|null>  $addressData
     * @param  array<string, string|int|null>  $shippingData
     */
    public function mount(
        array $guestData = [],
        array $addressData = [],
        array $shippingData = [],
        ?string $paymentMethod = null,
        bool $isAuthenticated = false,
    ): void {
        $this->guestData       = $guestData;
        $this->addressData     = $addressData;
        $this->shippingData    = $shippingData;
        $this->paymentMethod   = $paymentMethod;
        $this->isAuthenticated = $isAuthenticated;
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
     * Get cart items.
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
     * Get subtotal.
     */
    #[Computed]
    public function subtotal(): int
    {
        return $this->getCart()?->subtotal ?? 0;
    }

    /**
     * Get discount.
     */
    #[Computed]
    public function discount(): int
    {
        return $this->getCart()?->discount ?? 0;
    }

    /**
     * Get shipping cost.
     */
    #[Computed]
    public function shippingCost(): int
    {
        return (int) ($this->shippingData['shipping_cost'] ?? 0);
    }

    /**
     * Get total.
     */
    #[Computed]
    public function total(): int
    {
        return $this->subtotal - $this->discount + $this->shippingCost;
    }

    /**
     * Get payment method label.
     */
    #[Computed]
    public function paymentMethodLabel(): string
    {
        if ($this->paymentMethod === null) {
            return 'Nao selecionado';
        }

        return PaymentMethod::tryFrom($this->paymentMethod)?->label() ?? 'Desconhecido';
    }

    /**
     * Get customer name.
     */
    #[Computed]
    public function customerName(): string
    {
        if ($this->isAuthenticated) {
            return Auth::user()->name ?? '';
        }

        return $this->guestData['name'] ?? '';
    }

    /**
     * Get customer email.
     */
    #[Computed]
    public function customerEmail(): string
    {
        if ($this->isAuthenticated) {
            return Auth::user()->email ?? '';
        }

        return $this->guestData['email'] ?? '';
    }

    /**
     * Get formatted address.
     */
    #[Computed]
    public function formattedAddress(): string
    {
        $parts = [
            $this->addressData['shipping_street'] ?? '',
            $this->addressData['shipping_number'] ?? '',
        ];

        if (!empty($this->addressData['shipping_complement'])) {
            $parts[] = $this->addressData['shipping_complement'];
        }

        $parts[] = $this->addressData['shipping_neighborhood'] ?? '';
        $parts[] = ($this->addressData['shipping_city'] ?? '') . '/' . ($this->addressData['shipping_state'] ?? '');
        $parts[] = $this->addressData['shipping_zipcode'] ?? '';

        return implode(', ', array_filter($parts));
    }

    /**
     * Place the order.
     */
    public function placeOrder(): void
    {
        if (!$this->termsAccepted) {
            $this->error = 'Voce precisa aceitar os termos e condicoes para continuar.';

            return;
        }

        $this->isProcessing = true;
        $this->error        = null;

        try {
            $cart = $this->getCart();

            if ($cart === null || $cart->isEmpty()) {
                $this->error        = 'Seu carrinho esta vazio.';
                $this->isProcessing = false;

                return;
            }

            $checkoutService = app(CheckoutService::class);

            // Prepare order data
            $orderData = array_merge(
                $this->addressData,
                $this->shippingData,
            );

            if ($this->isAuthenticated) {
                $orderData['user_id'] = Auth::id();
            } else {
                $orderData['guest_email'] = $this->guestData['email'] ?? null;
                $orderData['guest_name']  = $this->guestData['name'] ?? null;
                $orderData['guest_cpf']   = $this->guestData['cpf'] ?? null;
                $orderData['guest_phone'] = $this->guestData['phone'] ?? null;
            }

            // Create the order
            $order = $checkoutService->createOrder($cart, $orderData);

            // Mark cart as converted
            $cart->status = CartStatus::Converted;
            $cart->save();

            // Redirect to payment processing or success page
            $this->redirect(route('checkout.success', ['order' => $order->id]));
        } catch (\Exception $e) {
            $this->error        = 'Erro ao processar seu pedido. Por favor, tente novamente.';
            $this->isProcessing = false;
        }
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-review');
    }
}
