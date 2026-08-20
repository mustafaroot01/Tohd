<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'default_country_code' => env('OTP_DEFAULT_COUNTRY_CODE', '964'),
    'length' => 6,
    'expiry_minutes' => 5,
    'max_verify_attempts' => 5,
    'resend_cooldown_seconds' => 60,
    'max_resend_per_hour' => 5,

    'otpiq' => [
        'base_url' => env('OTPIQ_BASE_URL', 'https://api.otpiq.com/api/sms'),
        'api_key' => env('OTPIQ_API_KEY'),
        'sender_id' => env('OTPIQ_SENDER_ID'),
        'provider' => env('OTPIQ_PROVIDER', 'sms'),
    ],
];
