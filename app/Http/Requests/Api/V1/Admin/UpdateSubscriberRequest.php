<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SubscriberStatus;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateSubscriberRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subscriberId = $this->route('subscriber')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', new PhoneNumberRule, Rule::unique('subscribers', 'phone')->ignore($subscriberId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['sometimes', new Enum(SubscriberStatus::class)],
        ];
    }
}
