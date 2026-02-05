<?php declare(strict_types = 1);

use App\Rules\CpfRule;

describe('CpfRule', function () {
    it('passes with a valid CPF with mask', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '529.982.247-25', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('passes with a valid CPF without mask', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '52998224725', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails with an invalid CPF', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '111.111.111-11', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails with a CPF with wrong check digits', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '529.982.247-26', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails with a CPF with invalid length', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '123.456.789', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails with an empty CPF', function () {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', '', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails with all same digits', function (string $cpf) {
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', $cpf, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    })->with([
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
    ]);
});
