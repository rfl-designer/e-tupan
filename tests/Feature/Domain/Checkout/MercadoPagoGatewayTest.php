<?php

declare(strict_types = 1);

use App\Domain\Checkout\DTOs\CardData;
use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Gateways\MercadoPagoGateway;
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'payment.gateways.mercadopago.access_token'   => 'TEST-ACCESS-TOKEN',
        'payment.gateways.mercadopago.public_key'     => 'TEST-PUBLIC-KEY',
        'payment.gateways.mercadopago.sandbox'        => true,
        'payment.gateways.mercadopago.webhook_secret' => 'test-webhook-secret',
    ]);

    $this->gateway = new MercadoPagoGateway();

    $this->order = Order::factory()->create([
        'total'                 => 10000, // R$ 100.00
        'guest_email'           => 'test@example.com',
        'guest_name'            => 'John Doe',
        'guest_cpf'             => '123.456.789-00',
        'guest_phone'           => '(11) 99999-9999',
        'shipping_zipcode'      => '01310-100',
        'shipping_street'       => 'Av Paulista',
        'shipping_number'       => '1000',
        'shipping_neighborhood' => 'Bela Vista',
        'shipping_city'         => 'Sao Paulo',
        'shipping_state'        => 'SP',
    ]);
});

describe('Gateway Configuration', function () {
    it('returns gateway name correctly', function () {
        expect($this->gateway->getName())->toBe('mercadopago');
    });

    it('is available when configured', function () {
        expect($this->gateway->isAvailable())->toBeTrue();
    });

    it('is not available when not configured', function () {
        config([
            'payment.gateways.mercadopago.access_token' => '',
            'payment.gateways.mercadopago.public_key'   => '',
        ]);

        $gateway = new MercadoPagoGateway();

        expect($gateway->isAvailable())->toBeFalse();
    });

    it('returns public key', function () {
        expect($this->gateway->getPublicKey())->toBe('TEST-PUBLIC-KEY');
    });

    it('returns sandbox mode status', function () {
        expect($this->gateway->isSandbox())->toBeTrue();
    });
});

describe('Credit Card Payments', function () {
    it('processes approved credit card payment', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'                 => 123456789,
                'status'             => 'approved',
                'status_detail'      => 'accredited',
                'payment_type_id'    => 'credit_card',
                'authorization_code' => 'AUTH123',
                'date_approved'      => now()->toIso8601String(),
            ], 200),
        ]);

        $cardData = new CardData(
            token: 'test_card_token',
            holderName: 'John Doe',
            installments: 1,
            cardBrand: 'visa',
            lastFourDigits: '1234',
        );

        $result = $this->gateway->processCard($this->order, $cardData);

        expect($result->isSuccessful())->toBeTrue();
        expect($result->status)->toBe(PaymentStatus::Approved);
        expect($result->transactionId)->toBe('123456789');
        expect($result->metadata['gateway'])->toBe('mercadopago');
    });

    it('handles rejected credit card payment', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'            => 123456789,
                'status'        => 'rejected',
                'status_detail' => 'cc_rejected_insufficient_amount',
            ], 200),
        ]);

        $cardData = new CardData(
            token: 'test_card_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $result = $this->gateway->processCard($this->order, $cardData);

        expect($result->isSuccessful())->toBeFalse();
        expect($result->status)->toBe(PaymentStatus::Declined);
        expect($result->errorCode)->toBe('cc_rejected_insufficient_amount');
        expect($result->errorMessage)->toContain('insuficiente');
    });

    it('handles pending credit card payment', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'            => 123456789,
                'status'        => 'in_process',
                'status_detail' => 'pending_contingency',
            ], 200),
        ]);

        $cardData = new CardData(
            token: 'test_card_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $result = $this->gateway->processCard($this->order, $cardData);

        expect($result->isSuccessful())->toBeTrue();
        expect($result->isPending())->toBeTrue();
    });

    it('handles API error gracefully', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'message' => 'Invalid token',
                'error'   => 'bad_request',
            ], 400),
        ]);

        $cardData = new CardData(
            token: 'invalid_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $result = $this->gateway->processCard($this->order, $cardData);

        expect($result->isSuccessful())->toBeFalse();
    });

    it('handles connection exception', function () {
        Http::fake([
            'api.mercadopago.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection failed'),
        ]);

        $cardData = new CardData(
            token: 'test_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $result = $this->gateway->processCard($this->order, $cardData);

        expect($result->isSuccessful())->toBeFalse();
        expect($result->errorCode)->toBe('gateway_error');
    });

    it('maps card brands correctly', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'     => 123456789,
                'status' => 'approved',
            ], 200),
        ]);

        $cardData = new CardData(
            token: 'test_token',
            holderName: 'John Doe',
            installments: 1,
            cardBrand: 'mastercard',
        );

        $this->gateway->processCard($this->order, $cardData);

        Http::assertSent(function ($request) {
            return $request['payment_method_id'] === 'master';
        });
    });
});

