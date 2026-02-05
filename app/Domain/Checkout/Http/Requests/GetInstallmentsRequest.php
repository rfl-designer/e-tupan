<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetInstallmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount'     => ['required', 'integer', 'min:100'],
            'card_brand' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'O valor é obrigatório.',
            'amount.integer'  => 'O valor deve ser um número inteiro.',
            'amount.min'      => 'O valor mínimo é :min centavos.',
            'card_brand.max'  => 'A bandeira do cartão não pode ter mais de :max caracteres.',
        ];
    }
}
