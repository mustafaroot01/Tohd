<?php

namespace App\Http\Requests\Api\V1\App;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subscriberId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', new PhoneNumberRule, Rule::unique('subscribers', 'phone')->ignore($subscriberId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            // changing the password requires proving you know the current one,
            // otherwise a leaked token is a permanent account takeover
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
