<?php declare(strict_types = 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use LaravelLegends\PtBrValidator\Rules\Cpf;

class CpfRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpfValidator = new Cpf();

        if (!$cpfValidator->passes($attribute, $value)) {
            $fail('O CPF informado não é válido.');
        }
    }
}
