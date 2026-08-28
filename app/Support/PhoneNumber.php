<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a raw phone number input into full E.164 format (e.g. +9647701234567).
     * Accepts local Iraqi format (07XXXXXXXXX), or already-international variants
     * (+9647XXXXXXXXX, 9647XXXXXXXXX, 009647XXXXXXXXX). Returns null if unparsable.
     */
    public static function normalize(string $raw): ?string
    {
        $countryCode = config('services.otp.country_code', '964');
        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        if (str_starts_with($digits, $countryCode)) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+'.$countryCode.substr($digits, 1);
        }

        return '+'.$countryCode.$digits;
    }

    /**
     * Strip the leading "+" for gateways that expect digits-only international format.
     */
    public static function digitsOnly(string $e164Phone): string
    {
        return ltrim($e164Phone, '+');
    }
}
