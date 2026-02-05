<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;

describe('CartStatus Enum', function () {
    it('has correct values', function () {
        expect(CartStatus::Active->value)->toBe('active')
            ->and(CartStatus::Abandoned->value)->toBe('abandoned')
            ->and(CartStatus::Converted->value)->toBe('converted');
    });

    it('has correct labels', function () {
        expect(CartStatus::Active->label())->toBe('Ativo')
            ->and(CartStatus::Abandoned->label())->toBe('Abandonado')
            ->and(CartStatus::Converted->label())->toBe('Convertido');
    });

    it('has correct colors', function () {
        expect(CartStatus::Active->color())->toBe('lime')
            ->and(CartStatus::Abandoned->color())->toBe('amber')
            ->and(CartStatus::Converted->color())->toBe('sky');
    });

    it('returns all options', function () {
        $options = CartStatus::options();

        expect($options)->toBe([
            'active'    => 'Ativo',
            'abandoned' => 'Abandonado',
            'converted' => 'Convertido',
        ]);
    });

    it('has three cases', function () {
        expect(CartStatus::cases())->toHaveCount(3);
    });
});
