<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order'   => ['required', 'array'],
            'order.*' => ['required', 'string', 'exists:banners,id'],
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
            'order.required' => 'A ordem dos banners é obrigatória.',
            'order.array'    => 'A ordem deve ser uma lista de IDs.',
            'order.*.exists' => 'Um dos banners não existe.',
        ];
    }
}
