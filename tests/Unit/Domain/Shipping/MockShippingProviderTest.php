<?php

declare(strict_types = 1);

use App\Domain\Shipping\DTOs\ShippingQuoteRequest;
use App\Domain\Shipping\Providers\MockShippingProvider;

describe('MockShippingProvider', function () {
    beforeEach(function () {
        $this->provider = new MockShippingProvider();
    });

    describe('basic functionality', function () {
        it('returns provider name', function () {
            expect($this->provider->getName())->toBe('Mock Shipping Provider');
        });

        it('is always available', function () {
            expect($this->provider->isAvailable())->toBeTrue();
        });
    });

    describe('shipping calculation', function () {
        it('returns empty array for invalid zipcode', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '123',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            expect($options)->toBeEmpty();
        });

        it('returns PAC and SEDEX options for valid zipcode', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            expect($options)->toHaveCount(3) // PAC, SEDEX, SEDEX 10 (capital)
                ->and($options[0]->code)->toBe('pac')
                ->and($options[1]->code)->toBe('sedex')
                ->and($options[2]->code)->toBe('sedex_10');
        });

        it('returns only PAC and SEDEX for non-capital zipcode', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '13500-000',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            expect($options)->toHaveCount(2)
                ->and($options[0]->code)->toBe('pac')
                ->and($options[1]->code)->toBe('sedex');
        });

        it('PAC is cheaper than SEDEX', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            $pac   = collect($options)->firstWhere('code', 'pac');
            $sedex = collect($options)->firstWhere('code', 'sedex');

            expect($pac->price)->toBeLessThan($sedex->price);
        });

        it('SEDEX is faster than PAC', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            $pac   = collect($options)->firstWhere('code', 'pac');
            $sedex = collect($options)->firstWhere('code', 'sedex');

            expect($sedex->deliveryDaysMax)->toBeLessThan($pac->deliveryDaysMax);
        });
    });

    describe('regional pricing', function () {
        it('charges more for distant regions', function () {
            $requestSudeste = new ShippingQuoteRequest(
                destinationZipcode: '01310-100', // Sao Paulo
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $requestNorte = new ShippingQuoteRequest(
                destinationZipcode: '66000-000', // Belem
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $optionsSudeste = $this->provider->calculate($requestSudeste);
            $optionsNorte   = $this->provider->calculate($requestNorte);

            $pacSudeste = collect($optionsSudeste)->firstWhere('code', 'pac');
            $pacNorte   = collect($optionsNorte)->firstWhere('code', 'pac');

            expect($pacNorte->price)->toBeGreaterThan($pacSudeste->price);
        });

        it('has longer delivery times for distant regions', function () {
            $requestSudeste = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $requestNorte = new ShippingQuoteRequest(
                destinationZipcode: '69000-000', // Manaus
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $optionsSudeste = $this->provider->calculate($requestSudeste);
            $optionsNorte   = $this->provider->calculate($requestNorte);

            $pacSudeste = collect($optionsSudeste)->firstWhere('code', 'pac');
            $pacNorte   = collect($optionsNorte)->firstWhere('code', 'pac');

            expect($pacNorte->deliveryDaysMax)->toBeGreaterThan($pacSudeste->deliveryDaysMax);
        });
    });

    describe('weight and dimension surcharges', function () {
        it('charges more for heavier packages', function () {
            $requestLight = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500, // 0.5kg
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $requestHeavy = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 5000, // 5kg
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $optionsLight = $this->provider->calculate($requestLight);
            $optionsHeavy = $this->provider->calculate($requestHeavy);

            $pacLight = collect($optionsLight)->firstWhere('code', 'pac');
            $pacHeavy = collect($optionsHeavy)->firstWhere('code', 'pac');

            expect($pacHeavy->price)->toBeGreaterThan($pacLight->price);
        });

        it('charges more for larger dimensions', function () {
            $requestSmall = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $requestLarge = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 60,
                totalWidth: 40,
                totalHeight: 30,
                totalValue: 10000,
            );

            $optionsSmall = $this->provider->calculate($requestSmall);
            $optionsLarge = $this->provider->calculate($requestLarge);

            $pacSmall = collect($optionsSmall)->firstWhere('code', 'pac');
            $pacLarge = collect($optionsLarge)->firstWhere('code', 'pac');

            expect($pacLarge->price)->toBeGreaterThan($pacSmall->price);
        });
    });

    describe('all options have carrier', function () {
        it('sets Correios as carrier for all options', function () {
            $request = new ShippingQuoteRequest(
                destinationZipcode: '01310-100',
                totalWeight: 500,
                totalLength: 20,
                totalWidth: 15,
                totalHeight: 10,
                totalValue: 10000,
            );

            $options = $this->provider->calculate($request);

            foreach ($options as $option) {
                expect($option->carrier)->toBe('Correios');
            }
        });
    });
});
