<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchTrackingRequest extends FormRequest
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
            'code' => ['required', 'string', 'min:5', 'max:50'],
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
            'code.required' => 'O código de rastreio é obrigatório.',
            'code.string'   => 'O código de rastreio deve ser um texto.',
            'code.min'      => 'O código de rastreio deve ter pelo menos :min caracteres.',
            'code.max'      => 'O código de rastreio não pode ter mais de :max caracteres.',
        ];
    }
}
