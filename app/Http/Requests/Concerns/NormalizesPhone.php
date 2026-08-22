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
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneNumber::normalize($this->input('phone')) ?? $this->input('phone'),
            ]);
        }
    }
}
