<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SubscriberStatus;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSubscriberRequest extends FormRequest
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
            'status' => ['nullable', new Enum(SubscriberStatus::class)],
        ];
    }
}
