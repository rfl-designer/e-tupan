<?php declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductType;

describe('ProductType', function () {
    it('has all expected cases', function () {
        $cases = ProductType::cases();

        expect($cases)->toHaveCount(2)
            ->and(ProductType::Simple->value)->toBe('simple')
            ->and(ProductType::Variable->value)->toBe('variable');
    });

    it('returns correct label for Simple', function () {
        expect(ProductType::Simple->label())->toBe('Produto Simples');
    });

    it('returns correct label for Variable', function () {
        expect(ProductType::Variable->label())->toBe('Produto Variável');
    });

    it('returns correct options array', function () {
        $options = ProductType::options();

        expect($options)->toBe([
            'simple'   => 'Produto Simples',
            'variable' => 'Produto Variável',
        ]);
    });

    it('can be created from value', function () {
        expect(ProductType::from('simple'))->toBe(ProductType::Simple)
            ->and(ProductType::from('variable'))->toBe(ProductType::Variable);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(ProductType::tryFrom('invalid'))->toBeNull();
    });
});
