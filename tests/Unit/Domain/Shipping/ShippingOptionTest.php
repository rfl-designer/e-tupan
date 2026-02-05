<?php

declare(strict_types = 1);

use App\Domain\Shipping\DTOs\ShippingOption;

describe('ShippingOption DTO', function () {
    it('creates shipping option with all properties', function () {
        $option = new ShippingOption(
            code: 'sedex',
            name: 'SEDEX',
            price: 2500,
            deliveryDaysMin: 1,
            deliveryDaysMax: 3,
            carrier: 'Correios',
        );

        expect($option->code)->toBe('sedex')
            ->and($option->name)->toBe('SEDEX')
            ->and($option->price)->toBe(2500)
            ->and($option->deliveryDaysMin)->toBe(1)
            ->and($option->deliveryDaysMax)->toBe(3)
            ->and($option->carrier)->toBe('Correios');
    });

    it('formats price in BRL', function () {
        $option = new ShippingOption(
            code: 'pac',
            name: 'PAC',
            price: 1599,
            deliveryDaysMin: 5,
            deliveryDaysMax: 8,
        );

        expect($option->formattedPrice())->toBe('R$ 15,99');
    });

    it('formats delivery time for single day', function () {
        $option = new ShippingOption(
            code: 'sedex_10',
            name: 'SEDEX 10',
            price: 4500,
            deliveryDaysMin: 1,
            deliveryDaysMax: 1,
        );

        expect($option->deliveryTimeDescription())->toBe('1 dias uteis');
    });

    it('formats delivery time for range', function () {
        $option = new ShippingOption(
            code: 'pac',
            name: 'PAC',
            price: 1500,
            deliveryDaysMin: 5,
            deliveryDaysMax: 8,
        );

        expect($option->deliveryTimeDescription())->toBe('5 a 8 dias uteis');
    });

    it('converts to array', function () {
        $option = new ShippingOption(
            code: 'sedex',
            name: 'SEDEX',
            price: 2500,
            deliveryDaysMin: 1,
            deliveryDaysMax: 3,
            carrier: 'Correios',
        );

        $array = $option->toArray();

        expect($array)
            ->toHaveKey('code', 'sedex')
            ->toHaveKey('name', 'SEDEX')
            ->toHaveKey('price', 2500)
            ->toHaveKey('formatted_price', 'R$ 25,00')
            ->toHaveKey('delivery_days_min', 1)
            ->toHaveKey('delivery_days_max', 3)
            ->toHaveKey('delivery_time', '1 a 3 dias uteis')
            ->toHaveKey('carrier', 'Correios');
    });

    it('allows null carrier', function () {
        $option = new ShippingOption(
            code: 'standard',
            name: 'Standard',
            price: 1000,
            deliveryDaysMin: 7,
            deliveryDaysMax: 14,
        );

        expect($option->carrier)->toBeNull();
    });
});
