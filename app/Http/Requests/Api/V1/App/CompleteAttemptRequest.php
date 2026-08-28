<?php

namespace App\Http\Requests\Api\V1\App;

use App\Services\AttemptTokenService;
use Illuminate\Foundation\Http\FormRequest;

class CompleteAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // bounded before it is ever decrypted
            'attempt_token' => ['required', 'string', 'max:'.AttemptTokenService::MAX_LENGTH],
            // the app's own count of seconds watched; only ever lowers the grade
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
