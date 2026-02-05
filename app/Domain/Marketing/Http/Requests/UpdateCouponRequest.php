<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Http\Requests;

use App\Domain\Marketing\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($this->route('coupon')),
            ],
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:1000'],
            'type'                 => ['required', Rule::enum(CouponType::class)],
            'value'                => ['nullable', 'numeric', 'min:0', 'required_unless:type,free_shipping'],
            'minimum_order_value'  => ['nullable', 'numeric', 'min:0'],
            'maximum_discount'     => ['nullable', 'numeric', 'min:0'],
            'usage_limit'          => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at'            => ['nullable', 'date'],
            'expires_at'           => ['nullable', 'date', 'after:starts_at'],
            'is_active'            => ['boolean'],
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
            'code.required'                => 'O código do cupom é obrigatório.',
            'code.max'                     => 'O código não pode ter mais de 50 caracteres.',
            'code.unique'                  => 'Este código de cupom já está em uso.',
            'name.required'                => 'O nome do cupom é obrigatório.',
            'name.max'                     => 'O nome não pode ter mais de 255 caracteres.',
            'description.max'              => 'A descrição não pode ter mais de 1000 caracteres.',
            'type.required'                => 'O tipo do cupom é obrigatório.',
            'type.enum'                    => 'O tipo selecionado é inválido.',
            'value.required_unless'        => 'O valor é obrigatório para cupons de porcentagem e valor fixo.',
            'value.numeric'                => 'O valor deve ser um número.',
            'value.min'                    => 'O valor não pode ser negativo.',
            'minimum_order_value.numeric'  => 'O valor mínimo do pedido deve ser um número.',
            'minimum_order_value.min'      => 'O valor mínimo do pedido não pode ser negativo.',
            'maximum_discount.numeric'     => 'O desconto máximo deve ser um número.',
            'maximum_discount.min'         => 'O desconto máximo não pode ser negativo.',
            'usage_limit.integer'          => 'O limite de uso deve ser um número inteiro.',
            'usage_limit.min'              => 'O limite de uso deve ser pelo menos 1.',
            'usage_limit_per_user.integer' => 'O limite por usuário deve ser um número inteiro.',
            'usage_limit_per_user.min'     => 'O limite por usuário deve ser pelo menos 1.',
            'starts_at.date'               => 'A data de início deve ser uma data válida.',
            'expires_at.date'              => 'A data de expiração deve ser uma data válida.',
            'expires_at.after'             => 'A data de expiração deve ser posterior à data de início.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert monetary values from reais to cents
        $data = [];

        if ($this->filled('value') && $this->input('type') === CouponType::Fixed->value) {
            $data['value'] = (int) ($this->input('value') * 100);
        }

        if ($this->filled('minimum_order_value')) {
            $data['minimum_order_value'] = (int) ($this->input('minimum_order_value') * 100);
        }

        if ($this->filled('maximum_discount')) {
            $data['maximum_discount'] = (int) ($this->input('maximum_discount') * 100);
        }

        if (!$this->has('is_active')) {
            $data['is_active'] = false;
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}
