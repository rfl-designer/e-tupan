<?php declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;

describe('ProductStatus', function () {
    it('has all expected cases', function () {
        $cases = ProductStatus::cases();

        expect($cases)->toHaveCount(3)
            ->and(ProductStatus::Draft->value)->toBe('draft')
            ->and(ProductStatus::Active->value)->toBe('active')
            ->and(ProductStatus::Inactive->value)->toBe('inactive');
    });

    it('returns correct label for Draft', function () {
        expect(ProductStatus::Draft->label())->toBe('Rascunho');
    });

    it('returns correct label for Active', function () {
        expect(ProductStatus::Active->label())->toBe('Ativo');
    });

    it('returns correct label for Inactive', function () {
        expect(ProductStatus::Inactive->label())->toBe('Inativo');
    });

    it('returns correct options array', function () {
        $options = ProductStatus::options();

        expect($options)->toBe([
            'draft'    => 'Rascunho',
            'active'   => 'Ativo',
            'inactive' => 'Inativo',
        ]);
    });

    it('can be created from value', function () {
        expect(ProductStatus::from('draft'))->toBe(ProductStatus::Draft)
            ->and(ProductStatus::from('active'))->toBe(ProductStatus::Active)
            ->and(ProductStatus::from('inactive'))->toBe(ProductStatus::Inactive);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(ProductStatus::tryFrom('invalid'))->toBeNull();
    });
});
