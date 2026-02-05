<?php

declare(strict_types = 1);

use App\Domain\Checkout\DTOs\InstallmentOption;
use App\Domain\Checkout\Services\InstallmentService;
use Illuminate\Support\Facades\{Cache, Http};

uses(Tests\TestCase::class);

beforeEach(function () {
    config([
        'payment.default'                           => 'mercadopago',
        'payment.gateways.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
        'payment.installments.max_installments'     => 12,
        'payment.installments.min_value'            => 500,
        'payment.installments.interest_free'        => 3,
        'payment.installments.interest_rate'        => 1.99,
    ]);

    Cache::flush();
});

describe('InstallmentService', function () {
    it('fetches installments from Mercado Pago API', function () {
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

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        expect($installments)->toHaveCount(2);
        expect($installments->first())->toBeInstanceOf(InstallmentOption::class);
    });

    it('caches installments for 5 minutes', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                    ],
                ],
            ], 200),
        ]);

        $service = new InstallmentService();

        // First call
        $service->getInstallments(10000);

        // Second call - should use cache
        $service->getInstallments(10000);

        Http::assertSentCount(1);
    });

    it('uses different cache keys for different amounts', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                    ],
                ],
            ], 200),
        ]);

        $service = new InstallmentService();

        $service->getInstallments(10000);
        $service->getInstallments(20000);

        Http::assertSentCount(2);
    });

    it('uses different cache keys for different card brands', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                    ],
                ],
            ], 200),
        ]);

        $service = new InstallmentService();

        $service->getInstallments(10000, 'visa');
        $service->getInstallments(10000, 'mastercard');

        Http::assertSentCount(2);
    });

    it('falls back to local calculation when API fails', function () {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([], 500),
        ]);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        expect($installments)->not->toBeEmpty();
        expect($installments->first())->toBeInstanceOf(InstallmentOption::class);
    });

    it('falls back to local calculation for mock gateway', function () {
        config(['payment.default' => 'mock']);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        expect($installments)->not->toBeEmpty();

        // Should not call external API
        Http::assertNothingSent();
    });

    it('respects max installments configuration', function () {
        config(['payment.installments.max_installments' => 6]);

        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 6, 'installment_amount' => 17.00, 'total_amount' => 102.00, 'installment_rate' => 0],
                        ['installments' => 12, 'installment_amount' => 9.00, 'total_amount' => 108.00, 'installment_rate' => 1.99],
                    ],
                ],
            ], 200),
        ]);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        $maxQuantity = $installments->max('quantity');

        expect($maxQuantity)->toBeLessThanOrEqual(6);
    });

    it('filters installments below min value', function () {
        config(['payment.installments.min_value' => 5000]); // R$ 50.00

        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 3, 'installment_amount' => 33.34, 'total_amount' => 100.00, 'installment_rate' => 0],
                        ['installments' => 12, 'installment_amount' => 9.00, 'total_amount' => 108.00, 'installment_rate' => 1.99],
                    ],
                ],
            ], 200),
        ]);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        foreach ($installments as $installment) {
            expect($installment->installmentAmount)->toBeGreaterThanOrEqual(5000);
        }
    });

    it('clears cache for specific amount', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods/installments*' => Http::response([
                [
                    'payment_method_id' => 'visa',
                    'payer_costs'       => [
                        ['installments' => 1, 'installment_amount' => 100.00, 'total_amount' => 100.00, 'installment_rate' => 0],
                    ],
                ],
            ], 200),
        ]);

        $service = new InstallmentService();

        // Populate cache
        $service->getInstallments(10000);

        // Clear cache
        $service->clearCache(10000);

        // Should fetch again
        $service->getInstallments(10000);

        Http::assertSentCount(2);
    });
});

