<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Services\OtpService;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Every existing session dies with the old password, and no new one is
 * handed out: whoever reset it proves the new password on the login screen.
 */
class ResetPasswordAction
{
    public function __construct(
        protected OtpService $otp,
        protected SubscriberActivityLogger $activityLogger,
    ) {}

    public function execute(string $phone, string $code, string $newPassword): void
    {
        $phone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $phone)->first()
            ?? throw ValidationException::withMessages(['phone' => 'لا يوجد حساب بهذا الرقم']);

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $this->otp->verify($phone, OtpPurpose::RESET, $code);

        DB::transaction(function () use ($subscriber, $newPassword) {
            $subscriber->update(['password' => Hash::make($newPassword)]);
            $subscriber->tokens()->delete();

            $this->activityLogger->log($subscriber, SubscriberActivityType::PASSWORD_RESET);
        });
    }
}
