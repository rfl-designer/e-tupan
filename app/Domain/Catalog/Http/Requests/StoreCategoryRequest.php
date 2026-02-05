<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Requests;

use App\Domain\Catalog\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'parent_id'        => ['nullable', 'integer', Rule::exists('categories', 'id'), $this->maxDepthRule()],
            'description'      => ['nullable', 'string'],
            'image'            => ['nullable', 'image', 'max:2048'],
            'meta_title'       => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_active'        => ['boolean'],
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
            'name.required'        => 'O nome da categoria é obrigatório.',
            'name.max'             => 'O nome não pode ter mais de 255 caracteres.',
            'slug.unique'          => 'Este slug já está em uso.',
            'slug.max'             => 'O slug não pode ter mais de 255 caracteres.',
            'parent_id.exists'     => 'A categoria pai selecionada não existe.',
            'image.image'          => 'O arquivo deve ser uma imagem.',
            'image.max'            => 'A imagem não pode ter mais de 2MB.',
            'meta_title.max'       => 'O título SEO não pode ter mais de 60 caracteres.',
            'meta_description.max' => 'A descrição SEO não pode ter mais de 160 caracteres.',
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

    /**
     * Custom validation rule for max depth.
     */
    private function maxDepthRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }

            $parent = Category::find($value);

            if ($parent === null) {
                return; // Will be caught by exists rule
            }

            if (!$parent->canHaveChildren()) {
                $fail('Não é possível criar subcategorias além do nível ' . Category::MAX_DEPTH . '.');
            }
        };
    }
}
