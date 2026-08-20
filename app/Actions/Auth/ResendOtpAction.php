<?php

namespace App\Actions\Auth;

use App\Actions\Otp\SendOtpAction;
use App\Enums\OtpPurpose;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Support\PhoneNumber;

class ResendOtpAction
{
    public function __construct(
        protected SendOtpAction $sendOtp
    ) {}

    /**
     * Resolve a subscriber by phone and resend an OTP for the given purpose.
     *
     * @throws DomainException
     */
    public function execute(string $phone, OtpPurpose $purpose): void
    {
        $normalizedPhone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $normalizedPhone)->first();

        if (! $subscriber) {
            throw new DomainException('رقم الهاتف غير مسجل في النظام', 'PHONE_NOT_FOUND', 404);
        }

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $this->sendOtp->execute($subscriber, $normalizedPhone, $purpose);
    }
}
