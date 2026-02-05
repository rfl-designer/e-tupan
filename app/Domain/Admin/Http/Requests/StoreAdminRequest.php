<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Domain\Admin\Models\Admin|null $admin */
        $admin = auth('admin')->user();

        return $admin?->isMaster() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'role'  => ['required', 'string', Rule::in(['master', 'operator'])],
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
            'name.required'  => 'O nome é obrigatório.',
            'name.max'       => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email'    => 'O email deve ser um endereço válido.',
            'email.unique'   => 'Este email já está em uso.',
            'role.required'  => 'O papel é obrigatório.',
            'role.in'        => 'O papel deve ser master ou operator.',
        ];
    }
}
