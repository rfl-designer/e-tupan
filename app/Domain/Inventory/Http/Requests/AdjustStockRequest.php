<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Http\Requests;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stockable_type' => ['required', 'string', Rule::in(['product', 'variant'])],
            'stockable_id'   => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('stockable_type');

                    $model = match ($type) {
                        'product' => Product::class,
                        'variant' => ProductVariant::class,
                        default   => null,
                    };

                    if ($model === null) {
                        $fail('Invalid stockable type.');

                        return;
                    }

                    if (!$model::query()->where('id', $value)->exists()) {
                        $fail('The selected stockable does not exist.');
                    }
                },
            ],
            'movement_type' => [
                'required',
                'string',
                Rule::in([
                    MovementType::ManualEntry->value,
                    MovementType::ManualExit->value,
                    MovementType::Adjustment->value,
                ]),
            ],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'notes'    => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stockable_type' => 'tipo de item',
            'stockable_id'   => 'item',
            'movement_type'  => 'tipo de movimentacao',
            'quantity'       => 'quantidade',
            'notes'          => 'motivo/observacao',
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
            'quantity.not_in' => 'A quantidade nao pode ser zero.',
            'notes.min'       => 'O motivo deve ter pelo menos :min caracteres.',
        ];
    }

    /**
     * Get the movement type enum.
     */
    public function getMovementType(): MovementType
    {
        return MovementType::from($this->validated('movement_type'));
    }

    /**
     * Get the adjusted quantity (negative for exit types).
     */
    public function getAdjustedQuantity(): int
    {
        $quantity = (int) $this->validated('quantity');
        $type     = $this->getMovementType();

        // Manual exit should always be negative
        if ($type === MovementType::ManualExit && $quantity > 0) {
            return -$quantity;
        }

        // Manual entry should always be positive
        if ($type === MovementType::ManualEntry && $quantity < 0) {
            return abs($quantity);
        }

        return $quantity;
    }
}
