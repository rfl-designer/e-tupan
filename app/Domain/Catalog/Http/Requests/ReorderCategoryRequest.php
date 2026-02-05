<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCategoryRequest extends FormRequest
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
            'order.*' => ['required', 'integer', 'exists:categories,id'],
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
            'order.required' => 'A ordem das categorias é obrigatória.',
            'order.array'    => 'A ordem deve ser uma lista de IDs.',
            'order.*.exists' => 'Uma das categorias não existe.',
        ];
    }
}
