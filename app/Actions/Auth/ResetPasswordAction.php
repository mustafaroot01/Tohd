<?php

namespace App\Actions\Auth;

use App\Actions\Otp\VerifyOtpAction;
use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;

class ResetPasswordAction
{
    public function __construct(
        protected VerifyOtpAction $verifyOtp,
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Verify a password-reset OTP and update the subscriber's password.
     *
     * @throws DomainException
     */
    public function execute(string $phone, string $code, string $newPassword): void
    {
        $normalizedPhone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $normalizedPhone)->first();

        if (! $subscriber) {
            throw new DomainException('رقم الهاتف غير مسجل في النظام', 'PHONE_NOT_FOUND', 404);
        }

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $this->verifyOtp->execute($subscriber, $normalizedPhone, $code, OtpPurpose::PASSWORD_RESET);

        $subscriber->update(['password' => Hash::make($newPassword)]);

        $this->activityLogger->log($subscriber, SubscriberActivityType::PASSWORD_RESET);
    }
}
