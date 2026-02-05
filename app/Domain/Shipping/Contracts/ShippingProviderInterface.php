<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\DTOs\{ShippingOption, ShippingQuoteRequest};

interface ShippingProviderInterface
{
    /**
     * Calculate shipping options for a given request.
     *
     * @return array<ShippingOption>
     */
    public function calculate(ShippingQuoteRequest $request): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool;
}
