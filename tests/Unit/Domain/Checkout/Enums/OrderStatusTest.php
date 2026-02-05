<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\OrderStatus;

describe('OrderStatus', function () {
    it('has all expected cases', function () {
        $cases = OrderStatus::cases();

        expect($cases)->toHaveCount(6)
            ->and(OrderStatus::Pending->value)->toBe('pending')
            ->and(OrderStatus::Processing->value)->toBe('processing')
            ->and(OrderStatus::Shipped->value)->toBe('shipped')
            ->and(OrderStatus::Completed->value)->toBe('completed')
            ->and(OrderStatus::Cancelled->value)->toBe('cancelled')
            ->and(OrderStatus::Refunded->value)->toBe('refunded');
    });

    it('returns correct labels', function () {
        expect(OrderStatus::Pending->label())->toBe('Pendente')
            ->and(OrderStatus::Processing->label())->toBe('Processando')
            ->and(OrderStatus::Shipped->label())->toBe('Enviado')
            ->and(OrderStatus::Completed->label())->toBe('Entregue')
            ->and(OrderStatus::Cancelled->label())->toBe('Cancelado')
            ->and(OrderStatus::Refunded->label())->toBe('Reembolsado');
    });

    it('returns correct colors', function () {
        expect(OrderStatus::Pending->color())->toBe('amber')
            ->and(OrderStatus::Processing->color())->toBe('sky')
            ->and(OrderStatus::Shipped->color())->toBe('indigo')
            ->and(OrderStatus::Completed->color())->toBe('lime')
            ->and(OrderStatus::Cancelled->color())->toBe('red')
            ->and(OrderStatus::Refunded->color())->toBe('purple');
    });

    it('returns correct icons', function () {
        expect(OrderStatus::Pending->icon())->toBe('clock')
            ->and(OrderStatus::Processing->icon())->toBe('arrow-path')
            ->and(OrderStatus::Shipped->icon())->toBe('truck')
            ->and(OrderStatus::Completed->icon())->toBe('check-circle')
            ->and(OrderStatus::Cancelled->icon())->toBe('x-circle')
            ->and(OrderStatus::Refunded->icon())->toBe('arrow-uturn-left');
    });

    it('identifies which orders can be cancelled', function () {
        expect(OrderStatus::Pending->canBeCancelled())->toBeTrue()
            ->and(OrderStatus::Processing->canBeCancelled())->toBeTrue()
            ->and(OrderStatus::Shipped->canBeCancelled())->toBeFalse()
            ->and(OrderStatus::Completed->canBeCancelled())->toBeFalse()
            ->and(OrderStatus::Cancelled->canBeCancelled())->toBeFalse()
            ->and(OrderStatus::Refunded->canBeCancelled())->toBeFalse();
    });

    it('identifies which orders can be refunded', function () {
        expect(OrderStatus::Completed->canBeRefunded())->toBeTrue()
            ->and(OrderStatus::Shipped->canBeRefunded())->toBeTrue()
            ->and(OrderStatus::Pending->canBeRefunded())->toBeFalse()
            ->and(OrderStatus::Processing->canBeRefunded())->toBeFalse()
            ->and(OrderStatus::Cancelled->canBeRefunded())->toBeFalse()
            ->and(OrderStatus::Refunded->canBeRefunded())->toBeFalse();
    });

    it('returns correct options array', function () {
        $options = OrderStatus::options();

        expect($options)->toBe([
            'pending'    => 'Pendente',
            'processing' => 'Processando',
            'shipped'    => 'Enviado',
            'completed'  => 'Entregue',
            'cancelled'  => 'Cancelado',
            'refunded'   => 'Reembolsado',
        ]);
    });

    it('can be created from value', function () {
        expect(OrderStatus::from('pending'))->toBe(OrderStatus::Pending)
            ->and(OrderStatus::from('processing'))->toBe(OrderStatus::Processing)
            ->and(OrderStatus::from('shipped'))->toBe(OrderStatus::Shipped)
            ->and(OrderStatus::from('completed'))->toBe(OrderStatus::Completed);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(OrderStatus::tryFrom('invalid'))->toBeNull();
    });
});
