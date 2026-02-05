<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\PaymentStatus;

describe('PaymentStatus', function () {
    it('has all expected cases', function () {
        $cases = PaymentStatus::cases();

        expect($cases)->toHaveCount(7)
            ->and(PaymentStatus::Pending->value)->toBe('pending')
            ->and(PaymentStatus::Processing->value)->toBe('processing')
            ->and(PaymentStatus::Approved->value)->toBe('approved')
            ->and(PaymentStatus::Declined->value)->toBe('declined')
            ->and(PaymentStatus::Cancelled->value)->toBe('cancelled')
            ->and(PaymentStatus::Refunded->value)->toBe('refunded')
            ->and(PaymentStatus::Failed->value)->toBe('failed');
    });

    it('returns correct labels', function () {
        expect(PaymentStatus::Pending->label())->toBe('Pendente')
            ->and(PaymentStatus::Processing->label())->toBe('Processando')
            ->and(PaymentStatus::Approved->label())->toBe('Aprovado')
            ->and(PaymentStatus::Declined->label())->toBe('Recusado')
            ->and(PaymentStatus::Cancelled->label())->toBe('Cancelado')
            ->and(PaymentStatus::Refunded->label())->toBe('Reembolsado')
            ->and(PaymentStatus::Failed->label())->toBe('Falhou');
    });

    it('returns correct colors', function () {
        expect(PaymentStatus::Pending->color())->toBe('amber')
            ->and(PaymentStatus::Processing->color())->toBe('sky')
            ->and(PaymentStatus::Approved->color())->toBe('lime')
            ->and(PaymentStatus::Declined->color())->toBe('red')
            ->and(PaymentStatus::Cancelled->color())->toBe('zinc')
            ->and(PaymentStatus::Refunded->color())->toBe('purple')
            ->and(PaymentStatus::Failed->color())->toBe('red');
    });

    it('identifies final statuses', function () {
        expect(PaymentStatus::Pending->isFinal())->toBeFalse()
            ->and(PaymentStatus::Processing->isFinal())->toBeFalse()
            ->and(PaymentStatus::Approved->isFinal())->toBeTrue()
            ->and(PaymentStatus::Declined->isFinal())->toBeTrue()
            ->and(PaymentStatus::Cancelled->isFinal())->toBeTrue()
            ->and(PaymentStatus::Refunded->isFinal())->toBeTrue()
            ->and(PaymentStatus::Failed->isFinal())->toBeTrue();
    });

    it('identifies successful payment', function () {
        expect(PaymentStatus::Approved->isSuccessful())->toBeTrue()
            ->and(PaymentStatus::Pending->isSuccessful())->toBeFalse()
            ->and(PaymentStatus::Declined->isSuccessful())->toBeFalse()
            ->and(PaymentStatus::Failed->isSuccessful())->toBeFalse();
    });

    it('identifies which payments can be refunded', function () {
        expect(PaymentStatus::Approved->canBeRefunded())->toBeTrue()
            ->and(PaymentStatus::Pending->canBeRefunded())->toBeFalse()
            ->and(PaymentStatus::Declined->canBeRefunded())->toBeFalse()
            ->and(PaymentStatus::Refunded->canBeRefunded())->toBeFalse();
    });

    it('returns correct options array', function () {
        $options = PaymentStatus::options();

        expect($options)->toHaveCount(7)
            ->and($options['pending'])->toBe('Pendente')
            ->and($options['approved'])->toBe('Aprovado');
    });
});
