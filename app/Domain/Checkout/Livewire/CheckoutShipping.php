<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Shipping\DTOs\ShippingQuoteRequest;
use App\Domain\Shipping\Services\{FreeShippingService, ShippingService};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CheckoutShipping extends Component
{
    /**
     * Destination zipcode.
     */
    public string $zipcode = '';

    /**
     * Selected shipping method code.
     */
    public ?string $selectedMethod = null;

    /**
     * Selected shipping data.
     *
     * @var array<string, string|int|bool|null>
     */
    public array $shippingData = [];

    /**
     * Available shipping options.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $shippingOptions = [];

    /**
     * Loading state.
     */
    public bool $isLoading = false;

    /**
     * Error message.
     */
    public ?string $error = null;

    /**
     * Mount the component.
     *
     * @param  array<string, string|int|bool|null>  $shippingData
     */
    public function mount(string $zipcode = '', array $shippingData = []): void
    {
        $this->zipcode      = $zipcode;
        $this->shippingData = $shippingData;

        if (!empty($shippingData['shipping_method'])) {
            $this->selectedMethod = $shippingData['shipping_method'];
        }

        // Auto-calculate shipping if zipcode is provided
        if (!empty($this->zipcode)) {
            $this->calculateShipping();
        }
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
     * Get cart total weight.
     */
    #[Computed]
    public function cartWeight(): float
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        $cart->load(['items.product', 'items.variant']);

        $weight = 0;

        foreach ($cart->items as $item) {
            $itemWeight = $item->variant?->weight ?? $item->product->weight ?? 0;
            $weight += $itemWeight * $item->quantity;
        }

        return $weight;
    }

    /**
     * Get cart subtotal.
     */
    #[Computed]
    public function cartSubtotal(): int
    {
        $cart = $this->getCart();

        return $cart?->subtotal ?? 0;
    }

    /**
     * Calculate shipping options.
     */
    public function calculateShipping(): void
    {
        if (empty($this->zipcode) || strlen(preg_replace('/\D/', '', $this->zipcode)) !== 8) {
            $this->error = 'CEP invalido.';

            return;
        }

        $this->isLoading       = true;
        $this->error           = null;
        $this->shippingOptions = [];

        try {
            $cart = $this->getCart();

            if ($cart === null) {
                $this->error     = 'Carrinho nao encontrado.';
                $this->isLoading = false;

                return;
            }

            $cart->load(['items.product', 'items.variant']);

            $shippingService     = app(ShippingService::class);
            $freeShippingService = new FreeShippingService();

            $request = ShippingQuoteRequest::fromCart(
                $cart,
                $this->zipcode,
            );

            $options = $shippingService->calculateShipping($request);

            // Apply free shipping if eligible
            $options = $freeShippingService->applyFreeShipping($options, $cart->subtotal);

            $this->shippingOptions = collect($options)
                ->map(fn ($option) => [
                    'code'              => $option->code,
                    'name'              => $option->name,
                    'carrier'           => $option->carrier,
                    'price'             => $option->price,
                    'delivery_days'     => $option->deliveryDays(),
                    'delivery_days_min' => $option->deliveryDaysMin,
                    'delivery_days_max' => $option->deliveryDaysMax,
                    'delivery_estimate' => $option->deliveryTimeDescription(),
                    'is_free_shipping'  => $option->isFreeShipping,
                ])
                ->toArray();

            // Mark fastest and cheapest options
            $this->markSpecialOptions();

            // Auto-select cheapest option if none selected
            if ($this->selectedMethod === null && !empty($this->shippingOptions)) {
                $cheapest = collect($this->shippingOptions)->sortBy('price')->first();
                $this->selectShipping($cheapest['code']);
            }
        } catch (\Exception $e) {
            $this->error = 'Erro ao calcular frete. Tente novamente.';
        }

        $this->isLoading = false;
    }

    /**
     * Mark fastest and cheapest options with badges.
     */
    protected function markSpecialOptions(): void
    {
        if (empty($this->shippingOptions)) {
            return;
        }

        $options = collect($this->shippingOptions);

        // Find cheapest
        $cheapestPrice = $options->min('price');
        // Find fastest
        $fastestDays = $options->min('delivery_days');

        $this->shippingOptions = $options->map(function (array $option) use ($cheapestPrice, $fastestDays): array {
            $option['is_cheapest'] = $option['price'] === $cheapestPrice;
            $option['is_fastest']  = $option['delivery_days'] === $fastestDays;

            return $option;
        })->toArray();
    }

    /**
     * Select a shipping method.
     */
    public function selectShipping(string $code): void
    {
        $this->selectedMethod = $code;

        $option = collect($this->shippingOptions)->firstWhere('code', $code);

        if ($option !== null) {
            $this->shippingData = [
                'shipping_method'   => $code,
                'shipping_carrier'  => $option['carrier'],
                'shipping_cost'     => $option['price'],
                'shipping_days'     => $option['delivery_days'],
                'shipping_quote_id' => $code, // Using code as quote_id for now (ME will return actual ID)
                'is_free_shipping'  => $option['is_free_shipping'] ?? false,
            ];
        }
    }

    /**
     * Continue to payment.
     */
    public function continueToPayment(): void
    {
        if ($this->selectedMethod === null) {
            $this->error = 'Selecione uma opcao de entrega.';

            return;
        }

        $this->dispatch('shipping-method-selected', $this->shippingData);
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-shipping');
    }
}
