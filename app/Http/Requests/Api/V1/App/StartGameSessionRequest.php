<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;

class StartGameSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curriculum_day_id' => ['nullable', 'uuid', 'exists:curriculum_days,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
