<?php

namespace App\Http\Requests\Api\V1\App;

use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $subscriberId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', new PhoneNumberRule, Rule::unique('subscribers', 'phone')->ignore($subscriberId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
