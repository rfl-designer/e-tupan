<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\{Cache, Http};

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'payment.default'                           => 'mercadopago',
        'payment.gateways.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
        'payment.gateways.mercadopago.public_key'   => 'TEST-PUBLIC-KEY',
        'payment.installments.max_installments'     => 12,
        'payment.installments.min_value'            => 500,
        'payment.installments.interest_free'        => 3,
        'payment.installments.interest_rate'        => 1.99,
    ]);

    Cache::flush();
});

describe('Installment API Endpoint', function () {
    it('returns installments for a given amount', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payment_type_id'   => 'credit_card',
                    'payer_costs'       => [
                        [
                            'installments'       => 1,
                            'installment_amount' => 100.00,
                            'total_amount'       => 100.00,
                            'installment_rate'   => 0,
                        ],
                        [
                            'installments'       => 2,
                            'installment_amount' => 50.00,
                            'total_amount'       => 100.00,
                            'installment_rate'   => 0,
                        ],
                        [
                            'installments'       => 3,
                            'installment_amount' => 33.34,
                            'total_amount'       => 100.02,
                            'installment_rate'   => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk()
            ->assertJsonStructure([
                'installments' => [
                    '*' => [
                        'quantity',
                        'amount',
                        'installment_amount',
                        'total_amount',
                        'interest_rate',
                        'has_interest',
                        'message',
                    ],
                ],
            ]);
    });

    it('validates amount is required', function () {
        $response = $this->getJson('/api/checkout/installments');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    });

    it('validates amount is integer', function () {
        $response = $this->getJson('/api/checkout/installments?amount=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    });

    it('validates minimum amount', function () {
        $response = $this->getJson('/api/checkout/installments?amount=50');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    });

    it('accepts card_brand parameter', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'master',
                    'payer_costs'       => [
                        [
                            'installments'       => 1,
                            'installment_amount' => 100.00,
                            'total_amount'       => 100.00,
                            'installment_rate'   => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000&card_brand=mastercard');

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'payment_method_id=master');
        });
    });

    it('indicates interest-free installments correctly', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        [
                            'installments'       => 3,
                            'installment_amount' => 33.34,
                            'total_amount'       => 100.02,
                            'installment_rate'   => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk();

        $installments      = $response->json('installments');
        $threeInstallments = collect($installments)->firstWhere('quantity', 3);

        expect($threeInstallments['has_interest'])->toBeFalse();
        expect($threeInstallments['message'])->toContain('sem juros');
    });

    it('indicates interest installments with CFT', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        [
                            'installments'       => 6,
                            'installment_amount' => 18.50,
                            'total_amount'       => 111.00,
                            'installment_rate'   => 1.99,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk();

        $installments    = $response->json('installments');
        $sixInstallments = collect($installments)->firstWhere('quantity', 6);

        expect($sixInstallments['has_interest'])->toBeTrue();
        expect($sixInstallments)->toHaveKey('cft');
        expect($sixInstallments['cft'])->toBeGreaterThan(0);
    });

    it('caches installment results', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        [
                            'installments'       => 1,
                            'installment_amount' => 100.00,
                            'total_amount'       => 100.00,
                            'installment_rate'   => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        // First request
        $this->getJson('/api/checkout/installments?amount=10000')->assertOk();

        // Second request with same amount should use cache
        $this->getJson('/api/checkout/installments?amount=10000')->assertOk();

        // API should only be called once
        Http::assertSentCount(1);
    });

    it('returns local calculation when API fails', function () {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk()
            ->assertJsonStructure([
                'installments' => [
                    '*' => [
                        'quantity',
                        'amount',
                        'installment_amount',
                        'total_amount',
                        'interest_rate',
                        'has_interest',
                        'message',
                    ],
                ],
            ]);

        // Should have multiple installment options
        expect(count($response->json('installments')))->toBeGreaterThan(0);
    });

    it('respects max installments configuration', function () {
        config(['payment.installments.max_installments' => 6]);

        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 3, 'installment_amount' => 33.34, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 6, 'installment_amount' => 17.00, 'total_amount' => 102.00, 'installment_rate' => 0],
                        ['installments' => 12, 'installment_amount' => 9.00, 'total_amount' => 108.00, 'installment_rate' => 1.99],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk();

        $installments = $response->json('installments');
        $maxQuantity  = collect($installments)->max('quantity');

        expect($maxQuantity)->toBeLessThanOrEqual(6);
    });

    it('respects min installment value configuration', function () {
        config(['payment.installments.min_value' => 5000]); // R$ 50.00

        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 3, 'installment_amount' => 33.34, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 6, 'installment_amount' => 17.00, 'total_amount' => 102.00, 'installment_rate' => 0],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk();

        $installments = $response->json('installments');

        foreach ($installments as $installment) {
            expect($installment['installment_amount'])->toBeGreaterThanOrEqual(5000);
        }
    });

    it('uses local calculation for mock gateway', function () {
        config(['payment.default' => 'mock']);

        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $response->assertOk()
            ->assertJsonStructure([
                'installments' => [
                    '*' => [
                        'quantity',
                        'amount',
                        'installment_amount',
                        'total_amount',
                        'interest_rate',
                        'has_interest',
                        'message',
                    ],
                ],
            ]);
    });
});

describe('Installment Messages', function () {
    beforeEach(function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 3, 'installment_amount' => 33.34, 'total_amount' => 100.02, 'installment_rate' => 0],
                        ['installments' => 6, 'installment_amount' => 18.50, 'total_amount' => 111.00, 'installment_rate' => 1.99],
                    ],
                ],
            ], 200),
        ]);
    });

    it('shows a vista message for single payment', function () {
        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $installments  = $response->json('installments');
        $singlePayment = collect($installments)->firstWhere('quantity', 1);

        expect($singlePayment['message'])->toContain('a vista');
    });

    it('shows sem juros message for interest-free installments', function () {
        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $installments      = $response->json('installments');
        $threeInstallments = collect($installments)->firstWhere('quantity', 3);

        expect($threeInstallments['message'])->toContain('sem juros');
    });

    it('shows total amount for installments with interest', function () {
        $response = $this->getJson('/api/checkout/installments?amount=10000');

        $installments    = $response->json('installments');
        $sixInstallments = collect($installments)->firstWhere('quantity', 6);

        expect($sixInstallments['message'])->toContain('R$');
        expect($sixInstallments['message'])->not->toContain('sem juros');
    });
});
