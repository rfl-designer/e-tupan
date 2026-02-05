<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AdminResetPasswordRequest extends FormRequest
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
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
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
            'token.required'     => __('O token de redefinição é obrigatório.'),
            'email.required'     => __('O email é obrigatório.'),
            'email.email'        => __('O email deve ser um endereço de email válido.'),
            'password.required'  => __('A senha é obrigatória.'),
            'password.confirmed' => __('A confirmação da senha não confere.'),
        ];
    }
}
