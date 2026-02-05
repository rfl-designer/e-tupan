<?php

declare(strict_types = 1);

use App\Rules\ValidCreditCard;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

describe('ValidCreditCard Rule', function () {
    describe('Valid Card Numbers (Luhn Algorithm)', function () {
        it('accepts valid Visa card', function () {
            $validator = Validator::make(
                ['card' => '4532015112830366'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts valid Visa card with spaces', function () {
            $validator = Validator::make(
                ['card' => '4532 0151 1283 0366'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts valid Mastercard', function () {
            $validator = Validator::make(
                ['card' => '5425233430109903'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts valid American Express', function () {
            $validator = Validator::make(
                ['card' => '374245455400126'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts valid Elo card', function () {
            // Elo test card number
            $validator = Validator::make(
                ['card' => '6362970000457013'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('Invalid Card Numbers', function () {
        it('rejects card with invalid Luhn checksum', function () {
            $validator = Validator::make(
                ['card' => '4532015112830367'], // Changed last digit
                ['card' => new ValidCreditCard()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects card with too few digits', function () {
            $validator = Validator::make(
                ['card' => '453201511283'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects card with too many digits', function () {
            $validator = Validator::make(
                ['card' => '45320151128303661234'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects empty card number when required', function () {
            $validator = Validator::make(
                ['card' => ''],
                ['card' => ['required', new ValidCreditCard()]],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects card with letters', function () {
            $validator = Validator::make(
                ['card' => '4532ABCD12830366'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects all zeros', function () {
            $validator = Validator::make(
                ['card' => '0000000000000000'],
                ['card' => new ValidCreditCard()],
            );

            expect($validator->fails())->toBeTrue();
        });
    });

    describe('Card Brand Detection', function () {
        it('detects Visa cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('4532015112830366'))->toBe('visa');
        });

        it('detects Mastercard cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('5425233430109903'))->toBe('mastercard');
        });

        it('detects Mastercard 2-series cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('2221000000000009'))->toBe('mastercard');
        });

        it('detects American Express cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('374245455400126'))->toBe('amex');
        });

        it('detects Elo cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('6362970000457013'))->toBe('elo');
        });

        it('returns unknown for unrecognized cards', function () {
            $rule = new ValidCreditCard();
            expect($rule->detectBrand('9999999999999999'))->toBe('unknown');
        });
    });

    describe('Error Message', function () {
        it('returns correct error message', function () {
            $validator = Validator::make(
                ['card' => '1234567890123456'],
                ['card' => new ValidCreditCard()],
            );

            $validator->fails();
            $errors = $validator->errors()->get('card');

            expect($errors)->toContain('O numero do cartao e invalido.');
        });
    });
});
