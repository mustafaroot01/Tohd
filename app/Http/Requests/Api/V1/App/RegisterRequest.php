<?php

namespace App\Http\Requests\Api\V1\App;

use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneNumber::normalize($this->input('phone')) ?? $this->input('phone'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new PhoneNumberRule, 'unique:subscribers,phone'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل الاسم مطلوب.',
            'phone.required' => 'حقل رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً في النظام.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
        ];
    }
}
