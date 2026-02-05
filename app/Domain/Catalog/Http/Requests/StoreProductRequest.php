<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Requests;

use App\Domain\Catalog\Enums\{ProductStatus, ProductType};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description'       => ['nullable', 'string'],
            'type'              => ['required', Rule::enum(ProductType::class)],
            'status'            => ['required', Rule::enum(ProductStatus::class)],

            // Prices (in reais, will be converted to centavos)
            'price'         => ['required', 'numeric', 'min:0'],
            'sale_price'    => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'sale_start_at' => ['nullable', 'date'],
            'sale_end_at'   => ['nullable', 'date', 'after:sale_start_at'],
            'cost'          => ['nullable', 'numeric', 'min:0'],

            // Stock
            'sku'              => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'stock_quantity'   => ['required', 'integer', 'min:0'],
            'manage_stock'     => ['boolean'],
            'allow_backorders' => ['boolean'],

            // Dimensions
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width'  => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],

            // SEO
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            // Relationships
            'categories'   => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['integer', 'exists:tags,id'],
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
            'name.required'           => 'O nome do produto é obrigatório.',
            'name.max'                => 'O nome não pode ter mais de 255 caracteres.',
            'slug.unique'             => 'Este slug já está em uso.',
            'short_description.max'   => 'A descrição curta não pode ter mais de 500 caracteres.',
            'type.required'           => 'O tipo do produto é obrigatório.',
            'status.required'         => 'O status do produto é obrigatório.',
            'price.required'          => 'O preço é obrigatório.',
            'price.min'               => 'O preço não pode ser negativo.',
            'sale_price.lt'           => 'O preço promocional deve ser menor que o preço normal.',
            'sale_end_at.after'       => 'A data final da promoção deve ser posterior à data inicial.',
            'sku.unique'              => 'Este SKU já está em uso.',
            'stock_quantity.required' => 'A quantidade em estoque é obrigatória.',
            'stock_quantity.min'      => 'A quantidade em estoque não pode ser negativa.',
            'categories.*.exists'     => 'Uma das categorias selecionadas não existe.',
            'tags.*.exists'           => 'Uma das tags selecionadas não existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'manage_stock'     => $this->boolean('manage_stock'),
            'allow_backorders' => $this->boolean('allow_backorders'),
        ]);
    }
}
