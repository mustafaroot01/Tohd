<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Every field is `sometimes` so a partial update works, the way it already does
 * for axes, skills and products. The controller used to require the whole
 * payload on PUT, which made a one-field edit impossible.
 */
class UpdateLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levelId = $this->route('level')?->id ?? $this->route('level');

        return [
            'level_number' => ['sometimes', 'required', 'integer', 'min:1', 'unique:levels,level_number,'.$levelId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'min_age' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_age' => ['sometimes', 'required', 'integer', 'min:1', 'gte:min_age'],
        ];
    }
}
