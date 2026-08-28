<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SubscriberStatus;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriberRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new PhoneNumberRule, 'unique:subscribers,phone'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in([SubscriberStatus::ACTIVE->value, SubscriberStatus::SUSPENDED->value])],
        ];
    }
}
