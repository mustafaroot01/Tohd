<?php

namespace App\Actions\Otp;

use App\Contracts\SmsGatewayInterface;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpResendCooldownException;
use App\Models\Otp;
use App\Models\Subscriber;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class SendOtpAction
{
    public function __construct(
        protected SmsGatewayInterface $smsGateway
    ) {}

    /**
     * Generate and deliver a fresh OTP code for the given subscriber/phone/purpose.
     *
     * @throws OtpResendCooldownException
     * @throws \App\Exceptions\OtpDeliveryFailedException
     */
    public function execute(Subscriber $subscriber, string $phone, OtpPurpose $purpose): void
    {
        $cooldownKey = "otp-cooldown:{$phone}:{$purpose->value}";
        $hourlyKey = "otp-hourly:{$phone}:{$purpose->value}";

        if (RateLimiter::tooManyAttempts($cooldownKey, 1) || RateLimiter::tooManyAttempts($hourlyKey, config('otp.max_resend_per_hour'))) {
            throw new OtpResendCooldownException;
        }

        $code = (string) random_int(
            (int) str_pad('1', config('otp.length'), '0'),
            (int) str_pad('9', config('otp.length'), '9')
        );

        $this->smsGateway->send($phone, $code);

        Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['expires_at' => now()]);

        Otp::create([
            'subscriber_id' => $subscriber->id,
            'phone' => $phone,
            'purpose' => $purpose,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(SystemSetting::get()->otp_expiry_minutes),
        ]);

        RateLimiter::hit($cooldownKey, config('otp.resend_cooldown_seconds'));
        RateLimiter::hit($hourlyKey, 3600);
    }
}
