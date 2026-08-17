<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skillId = $this->route('skill')?->id ?? $this->route('skill');

        return [
            'axis_id' => ['sometimes', 'required', 'uuid', 'exists:axes,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:skills,slug,'.$skillId],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
