<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;

class RedeemActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:8', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'حقل كود التفعيل إلزامي.',
            'code.string' => 'صيغة كود التفعيل غير صحيحة.',
        ];
    }
}
