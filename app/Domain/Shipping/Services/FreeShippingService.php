<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\DTOs\ShippingOption;

class FreeShippingService
{
    /**
     * Check if free shipping is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('shipping.free_shipping.enabled', false);
    }

    /**
     * Get the minimum amount required for free shipping (in cents).
     */
    public function getMinimumAmount(): int
    {
        return (int) config('shipping.free_shipping.min_amount', 0);
    }

    /**
     * Get the carrier code that qualifies for free shipping.
     */
    public function getFreeShippingCarrier(): ?string
    {
        return config('shipping.free_shipping.carrier');
    }

    /**
     * Check if a cart amount is eligible for free shipping.
     */
    public function isEligible(int $cartSubtotal): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $cartSubtotal >= $this->getMinimumAmount();
    }

    /**
     * Calculate the amount remaining to qualify for free shipping.
     *
     * @return int|null Amount in cents, or null if free shipping is disabled
     */
    public function amountRemainingForFreeShipping(int $cartSubtotal): ?int
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $remaining = $this->getMinimumAmount() - $cartSubtotal;

        return max(0, $remaining);
    }

    /**
     * Format the remaining amount message for display.
     */
    public function formatRemainingAmount(int $cartSubtotal): ?string
    {
        $remaining = $this->amountRemainingForFreeShipping($cartSubtotal);

        if ($remaining === null || $remaining === 0) {
            return null;
        }

        $formattedAmount = 'R$ ' . number_format($remaining / 100, 2, ',', '.');

        return "Falta {$formattedAmount} para frete gratis!";
    }

    /**
     * Apply free shipping to eligible shipping options.
     *
     * @param  array<ShippingOption>  $options
     * @return array<ShippingOption>
     */
    public function applyFreeShipping(array $options, int $cartSubtotal): array
    {
        if (!$this->isEligible($cartSubtotal)) {
            return $options;
        }

        $freeCarrier = $this->getFreeShippingCarrier();

        return array_map(function (ShippingOption $option) use ($freeCarrier): ShippingOption {
            if (!$this->isCarrierEligible($option, $freeCarrier)) {
                return $option;
            }

            return new ShippingOption(
                code: $option->code,
                name: $option->name . ' (Frete Gratis)',
                price: 0,
                deliveryDaysMin: $option->deliveryDaysMin,
                deliveryDaysMax: $option->deliveryDaysMax,
                carrier: $option->carrier,
                isFreeShipping: true,
            );
        }, $options);
    }

    /**
     * Check if a shipping option is eligible for free shipping based on carrier.
     */
    private function isCarrierEligible(ShippingOption $option, ?string $freeCarrier): bool
    {
        if ($freeCarrier === null) {
            return false;
        }

        $carrierMapping = [
            'correios_pac'   => ['PAC', 'Correios PAC'],
            'correios_sedex' => ['SEDEX', 'Correios SEDEX'],
            'jadlog_package' => ['Jadlog Package', 'Jadlog .Package'],
            'jadlog_com'     => ['Jadlog .Com', 'Jadlog.Com'],
        ];

        $eligibleNames = $carrierMapping[$freeCarrier] ?? [];

        foreach ($eligibleNames as $name) {
            if (stripos($option->name, $name) !== false) {
                return true;
            }
        }

        return false;
    }
}
