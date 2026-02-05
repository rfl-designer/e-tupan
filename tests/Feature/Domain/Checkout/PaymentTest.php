<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\{PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Models\{Order, Payment};

describe('Payment Model', function () {
    it('has uuid as primary key', function () {
        $payment = Payment::factory()->create();

        expect($payment->id)->toBeString()
            ->and(strlen($payment->id))->toBe(36);
    });

    it('belongs to an order', function () {
        $order   = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        expect($payment->order)->toBeInstanceOf(Order::class)
            ->and($payment->order->id)->toBe($order->id);
    });

    it('casts method to PaymentMethod enum', function () {
        $payment = Payment::factory()->create(['method' => 'credit_card']);

        expect($payment->method)->toBe(PaymentMethod::CreditCard)
            ->and($payment->method)->toBeInstanceOf(PaymentMethod::class);
    });

    it('casts status to PaymentStatus enum', function () {
        $payment = Payment::factory()->create(['status' => 'approved']);

        expect($payment->status)->toBe(PaymentStatus::Approved)
            ->and($payment->status)->toBeInstanceOf(PaymentStatus::class);
    });

    it('calculates amount in reais', function () {
        $payment = Payment::factory()->create(['amount' => 15000]);

        expect($payment->amount_in_reais)->toBe(150.00);
    });

    it('calculates installment amount in reais', function () {
        $payment = Payment::factory()->create([
            'amount'       => 30000,
            'installments' => 3,
        ]);

        expect($payment->installment_amount_in_reais)->toBe(100.00);
    });

    it('checks if payment is approved', function () {
        $approvedPayment = Payment::factory()->approved()->create();
        $pendingPayment  = Payment::factory()->pending()->create();

        expect($approvedPayment->isApproved())->toBeTrue()
            ->and($pendingPayment->isApproved())->toBeFalse();
    });

    it('checks if payment is pending', function () {
        $pendingPayment  = Payment::factory()->pending()->create();
        $approvedPayment = Payment::factory()->approved()->create();

        expect($pendingPayment->isPending())->toBeTrue()
            ->and($approvedPayment->isPending())->toBeFalse();
    });

    it('checks if payment failed', function () {
        $declinedPayment = Payment::factory()->declined()->create();
        $failedPayment   = Payment::factory()->failed()->create();
        $approvedPayment = Payment::factory()->approved()->create();

        expect($declinedPayment->isFailed())->toBeTrue()
            ->and($failedPayment->isFailed())->toBeTrue()
            ->and($approvedPayment->isFailed())->toBeFalse();
    });

    it('checks if payment is expired', function () {
        $expiredPayment = Payment::factory()->expired()->create();
        $validPayment   = Payment::factory()->pix()->create();

        expect($expiredPayment->isExpired())->toBeTrue()
            ->and($validPayment->isExpired())->toBeFalse();
    });

    it('checks payment method type', function () {
        $creditCard = Payment::factory()->creditCard()->create();
        $pix        = Payment::factory()->pix()->create();
        $bankSlip   = Payment::factory()->bankSlip()->create();

        expect($creditCard->isCreditCard())->toBeTrue()
            ->and($creditCard->isPix())->toBeFalse()
            ->and($pix->isPix())->toBeTrue()
            ->and($pix->isBankSlip())->toBeFalse()
            ->and($bankSlip->isBankSlip())->toBeTrue();
    });

    it('can mark payment as approved', function () {
        $payment = Payment::factory()->pending()->create();
        $payment->markAsApproved();

        expect($payment->fresh()->isApproved())->toBeTrue()
            ->and($payment->fresh()->paid_at)->not->toBeNull();
    });

    it('can refund payment', function () {
        $payment = Payment::factory()->approved()->create(['amount' => 10000]);
        $payment->refund();

        $refreshed = $payment->fresh();

        expect($refreshed->status)->toBe(PaymentStatus::Refunded)
            ->and($refreshed->refunded_amount)->toBe(10000)
            ->and($refreshed->refunded_at)->not->toBeNull();
    });

    it('can refund partial amount', function () {
        $payment = Payment::factory()->approved()->create(['amount' => 10000]);
        $payment->refund(5000);

        expect($payment->fresh()->refunded_amount)->toBe(5000);
    });

    it('displays card info correctly', function () {
        $payment = Payment::factory()->creditCard()->create([
            'card_brand'     => 'visa',
            'card_last_four' => '1234',
        ]);

        expect($payment->card_display)->toBe('Visa **** 1234');
    });

    it('displays installment info correctly', function () {
        $singlePayment = Payment::factory()->create([
            'amount'       => 10000,
            'installments' => 1,
        ]);
        $installmentPayment = Payment::factory()->create([
            'amount'       => 30000,
            'installments' => 3,
        ]);

        expect($singlePayment->installment_display)->toBe('A vista')
            ->and($installmentPayment->installment_display)->toBe('3x de R$ 100,00');
    });

    it('checks if payment is awaiting confirmation', function () {
        $pixPending        = Payment::factory()->pix()->pending()->create();
        $pixApproved       = Payment::factory()->pix()->approved()->create();
        $creditCardPending = Payment::factory()->creditCard()->pending()->create();

        expect($pixPending->isAwaitingConfirmation())->toBeTrue()
            ->and($pixApproved->isAwaitingConfirmation())->toBeFalse()
            ->and($creditCardPending->isAwaitingConfirmation())->toBeFalse();
    });

    it('scopes payments by status', function () {
        Payment::factory()->count(2)->approved()->create();
        Payment::factory()->count(3)->pending()->create();

        $approvedPayments = Payment::approved()->get();
        $pendingPayments  = Payment::pending()->get();

        expect($approvedPayments)->toHaveCount(2)
            ->and($pendingPayments)->toHaveCount(3);
    });

    it('scopes payments by method', function () {
        Payment::factory()->count(2)->creditCard()->create();
        Payment::factory()->count(3)->pix()->create();

        $creditCardPayments = Payment::withMethod(PaymentMethod::CreditCard)->get();

        expect($creditCardPayments)->toHaveCount(2);
    });

    it('scopes expired payments', function () {
        Payment::factory()->count(2)->expired()->create();
        Payment::factory()->count(3)->pix()->create();

        $expiredPayments = Payment::expired()->get();

        expect($expiredPayments)->toHaveCount(2);
    });
});
