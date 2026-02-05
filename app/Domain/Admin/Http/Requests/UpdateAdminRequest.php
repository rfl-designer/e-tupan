<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
        $adminId = $this->route('administrator')?->id;

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'role'      => ['required', 'string', Rule::in(['master', 'operator'])],
            'is_active' => ['required', 'boolean'],
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
            'name.required'      => 'O nome é obrigatório.',
            'name.max'           => 'O nome não pode ter mais de 255 caracteres.',
            'email.required'     => 'O email é obrigatório.',
            'email.email'        => 'O email deve ser um endereço válido.',
            'email.unique'       => 'Este email já está em uso.',
            'role.required'      => 'O papel é obrigatório.',
            'role.in'            => 'O papel deve ser master ou operator.',
            'is_active.required' => 'O status é obrigatório.',
            'is_active.boolean'  => 'O status deve ser verdadeiro ou falso.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }
}
