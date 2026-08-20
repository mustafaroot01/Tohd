<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('رقم الهاتف غير صالح.');

            return;
        }

        $normalized = PhoneNumber::normalize($value);

        if (! $normalized || ! preg_match('/^\+964\d{10}$/', $normalized)) {
            $fail('رقم الهاتف يجب أن يكون رقماً عراقياً صالحاً (مثال: 07701234567).');
        }
    }
}
