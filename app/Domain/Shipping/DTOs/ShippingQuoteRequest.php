<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

use App\Domain\Cart\Models\Cart;

readonly class ShippingQuoteRequest
{
    public function __construct(
        public string $destinationZipcode,
        public int $totalWeight,
        public int $totalLength,
        public int $totalWidth,
        public int $totalHeight,
        public int $totalValue,
        public ?string $originZipcode = null,
    ) {
    }

    /**
     * Create from cart data.
     *
     * @param  array<string, mixed>  $cartItems
     */
    public static function fromCartItems(string $zipcode, array $cartItems, int $totalValue): self
    {
        $totalWeight = 0;
        $totalLength = 0;
        $totalWidth  = 0;
        $totalHeight = 0;

        foreach ($cartItems as $item) {
            $product  = $item['product'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if ($product === null) {
                continue;
            }

            $totalWeight += (int) (($product['weight'] ?? 0.3) * 1000 * $quantity);
            $totalLength = max($totalLength, (int) ($product['length'] ?? 16));
            $totalWidth  = max($totalWidth, (int) ($product['width'] ?? 11));
            $totalHeight += (int) (($product['height'] ?? 2) * $quantity);
        }

        // Minimum dimensions
        $totalWeight = max($totalWeight, 300);
        $totalLength = max($totalLength, 16);
        $totalWidth  = max($totalWidth, 11);
        $totalHeight = max($totalHeight, 2);

        return new self(
            destinationZipcode: $zipcode,
            totalWeight: $totalWeight,
            totalLength: $totalLength,
            totalWidth: $totalWidth,
            totalHeight: $totalHeight,
            totalValue: $totalValue,
        );
    }

    /**
     * Create from a Cart model.
     */
    public static function fromCart(Cart $cart, string $zipcode): self
    {
        $totalWeight = 0;
        $totalLength = 0;
        $totalWidth  = 0;
        $totalHeight = 0;

        foreach ($cart->items as $item) {
            $product  = $item->variant ?? $item->product;
            $quantity = $item->quantity;

            if ($product === null) {
                continue;
            }

            $weight = (int) (($product->weight ?? 0.3) * 1000);
            $totalWeight += $weight * $quantity;
            $totalLength = max($totalLength, (int) ($product->length ?? 16));
            $totalWidth  = max($totalWidth, (int) ($product->width ?? 11));
            $totalHeight += (int) (($product->height ?? 2) * $quantity);
        }

        // Minimum dimensions
        $totalWeight = max($totalWeight, 300);
        $totalLength = max($totalLength, 16);
        $totalWidth  = max($totalWidth, 11);
        $totalHeight = max($totalHeight, 2);

        return new self(
            destinationZipcode: $zipcode,
            totalWeight: $totalWeight,
            totalLength: $totalLength,
            totalWidth: $totalWidth,
            totalHeight: $totalHeight,
            totalValue: $cart->subtotal,
        );
    }

    /**
     * Get zipcode without mask.
     */
    public function cleanZipcode(): string
    {
        return preg_replace('/\D/', '', $this->destinationZipcode) ?? '';
    }
}
