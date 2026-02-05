<?php

declare(strict_types = 1);

use App\Domain\Checkout\DTOs\CardData;
use App\Domain\Checkout\Enums\{PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Gateways\MockPaymentGateway;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Services\PaymentService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->gateway = new MockPaymentGateway();
    $this->service = new PaymentService($this->gateway);

    $this->order = Order::factory()->create([
        'total' => 10000, // R$ 100.00
    ]);
});

describe('Credit Card Payments', function () {
    it('processes a successful credit card payment', function () {
        $cardData = new CardData(
            token: 'test_approved_token',
            holderName: 'John Doe',
            installments: 1,
            cardBrand: 'visa',
            lastFourDigits: '1234',
        );

        $payment = $this->service->processCard($this->order, $cardData);

        expect($payment)->toBeInstanceOf(\App\Domain\Checkout\Models\Payment::class);
        expect($payment->method)->toBe(PaymentMethod::CreditCard);
        expect($payment->status)->toBe(PaymentStatus::Approved);
        expect($payment->card_brand)->toBe('visa');
        expect($payment->card_last_four)->toBe('1234');
        expect($payment->installments)->toBe(1);
        expect($payment->gateway_transaction_id)->toStartWith('mock_');

        // Order should be marked as paid
        $this->order->refresh();
        expect($this->order->isPaid())->toBeTrue();
    });

    it('handles declined credit card', function () {
        $cardData = new CardData(
            token: 'test_declined_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $payment = $this->service->processCard($this->order, $cardData);

        expect($payment->status)->toBe(PaymentStatus::Declined);
        expect($payment->gateway_transaction_id)->toBeNull();

        // Order should not be marked as paid
        $this->order->refresh();
        expect($this->order->isPaid())->toBeFalse();
    });

    it('processes payment with installments', function () {
        $cardData = new CardData(
            token: 'test_approved_token',
            holderName: 'John Doe',
            installments: 6,
            cardBrand: 'mastercard',
            lastFourDigits: '5678',
        );

        $payment = $this->service->processCard($this->order, $cardData);

        expect($payment->installments)->toBe(6);
        expect($payment->installment_amount)->toBeGreaterThan(0);
    });
});

describe('Pix Payments', function () {
    it('generates a Pix payment', function () {
        $payment = $this->service->generatePix($this->order);

        expect($payment->method)->toBe(PaymentMethod::Pix);
        expect($payment->status)->toBe(PaymentStatus::Pending);
        expect($payment->pix_qr_code)->not->toBeNull();
        expect($payment->pix_code)->not->toBeNull();
        expect($payment->expires_at)->not->toBeNull();
        expect($payment->gateway_transaction_id)->toStartWith('mock_pix_');
    });

    it('sets expiration time for Pix', function () {
        $payment = $this->service->generatePix($this->order);

        expect($payment->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($payment->expires_at->isFuture())->toBeTrue();
    });
});

describe('Bank Slip Payments', function () {
    it('generates a bank slip payment', function () {
        $payment = $this->service->generateBankSlip($this->order);

        expect($payment->method)->toBe(PaymentMethod::BankSlip);
        expect($payment->status)->toBe(PaymentStatus::Pending);
        expect($payment->bank_slip_url)->not->toBeNull();
        expect($payment->bank_slip_barcode)->not->toBeNull();
        expect($payment->expires_at)->not->toBeNull();
        expect($payment->gateway_transaction_id)->toStartWith('mock_boleto_');
    });

    it('sets due date for bank slip', function () {
        $payment = $this->service->generateBankSlip($this->order);

        expect($payment->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($payment->expires_at->isFuture())->toBeTrue();
    });
});

describe('Installment Options', function () {
    it('calculates installment options correctly', function () {
        $options = $this->service->getInstallmentOptions(10000);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        // First installment should be the full amount
        expect($options[1]['installments'])->toBe(1);
        expect($options[1]['value'])->toBe(10000);
        expect($options[1]['interest_free'])->toBeTrue();
    });

    it('respects minimum installment value', function () {
        // With R$ 100, max should be around 10-12 installments (min R$ 5 per installment)
        $options = $this->service->getInstallmentOptions(10000);

        $maxInstallments = max(array_keys($options));
        $minValue        = $options[$maxInstallments]['value'];

        expect($minValue)->toBeGreaterThanOrEqual(500); // R$ 5.00
    });

    it('marks interest-free installments correctly', function () {
        $options = $this->service->getInstallmentOptions(50000); // R$ 500

        // First 3 should be interest-free (default config)
        expect($options[1]['interest_free'])->toBeTrue();
        expect($options[2]['interest_free'])->toBeTrue();
        expect($options[3]['interest_free'])->toBeTrue();

        if (isset($options[4])) {
            expect($options[4]['interest_free'])->toBeFalse();
        }
    });
});