describe('Pix Payments', function () {
    it('generates pix payment successfully', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'                   => 123456789,
                'status'               => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code'        => '00020126580014BR.GOV.BCB.PIX...',
                        'qr_code_base64' => base64_encode('qr_image_data'),
                    ],
                ],
            ], 200),
        ]);

        $pixData = $this->gateway->generatePix($this->order);

        expect($pixData->transactionId)->toBe('123456789');
        expect($pixData->qrCode)->not->toBeEmpty();
        expect($pixData->qrCodeBase64)->not->toBeEmpty();
        expect($pixData->amount)->toBe(10000);
        expect($pixData->expiresAt)->not->toBeNull();
        expect($pixData->metadata['gateway'])->toBe('mercadopago');
    });

    it('throws exception on pix generation failure', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'message' => 'Error generating PIX',
            ], 400),
        ]);

        expect(fn () => $this->gateway->generatePix($this->order))
            ->toThrow(\RuntimeException::class);
    });
});

describe('Bank Slip Payments', function () {
    it('generates bank slip successfully', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id'                  => 123456789,
                'status'              => 'pending',
                'transaction_details' => [
                    'external_resource_url' => 'https://mercadopago.com/boleto.pdf',
                    'digitable_line'        => '23793.38128 60000.000003 00000.000402 1 85340000010000',
                    'barcode'               => [
                        'content' => '23793381286000000000300000000401853400000100.00',
                    ],
                ],
            ], 200),
        ]);

        $bankSlipData = $this->gateway->generateBankSlip($this->order);

        expect($bankSlipData->transactionId)->toBe('123456789');
        expect($bankSlipData->pdfUrl)->toContain('boleto.pdf');
        expect($bankSlipData->digitableLine)->not->toBeEmpty();
        expect($bankSlipData->barcode)->not->toBeEmpty();
        expect($bankSlipData->amount)->toBe(10000);
        expect($bankSlipData->dueDate)->not->toBeNull();
    });

    it('throws exception on bank slip generation failure', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'message' => 'Error generating boleto',
            ], 400),
        ]);

        expect(fn () => $this->gateway->generateBankSlip($this->order))
            ->toThrow(\RuntimeException::class);
    });
});

describe('Payment Status Check', function () {
    it('checks payment status successfully', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/123456789' => Http::response([
                'id'     => 123456789,
                'status' => 'approved',
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '123456789',
            'status'                 => PaymentStatus::Pending,
        ]);

        $status = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Approved);
    });

    it('returns current status on API failure', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([], 500),
        ]);

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '123456789',
            'status'                 => PaymentStatus::Pending,
        ]);

        $status = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Pending);
    });
});