describe('InstallmentOption DTO', function () {
    it('creates from Mercado Pago response', function () {
        $data = [
            'installments'       => 3,
            'installment_amount' => 33.34,
            'total_amount'       => 100.02,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->quantity)->toBe(3);
        expect($option->amount)->toBe(10000);
        expect($option->installmentAmount)->toBe(3334);
        expect($option->totalAmount)->toBe(10002);
        expect($option->interestRate)->toBe(0.0);
        expect($option->hasInterest)->toBeFalse();
    });

    it('calculates CFT for installments with interest', function () {
        $data = [
            'installments'       => 6,
            'installment_amount' => 18.50,
            'total_amount'       => 111.00,
            'installment_rate'   => 1.99,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->hasInterest)->toBeTrue();
        expect($option->cft)->not->toBeNull();
        expect($option->cft)->toBeGreaterThan(0);
    });

    it('does not calculate CFT for interest-free installments', function () {
        $data = [
            'installments'       => 3,
            'installment_amount' => 33.34,
            'total_amount'       => 100.02,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->hasInterest)->toBeFalse();
        expect($option->cft)->toBeNull();
    });

    it('builds correct message for single payment', function () {
        $data = [
            'installments'       => 1,
            'installment_amount' => 100.00,
            'total_amount'       => 100.00,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->message)->toContain('a vista');
    });

    it('builds correct message for interest-free installments', function () {
        $data = [
            'installments'       => 3,
            'installment_amount' => 33.34,
            'total_amount'       => 100.02,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->message)->toContain('sem juros');
        expect($option->message)->toContain('3x');
    });

    it('builds correct message for installments with interest', function () {
        $data = [
            'installments'       => 6,
            'installment_amount' => 18.50,
            'total_amount'       => 111.00,
            'installment_rate'   => 1.99,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);

        expect($option->message)->toContain('6x');
        expect($option->message)->toContain('R$');
        expect($option->message)->not->toContain('sem juros');
    });

    it('converts to array correctly', function () {
        $data = [
            'installments'       => 3,
            'installment_amount' => 33.34,
            'total_amount'       => 100.02,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);
        $array  = $option->toArray();

        expect($array)->toHaveKeys([
            'quantity',
            'amount',
            'installment_amount',
            'total_amount',
            'interest_rate',
            'has_interest',
            'message',
        ]);
    });

    it('includes CFT in array when has interest', function () {
        $data = [
            'installments'       => 6,
            'installment_amount' => 18.50,
            'total_amount'       => 111.00,
            'installment_rate'   => 1.99,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);
        $array  = $option->toArray();

        expect($array)->toHaveKey('cft');
    });

    it('does not include CFT in array when no interest', function () {
        $data = [
            'installments'       => 3,
            'installment_amount' => 33.34,
            'total_amount'       => 100.02,
            'installment_rate'   => 0,
        ];

        $option = InstallmentOption::fromMercadoPago($data, 10000);
        $array  = $option->toArray();

        expect($array)->not->toHaveKey('cft');
    });
});

describe('Local Calculation', function () {
    beforeEach(function () {
        config(['payment.default' => 'mock']);
    });

    it('calculates interest-free installments correctly', function () {
        config(['payment.installments.interest_free' => 3]);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        $threeInstallments = $installments->firstWhere('quantity', 3);

        expect($threeInstallments->hasInterest)->toBeFalse();
        expect($threeInstallments->totalAmount)->toBe(10000);
    });

    it('calculates installments with interest correctly', function () {
        config([
            'payment.installments.interest_free' => 3,
            'payment.installments.interest_rate' => 2.0,
        ]);

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000);

        $sixInstallments = $installments->firstWhere('quantity', 6);

        expect($sixInstallments->hasInterest)->toBeTrue();
        expect($sixInstallments->totalAmount)->toBeGreaterThan(10000);
    });

    it('limits installments based on min value', function () {
        config(['payment.installments.min_value' => 2000]); // R$ 20.00

        $service      = new InstallmentService();
        $installments = $service->getInstallments(10000); // R$ 100.00

        // With R$ 100 and min R$ 20, max should be 5 installments
        $maxQuantity = $installments->max('quantity');

        expect($maxQuantity)->toBeLessThanOrEqual(5);
    });
});
