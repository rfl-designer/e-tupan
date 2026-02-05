<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Requests;

use App\Domain\Catalog\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'slug')],
            'type' => ['required', Rule::enum(AttributeType::class)],
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
            'name.required' => 'O nome do atributo é obrigatório.',
            'name.max'      => 'O nome não pode ter mais de 255 caracteres.',
            'slug.unique'   => 'Este slug já está em uso.',
            'slug.max'      => 'O slug não pode ter mais de 255 caracteres.',
            'type.required' => 'O tipo do atributo é obrigatório.',
            'type.enum'     => 'O tipo selecionado é inválido.',
        ];
    }
}
