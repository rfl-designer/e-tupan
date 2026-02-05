<?php

declare(strict_types = 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValid($value)) {
            $fail('O CPF informado e invalido.');
        }
    }

    /**
     * Validate the CPF number.
     */
    public function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Remove non-numeric characters
        $cpf = preg_replace('/\D/', '', $value);

        // Must have exactly 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Reject CPFs with all same digits
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder       = $sum % 11;
        $firstCheckDigit = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $firstCheckDigit) {
            return false;
        }

        // Validate second check digit
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder        = $sum % 11;
        $secondCheckDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $secondCheckDigit;
    }
}
