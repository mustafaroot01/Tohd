<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'curriculum_id' => ['required', 'uuid', 'exists:curriculums,id'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'status' => ['nullable', new Enum(ProductStatus::class)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
