<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'حقل رقم الهاتف مطلوب.',
            'code.required' => 'حقل رمز التحقق مطلوب.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
        ];
    }
}
