<?php

declare(strict_types = 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCreditCard implements ValidationRule
{
    /**
     * Card brand patterns.
     *
     * @var array<string, string>
     */
    protected array $brandPatterns = [
        'visa'       => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'mastercard' => '/^(5[1-5][0-9]{14}|2[2-7][0-9]{14})$/',
        'amex'       => '/^3[47][0-9]{13}$/',
        'elo'        => '/^(636368|636369|438935|504175|451416|636297|5067|4576|4011|506699)[0-9]*$/',
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValid($value)) {
            $fail('O numero do cartao e invalido.');
        }
    }

    /**
     * Validate the credit card number using Luhn algorithm.
     */
    public function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Remove non-numeric characters
        $cardNumber = preg_replace('/\D/', '', $value);

        // Must have between 13 and 19 digits
        $length = strlen($cardNumber);

        if ($length < 13 || $length > 19) {
            return false;
        }

        // Reject all zeros
        if (preg_match('/^0+$/', $cardNumber)) {
            return false;
        }

        return $this->luhnCheck($cardNumber);
    }

    /**
     * Perform Luhn algorithm check.
     */
    protected function luhnCheck(string $cardNumber): bool
    {
        $sum    = 0;
        $length = strlen($cardNumber);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $cardNumber[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * Detect the card brand from the card number.
     */
    public function detectBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        foreach ($this->brandPatterns as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }

        return 'unknown';
    }
}
