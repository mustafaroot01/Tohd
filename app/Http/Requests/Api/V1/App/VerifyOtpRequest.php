<?php

namespace App\Http\Requests\Api\V1\App;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule],
            'code' => ['required', 'string', 'digits:6'],
            // handed out by register; only its holder can complete the account
            'signup_token' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'code.required' => 'رمز التحقق مطلوب',
            'code.digits' => 'الرمز يتكوّن من ٦ أرقام',
            'signup_token.required' => 'انتهت جلسة التسجيل، أعد إدخال بياناتك',
        ];
    }
}
