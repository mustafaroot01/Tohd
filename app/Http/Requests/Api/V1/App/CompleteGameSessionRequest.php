<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;

class CompleteGameSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attempts' => ['required', 'integer', 'min:1', 'max:500'],
            'correct_attempts' => ['required', 'integer', 'min:0', 'max:500'],
            'incorrect_attempts' => ['nullable', 'integer', 'min:0', 'max:500'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:7200'],
            'score' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'attempts.required' => 'حقل عدد المحاولات إلزامي.',
            'correct_attempts.required' => 'حقل المحاولات الصحيحة إلزامي.',
            'duration_seconds.required' => 'مدة الجلسة بالثواني إلزامية.',
        ];
    }
}
