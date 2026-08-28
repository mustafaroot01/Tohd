<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGovernorateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** A cleared field in the dialog arrives as null; the columns are NOT NULL. */
    protected function prepareForValidation(): void
    {
        foreach (['sort_order' => 0, 'is_active' => true] as $field => $default) {
            if ($this->exists($field) && $this->input($field) === null) {
                $this->merge([$field => $default]);
            }
        }
    }

    public function rules(): array
    {
        $id = $this->route('governorate')?->id;

        return [
            // `sometimes` throughout, so hiding one is a single-field request
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('governorates', 'name')->ignore($id)],
            'code' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('governorates', 'code')->ignore($id)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
