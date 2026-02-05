<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Cart\Models\Cart;
use App\Domain\Shipping\Contracts\ShippingProviderInterface;
use App\Domain\Shipping\DTOs\{ShippingOption, ShippingQuoteRequest};

class ShippingService
{
    public function __construct(
        private ShippingProviderInterface $provider,
    ) {
    }

    /**
     * Get shipping options for a cart.
     *
     * @return array<ShippingOption>
     */
    public function getOptionsForCart(Cart $cart, string $zipcode): array
    {
        $cart->loadMissing('items.product');

        $cartItems = $cart->items->map(function ($item) {
            return [
                'product' => [
                    'weight' => $item->product?->weight ?? 0.3,
                    'length' => $item->product?->length ?? 16,
                    'width'  => $item->product?->width ?? 11,
                    'height' => $item->product?->height ?? 2,
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $request = ShippingQuoteRequest::fromCartItems(
            zipcode: $zipcode,
            cartItems: $cartItems,
            totalValue: $cart->subtotal,
        );

        return $this->provider->calculate($request);
    }

    /**
     * Apply shipping to cart.
     */
    public function applyToCart(Cart $cart, string $optionCode, string $zipcode): Cart
    {
        $options = $this->getOptionsForCart($cart, $zipcode);

        $selectedOption = collect($options)->first(
            fn (ShippingOption $option) => $option->code === $optionCode,
        );

        if ($selectedOption === null) {
            throw new \InvalidArgumentException("Opcao de frete '$optionCode' nao encontrada.");
        }

        $cart->shipping_zipcode = $zipcode;
        $cart->shipping_method  = $selectedOption->code;
        $cart->shipping_cost    = $selectedOption->price;
        $cart->shipping_days    = $selectedOption->deliveryDaysMax;
        $cart->calculateTotals();
        $cart->save();

        return $cart;
    }

    /**
     * Remove shipping from cart.
     */
    public function removeFromCart(Cart $cart): Cart
    {
        $cart->shipping_zipcode = null;
        $cart->shipping_method  = null;
        $cart->shipping_cost    = null;
        $cart->shipping_days    = null;
        $cart->calculateTotals();
        $cart->save();

        return $cart;
    }

    /**
     * Check if provider is available.
     */
    public function isProviderAvailable(): bool
    {
        return $this->provider->isAvailable();
    }

    /**
     * Get provider name.
     */
    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    /**
     * Calculate shipping options from a request DTO.
     *
     * @return array<ShippingOption>
     */
    public function calculateShipping(ShippingQuoteRequest $request): array
    {
        return $this->provider->calculate($request);
    }
}
