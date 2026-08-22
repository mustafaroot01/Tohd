<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'purpose' => ['nullable', 'string', Rule::in(['PHONE_VERIFICATION', 'PASSWORD_RESET'])],
        ];
    }
}
