<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove non-numeric characters
        $cpf = preg_replace('/\D/', '', (string) $value);

        // Check if has 11 digits
        if (strlen($cpf) !== 11) {
            $fail('O CPF deve conter 11 digitos.');

            return;
        }

        // Check for known invalid CPFs (all same digits)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado e invalido.');

            return;
        }

        // Validate first check digit
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder       = $sum % 11;
        $firstCheckDigit = ($remainder < 2) ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $firstCheckDigit) {
            $fail('O CPF informado e invalido.');

            return;
        }

        // Validate second check digit
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder        = $sum % 11;
        $secondCheckDigit = ($remainder < 2) ? 0 : 11 - $remainder;

        if ((int) $cpf[10] !== $secondCheckDigit) {
            $fail('O CPF informado e invalido.');
        }
    }
}
