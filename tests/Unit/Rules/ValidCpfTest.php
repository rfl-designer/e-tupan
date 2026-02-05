<?php

declare(strict_types = 1);

use App\Rules\ValidCpf;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

describe('ValidCpf Rule', function () {
    describe('Valid CPFs', function () {
        it('accepts valid CPF with formatting', function () {
            $validator = Validator::make(
                ['cpf' => '529.982.247-25'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts valid CPF without formatting', function () {
            $validator = Validator::make(
                ['cpf' => '52998224725'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts another valid CPF', function () {
            $validator = Validator::make(
                ['cpf' => '111.444.777-35'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('accepts CPF with spaces', function () {
            $validator = Validator::make(
                ['cpf' => '529 982 247 25'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('Invalid CPFs', function () {
        it('rejects CPF with all same digits', function () {
            $invalidCpfs = [
                '000.000.000-00',
                '111.111.111-11',
                '222.222.222-22',
                '333.333.333-33',
                '444.444.444-44',
                '555.555.555-55',
                '666.666.666-66',
                '777.777.777-77',
                '888.888.888-88',
                '999.999.999-99',
            ];

            foreach ($invalidCpfs as $cpf) {
                $validator = Validator::make(
                    ['cpf' => $cpf],
                    ['cpf' => new ValidCpf()],
                );

                expect($validator->fails())->toBeTrue("CPF {$cpf} should be invalid");
            }
        });

        it('rejects CPF with wrong check digits', function () {
            $validator = Validator::make(
                ['cpf' => '529.982.247-26'], // Last digit should be 5, not 6
                ['cpf' => new ValidCpf()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects CPF with too few digits', function () {
            $validator = Validator::make(
                ['cpf' => '529.982.247'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects CPF with too many digits', function () {
            $validator = Validator::make(
                ['cpf' => '529.982.247-251'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects empty CPF when required', function () {
            $validator = Validator::make(
                ['cpf' => ''],
                ['cpf' => ['required', new ValidCpf()]],
            );

            expect($validator->fails())->toBeTrue();
        });

        it('rejects CPF with letters', function () {
            $validator = Validator::make(
                ['cpf' => '529.982.ABC-25'],
                ['cpf' => new ValidCpf()],
            );

            expect($validator->fails())->toBeTrue();
        });
    });

    describe('Error Message', function () {
        it('returns correct error message', function () {
            $validator = Validator::make(
                ['cpf' => '000.000.000-00'],
                ['cpf' => new ValidCpf()],
            );

            $validator->fails();
            $errors = $validator->errors()->get('cpf');

            expect($errors)->toContain('O CPF informado e invalido.');
        });
    });
});
