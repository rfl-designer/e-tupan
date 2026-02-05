<?php

declare(strict_types = 1);

use App\Domain\Shipping\DTOs\ShippingOption;
use App\Domain\Shipping\Services\FreeShippingService;

beforeEach(function (): void {
    config([
        'shipping.free_shipping.enabled'    => true,
        'shipping.free_shipping.min_amount' => 20000, // R$ 200.00
        'shipping.free_shipping.carrier'    => 'correios_pac',
    ]);
});

describe('FreeShippingService', function (): void {
    it('returns true when free shipping is enabled and cart meets minimum', function (): void {
        $service = new FreeShippingService();

        $result = $service->isEligible(25000); // R$ 250.00

        expect($result)->toBeTrue();
    });

    it('returns false when cart is below minimum amount', function (): void {
        $service = new FreeShippingService();

        $result = $service->isEligible(15000); // R$ 150.00

        expect($result)->toBeFalse();
    });

    it('returns false when free shipping is disabled', function (): void {
        config(['shipping.free_shipping.enabled' => false]);

        $service = new FreeShippingService();

        $result = $service->isEligible(25000);

        expect($result)->toBeFalse();
    });

    it('calculates amount remaining for free shipping', function (): void {
        $service = new FreeShippingService();

        $remaining = $service->amountRemainingForFreeShipping(15000);

        expect($remaining)->toBe(5000); // R$ 50.00 remaining
    });

    it('returns zero remaining when cart exceeds minimum', function (): void {
        $service = new FreeShippingService();

        $remaining = $service->amountRemainingForFreeShipping(25000);

        expect($remaining)->toBe(0);
    });

    it('returns null remaining when free shipping is disabled', function (): void {
        config(['shipping.free_shipping.enabled' => false]);

        $service = new FreeShippingService();

        $remaining = $service->amountRemainingForFreeShipping(15000);

        expect($remaining)->toBeNull();
    });

    it('applies free shipping to eligible carrier option', function (): void {
        $service = new FreeShippingService();

        $options = [
            new ShippingOption('1', 'Correios PAC', 1500, 5, 8, 'Correios'),
            new ShippingOption('2', 'Correios SEDEX', 2500, 2, 3, 'Correios'),
        ];

        $result = $service->applyFreeShipping($options, 25000);

        expect($result)->toHaveCount(2);
        expect($result[0]->price)->toBe(0);
        expect($result[0]->name)->toBe('Correios PAC (Frete Gratis)');
        expect($result[1]->price)->toBe(2500); // SEDEX unchanged
    });

    it('does not apply free shipping when cart is below minimum', function (): void {
        $service = new FreeShippingService();

        $options = [
            new ShippingOption('1', 'Correios PAC', 1500, 5, 8, 'Correios'),
        ];

        $result = $service->applyFreeShipping($options, 15000);

        expect($result[0]->price)->toBe(1500);
    });

    it('returns free shipping carrier code', function (): void {
        $service = new FreeShippingService();

        expect($service->getFreeShippingCarrier())->toBe('correios_pac');
    });

    it('returns minimum amount for free shipping', function (): void {
        $service = new FreeShippingService();

        expect($service->getMinimumAmount())->toBe(20000);
    });

    it('returns true when free shipping is enabled', function (): void {
        $service = new FreeShippingService();

        expect($service->isEnabled())->toBeTrue();
    });

    it('formats remaining amount correctly', function (): void {
        $service = new FreeShippingService();

        $formatted = $service->formatRemainingAmount(15000);

        expect($formatted)->toBe('Falta R$ 50,00 para frete gratis!');
    });

    it('returns empty message when already eligible', function (): void {
        $service = new FreeShippingService();

        $formatted = $service->formatRemainingAmount(25000);

        expect($formatted)->toBeNull();
    });
});
