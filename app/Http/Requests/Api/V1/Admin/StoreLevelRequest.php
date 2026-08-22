<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_number' => ['required', 'integer', 'min:1', 'unique:levels,level_number'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_age' => ['required', 'integer', 'min:1'],
            'max_age' => ['required', 'integer', 'min:1', 'gte:min_age'],
        ];
    }
}
