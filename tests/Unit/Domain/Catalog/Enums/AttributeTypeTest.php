<?php declare(strict_types = 1);

use App\Domain\Catalog\Enums\AttributeType;

describe('AttributeType', function () {
    it('has all expected cases', function () {
        $cases = AttributeType::cases();

        expect($cases)->toHaveCount(3)
            ->and(AttributeType::Select->value)->toBe('select')
            ->and(AttributeType::Color->value)->toBe('color')
            ->and(AttributeType::Text->value)->toBe('text');
    });

    it('returns correct label for Select', function () {
        expect(AttributeType::Select->label())->toBe('Seleção');
    });

    it('returns correct label for Color', function () {
        expect(AttributeType::Color->label())->toBe('Cor');
    });

    it('returns correct label for Text', function () {
        expect(AttributeType::Text->label())->toBe('Texto');
    });

    it('returns correct options array', function () {
        $options = AttributeType::options();

        expect($options)->toBe([
            'select' => 'Seleção',
            'color'  => 'Cor',
            'text'   => 'Texto',
        ]);
    });

    it('can be created from value', function () {
        expect(AttributeType::from('select'))->toBe(AttributeType::Select)
            ->and(AttributeType::from('color'))->toBe(AttributeType::Color)
            ->and(AttributeType::from('text'))->toBe(AttributeType::Text);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(AttributeType::tryFrom('invalid'))->toBeNull();
    });
});
