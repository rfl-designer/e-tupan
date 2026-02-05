<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\PaymentMethod;

describe('PaymentMethod', function () {
    it('has all expected cases', function () {
        $cases = PaymentMethod::cases();

        expect($cases)->toHaveCount(3)
            ->and(PaymentMethod::CreditCard->value)->toBe('credit_card')
            ->and(PaymentMethod::Pix->value)->toBe('pix')
            ->and(PaymentMethod::BankSlip->value)->toBe('bank_slip');
    });

    it('returns correct labels', function () {
        expect(PaymentMethod::CreditCard->label())->toBe('Cartao de Credito')
            ->and(PaymentMethod::Pix->label())->toBe('Pix')
            ->and(PaymentMethod::BankSlip->label())->toBe('Boleto Bancario');
    });

    it('returns correct icons', function () {
        expect(PaymentMethod::CreditCard->icon())->toBe('credit-card')
            ->and(PaymentMethod::Pix->icon())->toBe('qr-code')
            ->and(PaymentMethod::BankSlip->icon())->toBe('document-text');
    });

    it('returns correct descriptions', function () {
        expect(PaymentMethod::CreditCard->description())->toBe('Parcele em ate 12x')
            ->and(PaymentMethod::Pix->description())->toBe('Aprovacao imediata')
            ->and(PaymentMethod::BankSlip->description())->toBe('Vencimento em 3 dias uteis');
    });

    it('identifies methods requiring instant confirmation', function () {
        expect(PaymentMethod::CreditCard->requiresInstantConfirmation())->toBeTrue()
            ->and(PaymentMethod::Pix->requiresInstantConfirmation())->toBeFalse()
            ->and(PaymentMethod::BankSlip->requiresInstantConfirmation())->toBeFalse();
    });

    it('identifies asynchronous methods', function () {
        expect(PaymentMethod::CreditCard->isAsynchronous())->toBeFalse()
            ->and(PaymentMethod::Pix->isAsynchronous())->toBeTrue()
            ->and(PaymentMethod::BankSlip->isAsynchronous())->toBeTrue();
    });

    it('returns correct default expiration times', function () {
        expect(PaymentMethod::CreditCard->defaultExpirationMinutes())->toBeNull()
            ->and(PaymentMethod::Pix->defaultExpirationMinutes())->toBe(30)
            ->and(PaymentMethod::BankSlip->defaultExpirationMinutes())->toBe(4320);
    });

    it('returns correct options array', function () {
        $options = PaymentMethod::options();

        expect($options)->toBe([
            'credit_card' => 'Cartao de Credito',
            'pix'         => 'Pix',
            'bank_slip'   => 'Boleto Bancario',
        ]);
    });
});
