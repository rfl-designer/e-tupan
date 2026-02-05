<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Livewire;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Shipping\DTOs\ShippingOption;
use App\Domain\Shipping\Services\ShippingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, On, Validate};
use Livewire\Component;

class ShippingCalculator extends Component
{
    #[Validate('required|string|size:9', as: 'CEP', message: [
        'required' => 'Informe o CEP',
        'size'     => 'CEP deve ter 8 digitos',
    ])]
    public string $zipcode = '';

    public string $selectedOption = '';

    public bool $isCalculating = false;

    public bool $hasCalculated = false;

    public string $errorMessage = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $options = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $cart = $this->getCart();

        if ($cart !== null && $cart->shipping_zipcode !== null) {
            $this->zipcode        = $this->formatZipcode($cart->shipping_zipcode);
            $this->selectedOption = $cart->shipping_method ?? '';
            $this->calculateShipping();
        }
    }

    /**
     * Calculate shipping when zipcode changes.
     */
    public function updatedZipcode(string $value): void
    {
        // Apply mask
        $clean = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($clean) >= 5) {
            $this->zipcode = substr($clean, 0, 5) . '-' . substr($clean, 5, 3);
        } else {
            $this->zipcode = $clean;
        }

        // Reset options when zipcode changes
        if ($this->hasCalculated) {
            $this->hasCalculated  = false;
            $this->options        = [];
            $this->selectedOption = '';
        }
    }

    /**
     * Calculate shipping options.
     */
    public function calculateShipping(): void
    {
        $this->validate();

        $this->isCalculating = true;
        $this->errorMessage  = '';
        $this->options       = [];

        $cart = $this->getCart();

        if ($cart === null || $cart->isEmpty()) {
            $this->errorMessage  = 'Adicione itens ao carrinho para calcular o frete.';
            $this->isCalculating = false;

            return;
        }

        $shippingService = app(ShippingService::class);
        $shippingOptions = $shippingService->getOptionsForCart($cart, $this->zipcode);

        if (empty($shippingOptions)) {
            $this->errorMessage  = 'CEP invalido ou nao atendido.';
            $this->isCalculating = false;

            return;
        }

        $this->options = array_map(
            fn (ShippingOption $option) => $option->toArray(),
            $shippingOptions,
        );

        $this->hasCalculated = true;
        $this->isCalculating = false;
    }

    /**
     * Select a shipping option.
     */
    public function selectOption(string $code): void
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return;
        }

        $this->selectedOption = $code;

        $shippingService = app(ShippingService::class);
        $shippingService->applyToCart($cart, $code, $this->zipcode);

        $this->dispatch('cart-updated');
        $this->dispatch('shipping-selected', code: $code);
    }

    /**
     * Clear shipping selection.
     */
    public function clearShipping(): void
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return;
        }

        $shippingService = app(ShippingService::class);
        $shippingService->removeFromCart($cart);

        $this->selectedOption = '';
        $this->hasCalculated  = false;
        $this->options        = [];
        $this->zipcode        = '';

        $this->dispatch('cart-updated');
        $this->dispatch('shipping-cleared');
    }

    /**
     * Get the current selected option details.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function currentOption(): ?array
    {
        if ($this->selectedOption === '' || empty($this->options)) {
            return null;
        }

        return collect($this->options)->firstWhere('code', $this->selectedOption);
    }

    /**
     * Refresh when cart is updated.
     */
    #[On('cart-updated')]
    public function refreshOptions(): void
    {
        if ($this->hasCalculated && $this->zipcode !== '') {
            $this->calculateShipping();
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
     * Format zipcode with mask.
     */
    protected function formatZipcode(string $zipcode): string
    {
        $clean = preg_replace('/\D/', '', $zipcode) ?? '';

        if (strlen($clean) === 8) {
            return substr($clean, 0, 5) . '-' . substr($clean, 5, 3);
        }

        return $clean;
    }

    public function render(): View
    {
        return view('livewire.shipping.shipping-calculator');
    }
}
