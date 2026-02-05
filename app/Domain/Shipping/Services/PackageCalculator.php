<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\DTOs\ShippingQuoteRequest;
use Illuminate\Support\Collection;

class PackageCalculator
{
    /**
     * Volumetric weight divisor (standard for domestic shipments).
     */
    private const int VOLUMETRIC_DIVISOR = 6000;

    /**
     * Calculate volumetric weight from dimensions.
     * Formula: (Length x Width x Height) / 6000
     *
     * @param  float|int  $length  Length in cm
     * @param  float|int  $width  Width in cm
     * @param  float|int  $height  Height in cm
     * @return float Weight in kg
     */
    public function calculateVolumetricWeight(float|int $length, float|int $width, float|int $height): float
    {
        return ($length * $width * $height) / self::VOLUMETRIC_DIVISOR;
    }

    /**
     * Get the billable weight (greater of real vs volumetric weight).
     *
     * @param  float  $realWeight  Real weight in kg
     * @param  float|int  $length  Length in cm
     * @param  float|int  $width  Width in cm
     * @param  float|int  $height  Height in cm
     * @return float Billable weight in kg
     */
    public function getBillableWeight(float $realWeight, float|int $length, float|int $width, float|int $height): float
    {
        $volumetricWeight = $this->calculateVolumetricWeight($length, $width, $height);

        return max($realWeight, $volumetricWeight);
    }

    /**
     * Calculate package dimensions from a single product.
     *
     * @return array{weight: int, length: int, width: int, height: int, billable_weight: int}
     */
    public function calculateFromProduct(Product|ProductVariant $product, int $quantity = 1): array
    {
        $defaults = $this->getDefaults();

        $weight = (float) ($product->weight ?? $defaults['weight']);
        $length = (int) ($product->length ?? $defaults['length']);
        $width  = (int) ($product->width ?? $defaults['width']);
        $height = (int) ($product->height ?? $defaults['height']);

        // Total weight = unit weight * quantity
        $totalWeight = (int) ($weight * 1000 * $quantity);

        // Total height = stacked items
        $totalHeight = $height * $quantity;

        // Calculate billable weight
        $billableWeight = $this->getBillableWeight(
            $weight * $quantity,
            $length,
            $width,
            $totalHeight,
        );

        return [
            'weight'          => $totalWeight,
            'length'          => $length,
            'width'           => $width,
            'height'          => $totalHeight,
            'billable_weight' => (int) ($billableWeight * 1000),
        ];
    }

