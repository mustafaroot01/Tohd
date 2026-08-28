<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidGameConfigRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('يجب أن تكون إعدادات اللعبة كائناً بصيغة JSON صالحة.');

            return;
        }

        if (isset($value['success_threshold'])) {
            $threshold = (float) $value['success_threshold'];
            if ($threshold < 0.1 || $threshold > 1.0) {
                $fail('معيار النجاح (success_threshold) يجب أن يكون بين 0.1 و 1.0.');
            }
        }

        // 0 is the agreed encoding for "unlimited attempts".
        if (isset($value['attempts'])) {
            $attempts = (int) $value['attempts'];
            if ($attempts !== 0 && ($attempts < 1 || $attempts > 100)) {
                $fail('عدد المحاولات يجب أن يكون بين 1 و 100، أو 0 للمحاولات غير المحدودة.');
            }
        }

        if (isset($value['time_limit_seconds'])) {
            $timeLimit = (int) $value['time_limit_seconds'];
            if ($timeLimit < 5 || $timeLimit > 3600) {
                $fail('الحد الزمني بالثواني (time_limit_seconds) يجب أن يكون بين 5 و 3600 ثانية.');
            }
        }
    }
}
