<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurriculumDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_number' => ['nullable', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'estimated_duration_seconds' => ['nullable', 'integer', 'min:60', 'max:7200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