    /**
     * Calculate package dimensions from collection of cart items.
     *
     * @param  Collection<int, array{product: Product|ProductVariant, quantity: int}>  $items
     * @return array{weight: int, length: int, width: int, height: int, billable_weight: int}
     */
    public function calculateFromCartItems(Collection $items): array
    {
        $defaults = $this->getDefaults();
        $limits   = $this->getLimits();

        $totalWeight = 0;
        $maxLength   = 0;
        $maxWidth    = 0;
        $totalHeight = 0;

        foreach ($items as $item) {
            $product  = $item['product'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if ($product === null) {
                continue;
            }

            $weight = (float) ($product->weight ?? $defaults['weight']);
            $length = (int) ($product->length ?? $defaults['length']);
            $width  = (int) ($product->width ?? $defaults['width']);
            $height = (int) ($product->height ?? $defaults['height']);

            $totalWeight += (int) ($weight * 1000 * $quantity);
            $maxLength = max($maxLength, $length);
            $maxWidth  = max($maxWidth, $width);
            $totalHeight += $height * $quantity;
        }

        // Enforce minimum dimensions
        $totalWeight = max($totalWeight, (int) ($defaults['weight'] * 1000));
        $maxLength   = max($maxLength, $limits['min_length']);
        $maxWidth    = max($maxWidth, $limits['min_width']);
        $totalHeight = max($totalHeight, $limits['min_height']);

        // Calculate billable weight
        $billableWeight = $this->getBillableWeight(
            $totalWeight / 1000,
            $maxLength,
            $maxWidth,
            $totalHeight,
        );

        return [
            'weight'          => $totalWeight,
            'length'          => $maxLength,
            'width'           => $maxWidth,
            'height'          => $totalHeight,
            'billable_weight' => (int) ($billableWeight * 1000),
        ];
    }

    /**
     * Calculate package dimensions from Cart model.
     *
     * @return array{weight: int, length: int, width: int, height: int, billable_weight: int}
     */
    public function calculateFromCart(Cart $cart): array
    {
        $defaults = $this->getDefaults();
        $limits   = $this->getLimits();

        $totalWeight = 0;
        $maxLength   = 0;
        $maxWidth    = 0;
        $totalHeight = 0;

        foreach ($cart->items as $item) {
            $product  = $item->variant ?? $item->product;
            $quantity = $item->quantity;

            if ($product === null) {
                continue;
            }

            $weight = (float) ($product->weight ?? $defaults['weight']);
            $length = (int) ($product->length ?? $defaults['length']);
            $width  = (int) ($product->width ?? $defaults['width']);
            $height = (int) ($product->height ?? $defaults['height']);

            $totalWeight += (int) ($weight * 1000 * $quantity);
            $maxLength = max($maxLength, $length);
            $maxWidth  = max($maxWidth, $width);
            $totalHeight += $height * $quantity;
        }

        // Enforce minimum dimensions
        $totalWeight = max($totalWeight, (int) ($defaults['weight'] * 1000));
        $maxLength   = max($maxLength, $limits['min_length']);
        $maxWidth    = max($maxWidth, $limits['min_width']);
        $totalHeight = max($totalHeight, $limits['min_height']);

        // Calculate billable weight
        $billableWeight = $this->getBillableWeight(
            $totalWeight / 1000,
            $maxLength,
            $maxWidth,
            $totalHeight,
        );

        return [
            'weight'          => $totalWeight,
            'length'          => $maxLength,
            'width'           => $maxWidth,
            'height'          => $totalHeight,
            'billable_weight' => (int) ($billableWeight * 1000),
        ];
    }

    /**
     * Validate dimensions against carrier limits.
     *
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateDimensions(int $weight, int $length, int $width, int $height): array
    {
        $limits = $this->getLimits();
        $errors = [];

        // Convert weight from grams to kg for comparison
        $weightInKg = $weight / 1000;

        if ($weightInKg > $limits['max_weight']) {
            $errors[] = 'weight';
        }

        if ($length > $limits['max_length']) {
            $errors[] = 'length';
        }

        if ($width > $limits['max_width']) {
            $errors[] = 'width';
        }

        if ($height > $limits['max_height']) {
            $errors[] = 'height';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate package dimensions from Order model.
     *
     * @return array{weight: float, length: int, width: int, height: int, billable_weight: float}
     */
    public function calculateForOrder(Order $order): array
    {
        $defaults = $this->getDefaults();
        $limits   = $this->getLimits();

        $totalWeight = 0;
        $maxLength   = 0;
        $maxWidth    = 0;
        $totalHeight = 0;

        foreach ($order->items as $item) {
            $product  = $item->variant ?? $item->product;
            $quantity = $item->quantity;

            if ($product === null) {
                continue;
            }

            $weight = (float) ($product->weight ?? $defaults['weight']);
            $length = (int) ($product->length ?? $defaults['length']);
            $width  = (int) ($product->width ?? $defaults['width']);
            $height = (int) ($product->height ?? $defaults['height']);

            $totalWeight += $weight * $quantity;
            $maxLength = max($maxLength, $length);
            $maxWidth  = max($maxWidth, $width);
            $totalHeight += $height * $quantity;
        }

        // Enforce minimum dimensions
        $totalWeight = max($totalWeight, $defaults['weight']);
        $maxLength   = max($maxLength, $limits['min_length']);
        $maxWidth    = max($maxWidth, $limits['min_width']);
        $totalHeight = max($totalHeight, $limits['min_height']);

        // Calculate billable weight
        $billableWeight = $this->getBillableWeight(
            $totalWeight,
            $maxLength,
            $maxWidth,
            $totalHeight,
        );

        return [
            'weight'          => $totalWeight,
            'length'          => $maxLength,
            'width'           => $maxWidth,
            'height'          => $totalHeight,
            'billable_weight' => $billableWeight,
        ];
    }

    /**
     * Create a ShippingQuoteRequest from a Cart model.
     */
    public function createShippingQuoteRequest(Cart $cart, string $zipcode): ShippingQuoteRequest
    {
        $package = $this->calculateFromCart($cart);

        return new ShippingQuoteRequest(
            destinationZipcode: $zipcode,
            totalWeight: $package['billable_weight'],
            totalLength: $package['length'],
            totalWidth: $package['width'],
            totalHeight: $package['height'],
            totalValue: $cart->subtotal,
        );
    }

    /**
     * Get default package dimensions from config.
     *
     * @return array{weight: float, length: int, width: int, height: int}
     */
    private function getDefaults(): array
    {
        return [
            'weight' => (float) config('shipping.defaults.weight', 0.3),
            'length' => (int) config('shipping.defaults.length', 16),
            'width'  => (int) config('shipping.defaults.width', 11),
            'height' => (int) config('shipping.defaults.height', 2),
        ];
    }

    /**
     * Get package limits from config.
     *
     * @return array{max_weight: int, max_length: int, max_width: int, max_height: int, min_length: int, min_width: int, min_height: int}
     */
    private function getLimits(): array
    {
        return [
            'max_weight' => (int) config('shipping.limits.max_weight', 30),
            'max_length' => (int) config('shipping.limits.max_length', 100),
            'max_width'  => (int) config('shipping.limits.max_width', 100),
            'max_height' => (int) config('shipping.limits.max_height', 100),
            'min_length' => (int) config('shipping.limits.min_length', 11),
            'min_width'  => (int) config('shipping.limits.min_width', 2),
            'min_height' => (int) config('shipping.limits.min_height', 2),
        ];
    }
}
