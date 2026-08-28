<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;

class StartAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // which day of the plan the app is showing; verified server-side
            'curriculum_day_id' => ['nullable', 'uuid'],
        ];
    }
}
