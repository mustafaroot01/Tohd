<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:products,slug,'.$productId],
            'description' => ['nullable', 'string'],
            'curriculum_id' => ['sometimes', 'required', 'uuid', 'exists:curriculums,id'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