describe('Refunds', function () {
    it('processes full refund successfully', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*/refunds' => Http::response([
                'id'           => 987654321,
                'amount'       => 100.00,
                'status'       => 'approved',
                'date_created' => now()->toIso8601String(),
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '123456789',
            'amount'                 => 10000,
            'status'                 => PaymentStatus::Approved,
        ]);

        $result = $this->gateway->refund($payment);

        expect($result->isSuccessful())->toBeTrue();
        expect($result->refundId)->toBe('987654321');
        expect($result->amount)->toBe(10000);
    });

    it('processes partial refund', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/123456789/refunds' => Http::response([
                'id'     => 987654321,
                'amount' => 50.00,
                'status' => 'approved',
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '123456789',
            'amount'                 => 10000,
            'status'                 => PaymentStatus::Approved,
        ]);

        $result = $this->gateway->refund($payment, 5000);

        expect($result->isSuccessful())->toBeTrue();
        expect($result->amount)->toBe(5000);
    });

    it('handles refund failure', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*/refunds' => Http::response([
                'message' => 'Refund not allowed',
            ], 400),
        ]);

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '123456789',
            'status'                 => PaymentStatus::Approved,
        ]);

        $result = $this->gateway->refund($payment);

        expect($result->isSuccessful())->toBeFalse();
        expect($result->errorCode)->toBe('refund_failed');
    });
});

describe('Webhook Handling', function () {
    it('parses webhook payload and fetches payment data', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/123456789' => Http::response([
                'id'                 => 123456789,
                'status'             => 'approved',
                'payment_type_id'    => 'credit_card',
                'status_detail'      => 'accredited',
                'external_reference' => 'order-uuid',
            ], 200),
        ]);

        $payload = json_encode([
            'action' => 'payment.updated',
            'data'   => [
                'id' => '123456789',
            ],
        ]);

        $parsed = $this->gateway->parseWebhookPayload($payload);

        expect($parsed['transaction_id'])->toBe('123456789');
        expect($parsed['status'])->toBe(PaymentStatus::Approved);
        expect($parsed['metadata']['external_reference'])->toBe('order-uuid');
    });

    it('returns pending status when payment fetch fails', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([], 500),
        ]);

        $payload = json_encode([
            'action' => 'payment.updated',
            'data'   => [
                'id' => '123456789',
            ],
        ]);

        $parsed = $this->gateway->parseWebhookPayload($payload);

        expect($parsed['transaction_id'])->toBe('123456789');
        expect($parsed['status'])->toBe(PaymentStatus::Pending);
    });
});

describe('Status Mapping', function () {
    it('maps approved status correctly', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id'     => 123,
                'status' => 'approved',
            ], 200),
        ]);

        $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
        $status  = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Approved);
    });

    it('maps pending statuses correctly', function () {
        $pendingStatuses = ['pending', 'in_process', 'in_mediation'];

        foreach ($pendingStatuses as $mpStatus) {
            Http::fake([
                'api.mercadopago.com/v1/payments/*' => Http::response([
                    'id'     => 123,
                    'status' => $mpStatus,
                ], 200),
            ]);

            $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
            $status  = $this->gateway->checkPaymentStatus($payment);

            expect($status)->toBe(PaymentStatus::Pending, "Failed for status: {$mpStatus}");
        }
    });

    it('maps rejected status to declined', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id'     => 123,
                'status' => 'rejected',
            ], 200),
        ]);

        $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
        $status  = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Declined);
    });

    it('maps cancelled status correctly', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id'     => 123,
                'status' => 'cancelled',
            ], 200),
        ]);

        $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
        $status  = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Cancelled);
    });

    it('maps refund statuses correctly', function () {
        $refundStatuses = ['refunded', 'charged_back'];

        foreach ($refundStatuses as $mpStatus) {
            Http::fake([
                'api.mercadopago.com/v1/payments/*' => Http::response([
                    'id'     => 123,
                    'status' => $mpStatus,
                ], 200),
            ]);

            $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
            $status  = $this->gateway->checkPaymentStatus($payment);

            expect($status)->toBe(PaymentStatus::Refunded, "Failed for status: {$mpStatus}");
        }
    });

    it('maps unknown status to failed', function () {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id'     => 123,
                'status' => 'some_unknown_status',
            ], 200),
        ]);

        $payment = Payment::factory()->create(['gateway_transaction_id' => '123']);
        $status  = $this->gateway->checkPaymentStatus($payment);

        expect($status)->toBe(PaymentStatus::Failed);
    });
});
