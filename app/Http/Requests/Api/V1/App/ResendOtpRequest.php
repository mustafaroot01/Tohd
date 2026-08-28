<?php

namespace App\Http\Requests\Api\V1\App;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

/** "لم يصلني الرمز": the server works out whether it is a signup or a reset. */
class ResendOtpRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return ['phone.required' => 'رقم الهاتف مطلوب'];
    }
}
