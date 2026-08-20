<?php

namespace App\Actions\Auth;

use App\Actions\Otp\VerifyOtpAction;
use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;

class VerifyPhoneAction
{
    public function __construct(
        protected VerifyOtpAction $verifyOtp,
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Verify a subscriber's phone via OTP, activate the account, and issue a Sanctum token.
     *
     * @return array{user: Subscriber, token: string}
     *
     * @throws DomainException
     */
    public function execute(string $phone, string $code): array
    {
        $normalizedPhone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $normalizedPhone)->first();

        if (! $subscriber) {
            throw new DomainException('رقم الهاتف غير مسجل في النظام', 'PHONE_NOT_FOUND', 404);
        }

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $this->verifyOtp->execute($subscriber, $normalizedPhone, $code, OtpPurpose::PHONE_VERIFICATION);

        $subscriber->update([
            'phone_verified_at' => now(),
            'status' => SubscriberStatus::ACTIVE,
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_ACTIVATED);

        $token = $subscriber->createToken('mobile_app')->plainTextToken;

        return [
            'user' => $subscriber,
            'token' => $token,
        ];
    }
}
