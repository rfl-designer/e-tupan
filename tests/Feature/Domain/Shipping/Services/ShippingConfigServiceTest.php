<?php

declare(strict_types = 1);

use App\Domain\Shipping\Enums\ShippingCarrier;
use App\Domain\Shipping\Models\ShippingSetting;
use App\Domain\Shipping\Services\ShippingConfigService;

beforeEach(function (): void {
    $this->service = new ShippingConfigService();
});

describe('ShippingConfigService', function (): void {
    describe('carriers configuration', function (): void {
        it('returns default carriers config from config file', function (): void {
            $config = $this->service->getCarriersConfig();

            expect($config)->toBeArray()
                ->and($config)->toHaveKey('correios_pac')
                ->and($config['correios_pac'])->toHaveKeys(['enabled', 'additional_days', 'price_margin', 'position']);
        });

        it('returns enabled carriers in order', function (): void {
            ShippingSetting::set('carriers_config', [
                'correios_pac'   => ['enabled' => true, 'additional_days' => 0, 'price_margin' => 0, 'position' => 1],
                'correios_sedex' => ['enabled' => true, 'additional_days' => 0, 'price_margin' => 0, 'position' => 0],
                'jadlog_package' => ['enabled' => false, 'additional_days' => 0, 'price_margin' => 0, 'position' => 2],
            ], 'json', 'carriers');

            cache()->forget('shipping:carriers_config');

            $carriers = $this->service->getEnabledCarriers();

            expect($carriers)->toHaveCount(2)
                ->and($carriers[0])->toBe(ShippingCarrier::CorreiosSedex)
                ->and($carriers[1])->toBe(ShippingCarrier::CorreiosPac);
        });

        it('can update carrier configuration', function (): void {
            $this->service->updateCarriersConfig([
                'correios_pac' => ['enabled' => true, 'additional_days' => 2],
            ]);

            cache()->forget('shipping:carriers_config');

            $config = $this->service->getCarriersConfig();

            expect($config['correios_pac']['enabled'])->toBeTrue()
                ->and($config['correios_pac']['additional_days'])->toBe(2);
        });

        it('can enable and disable carrier', function (): void {
            $this->service->setCarrierEnabled(ShippingCarrier::CorreiosPac, true);
            cache()->forget('shipping:carriers_config');

            $config = $this->service->getCarrierConfig(ShippingCarrier::CorreiosPac);
            expect($config['enabled'])->toBeTrue();

            $this->service->setCarrierEnabled(ShippingCarrier::CorreiosPac, false);
            cache()->forget('shipping:carriers_config');

            $config = $this->service->getCarrierConfig(ShippingCarrier::CorreiosPac);
            expect($config['enabled'])->toBeFalse();
        });

        it('can update carrier positions', function (): void {
            $this->service->updateCarrierPositions([
                'correios_pac'   => 2,
                'correios_sedex' => 1,
            ]);

            cache()->forget('shipping:carriers_config');

            $config = $this->service->getCarriersConfig();

            expect($config['correios_pac']['position'])->toBe(2)
                ->and($config['correios_sedex']['position'])->toBe(1);
        });
    });

    describe('handling days', function (): void {
        it('returns default handling days from config', function (): void {
            config(['shipping.handling_days' => 2]);

            $days = $this->service->getHandlingDays();

            expect($days)->toBe(2);
        });

        it('returns handling days from database if set', function (): void {
            ShippingSetting::set('handling_days', 3, 'integer', 'general');

            $days = $this->service->getHandlingDays();

            expect($days)->toBe(3);
        });

        it('can set handling days', function (): void {
            $this->service->setHandlingDays(5);

            $days = $this->service->getHandlingDays();

            expect($days)->toBe(5);
        });
    });

    describe('free shipping configuration', function (): void {
        it('returns default free shipping config from config file', function (): void {
            config([
                'shipping.free_shipping.enabled'    => true,
                'shipping.free_shipping.min_amount' => 15000,
                'shipping.free_shipping.carrier'    => 'correios_pac',
            ]);

            $config = $this->service->getFreeShippingConfig();

            expect($config['enabled'])->toBeTrue()
                ->and($config['min_amount'])->toBe(15000)
                ->and($config['carrier'])->toBe('correios_pac');
        });

        it('can update free shipping config', function (): void {
            $this->service->updateFreeShippingConfig([
                'enabled'    => true,
                'min_amount' => 20000,
                'carrier'    => 'correios_sedex',
            ]);

            $config = $this->service->getFreeShippingConfig();

            expect($config['enabled'])->toBeTrue()
                ->and($config['min_amount'])->toBe(20000)
                ->and($config['carrier'])->toBe('correios_sedex');
        });
    });

    describe('origin address', function (): void {
        it('returns origin address from config', function (): void {
            config([
                'shipping.origin.zipcode' => '01310100',
                'shipping.origin.city'    => 'Sao Paulo',
                'shipping.origin.state'   => 'SP',
            ]);

            $address = $this->service->getOriginAddress();

            expect($address['zipcode'])->toBe('01310100')
                ->and($address['city'])->toBe('Sao Paulo')
                ->and($address['state'])->toBe('SP');
        });

        it('can update origin address', function (): void {
            $this->service->updateOriginAddress([
                'zipcode' => '22041080',
                'city'    => 'Rio de Janeiro',
                'state'   => 'RJ',
            ]);

            $address = $this->service->getOriginAddress();

            expect($address['zipcode'])->toBe('22041080')
                ->and($address['city'])->toBe('Rio de Janeiro')
                ->and($address['state'])->toBe('RJ');
        });
    });
});
