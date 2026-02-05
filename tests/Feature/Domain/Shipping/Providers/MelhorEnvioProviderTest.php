<?php

declare(strict_types = 1);

use App\Domain\Shipping\DTOs\{ShippingOption, ShippingQuoteRequest};
use App\Domain\Shipping\Providers\MelhorEnvioProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'shipping.providers.melhor_envio.token'   => 'test-token',
        'shipping.providers.melhor_envio.sandbox' => true,
        'shipping.origin.zipcode'                 => '01310100',
        'shipping.origin.street'                  => 'Avenida Paulista',
        'shipping.origin.number'                  => '1000',
        'shipping.origin.neighborhood'            => 'Bela Vista',
        'shipping.origin.city'                    => 'Sao Paulo',
        'shipping.origin.state'                   => 'SP',
        'shipping.defaults.weight'                => 0.3,
        'shipping.defaults.height'                => 2,
        'shipping.defaults.width'                 => 11,
        'shipping.defaults.length'                => 16,
        'shipping.handling_days'                  => 1,
        'shipping.cache.quotes_ttl'               => 300,
    ]);
});

describe('MelhorEnvioProvider', function (): void {
    it('returns provider name', function (): void {
        $provider = new MelhorEnvioProvider();

        expect($provider->getName())->toBe('Melhor Envio');
    });

    it('is available when token is configured', function (): void {
        $provider = new MelhorEnvioProvider();

        expect($provider->isAvailable())->toBeTrue();
    });

    it('is not available when token is missing', function (): void {
        config(['shipping.providers.melhor_envio.token' => null]);

        $provider = new MelhorEnvioProvider();

        expect($provider->isAvailable())->toBeFalse();
    });

    it('uses sandbox url when sandbox mode is enabled', function (): void {
        $provider = new MelhorEnvioProvider();

        expect($provider->getBaseUrl())->toBe('https://sandbox.melhorenvio.com.br');
    });

    it('uses production url when sandbox mode is disabled', function (): void {
        config(['shipping.providers.melhor_envio.sandbox' => false]);

        $provider = new MelhorEnvioProvider();

        expect($provider->getBaseUrl())->toBe('https://melhorenvio.com.br');
    });

    it('can test connection successfully', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me' => Http::response([
                'id'    => '123',
                'email' => 'test@example.com',
            ], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $result   = $provider->testConnection();

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->url() === 'https://sandbox.melhorenvio.com.br/api/v2/me';
        });
    });

    it('returns false when connection test fails', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $provider = new MelhorEnvioProvider();
        $result   = $provider->testConnection();

        expect($result)->toBeFalse();
    });

    it('calculates shipping options from API response', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([
                [
                    'id'             => 1,
                    'name'           => 'PAC',
                    'company'        => ['name' => 'Correios'],
                    'price'          => '15.50',
                    'delivery_time'  => 8,
                    'delivery_range' => ['min' => 5, 'max' => 8],
                    'error'          => null,
                ],
                [
                    'id'             => 2,
                    'name'           => 'SEDEX',
                    'company'        => ['name' => 'Correios'],
                    'price'          => '25.90',
                    'delivery_time'  => 3,
                    'delivery_range' => ['min' => 1, 'max' => 3],
                    'error'          => null,
                ],
            ], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $options = $provider->calculate($request);

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(2)
            ->and($options[0])->toBeInstanceOf(ShippingOption::class)
            ->and($options[0]->code)->toBe('1')
            ->and($options[0]->name)->toBe('PAC')
            ->and($options[0]->price)->toBe(1550)
            ->and($options[0]->carrier)->toBe('Correios')
            ->and($options[0]->deliveryDaysMin)->toBe(6)
            ->and($options[0]->deliveryDaysMax)->toBe(9)
            ->and($options[1]->code)->toBe('2')
            ->and($options[1]->name)->toBe('SEDEX')
            ->and($options[1]->price)->toBe(2590);
    });

    it('filters out options with errors', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([
                [
                    'id'             => 1,
                    'name'           => 'PAC',
                    'company'        => ['name' => 'Correios'],
                    'price'          => '15.50',
                    'delivery_time'  => 8,
                    'delivery_range' => ['min' => 5, 'max' => 8],
                    'error'          => null,
                ],
                [
                    'id'             => 2,
                    'name'           => 'SEDEX',
                    'company'        => ['name' => 'Correios'],
                    'price'          => null,
                    'delivery_time'  => null,
                    'delivery_range' => null,
                    'error'          => 'Servico indisponivel para o CEP informado',
                ],
            ], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $options = $provider->calculate($request);

        expect($options)->toHaveCount(1)
            ->and($options[0]->name)->toBe('PAC');
    });

    it('returns empty array when API fails', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response(
                ['error' => 'Internal Server Error'],
                500,
            ),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $options = $provider->calculate($request);

        expect($options)->toBeArray()->toBeEmpty();
    });

    it('sends correct request payload to API', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041-080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $provider->calculate($request);

        Http::assertSent(function ($httpRequest) {
            $body = $httpRequest->data();

            return $body['from']['postal_code'] === '01310100'
                && $body['to']['postal_code'] === '22041080'
                && $body['package']['weight'] === 0.5
                && $body['package']['width'] === 15
                && $body['package']['height'] === 10
                && $body['package']['length'] === 20;
        });
    });

    it('applies handling days to delivery time', function (): void {
        config(['shipping.handling_days' => 2]);

        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([
                [
                    'id'             => 1,
                    'name'           => 'PAC',
                    'company'        => ['name' => 'Correios'],
                    'price'          => '15.50',
                    'delivery_time'  => 8,
                    'delivery_range' => ['min' => 5, 'max' => 8],
                    'error'          => null,
                ],
            ], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $options = $provider->calculate($request);

        expect($options[0]->deliveryDaysMin)->toBe(7)
            ->and($options[0]->deliveryDaysMax)->toBe(10);
    });

    it('caches quote results', function (): void {
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([
                [
                    'id'             => 1,
                    'name'           => 'PAC',
                    'company'        => ['name' => 'Correios'],
                    'price'          => '15.50',
                    'delivery_time'  => 8,
                    'delivery_range' => ['min' => 5, 'max' => 8],
                    'error'          => null,
                ],
            ], 200),
        ]);

        $provider = new MelhorEnvioProvider();
        $request  = new ShippingQuoteRequest(
            destinationZipcode: '22041080',
            totalWeight: 500,
            totalLength: 20,
            totalWidth: 15,
            totalHeight: 10,
            totalValue: 10000,
        );

        $provider->calculate($request);
        $provider->calculate($request);

        Http::assertSentCount(1);
    });
});
