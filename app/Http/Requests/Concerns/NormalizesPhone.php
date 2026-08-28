<?php

namespace App\Http\Requests\Concerns;

use App\Support\PhoneNumber;

/**
 * Rewrites an incoming `phone` field to E.164 before validation runs, so the
 * unique index and every downstream lookup see one canonical form.
 *
 * An unparsable number is left untouched on purpose — PhoneNumberRule is the
 * one that reports it, and it should report what the user actually typed.
 */
trait NormalizesPhone
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        // whatever the caller sent — an array, an object — must reach the
        // validator untouched instead of crashing the normaliser
        if (is_string($phone) || is_int($phone) || is_float($phone)) {
            $this->merge([
                'phone' => PhoneNumber::normalize((string) $phone) ?? $phone,
            ]);
        }
    }
}
