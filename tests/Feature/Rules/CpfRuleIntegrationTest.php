<?php declare(strict_types = 1);

use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Support\Facades\Validator;

describe('CpfRule Integration', function () {
    it('works with Laravel validator for valid CPF', function () {
        $validator = Validator::make(
            ['cpf' => '529.982.247-25'],
            ['cpf' => [new CpfRule()]],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation with Laravel validator for invalid CPF', function () {
        $validator = Validator::make(
            ['cpf' => '123.456.789-00'],
            ['cpf' => [new CpfRule()]],
        );

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('cpf'))->toBe('O CPF informado não é válido.');
    });

    it('validates unique CPF constraint in database', function () {
        $existingUser = User::factory()->create([
            'cpf' => '529.982.247-25',
        ]);

        $validator = Validator::make(
            ['cpf' => '529.982.247-25'],
            ['cpf' => ['unique:users,cpf']],
        );

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('cpf'))->toBeTrue();
    });

    it('allows null CPF for existing users', function () {
        $user = User::factory()->withoutCpf()->create();

        expect($user->cpf)->toBeNull();
    });

    it('can create user with valid CPF', function () {
        $user = User::factory()->create([
            'cpf' => '529.982.247-25',
        ]);

        expect($user->cpf)->toBe('529.982.247-25');
    });

    it('generates valid CPF in factory', function () {
        $user   = User::factory()->create();
        $rule   = new CpfRule();
        $failed = false;

        $rule->validate('cpf', $user->cpf, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});
