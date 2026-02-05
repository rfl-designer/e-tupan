<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
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
            'title'         => ['required', 'string', 'max:255'],
            'image_desktop' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_mobile'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'link'          => ['nullable', 'string', 'max:500'],
            'alt_text'      => ['nullable', 'string', 'max:255'],
            'is_active'     => ['nullable', 'boolean'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
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
            'title.required'         => 'O título do banner é obrigatório.',
            'title.max'              => 'O título não pode ter mais de 255 caracteres.',
            'image_desktop.image'    => 'O arquivo deve ser uma imagem.',
            'image_desktop.mimes'    => 'A imagem deve ser do tipo JPG, PNG ou WebP.',
            'image_desktop.max'      => 'A imagem não pode ter mais de 2MB.',
            'image_mobile.image'     => 'O arquivo deve ser uma imagem.',
            'image_mobile.mimes'     => 'A imagem deve ser do tipo JPG, PNG ou WebP.',
            'image_mobile.max'       => 'A imagem não pode ter mais de 2MB.',
            'link.max'               => 'O link não pode ter mais de 500 caracteres.',
            'alt_text.max'           => 'O texto alternativo não pode ter mais de 255 caracteres.',
            'starts_at.date'         => 'A data de início deve ser uma data válida.',
            'ends_at.date'           => 'A data de fim deve ser uma data válida.',
            'ends_at.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ];
    }
}
