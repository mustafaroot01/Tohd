<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * One button for both code screens. A signup still in progress lives in the
 * cache, not as an account; anything else is a password-recovery resend for
 * an existing account.
 */
class ResendOtpAction
{
    public function __construct(protected OtpService $otp) {}

    /**
     * @return array{phone: string, purpose: string, resend_in: int}
     */
    public function execute(string $phone): array
    {
        $phone = PhoneNumber::normalize($phone);

        if ($signup = Cache::get(RegisterSubscriberAction::signupKey($phone))) {
            $this->otp->send($phone, OtpPurpose::REGISTER);

            // the new code lives three minutes; the signup it completes must not
            // expire before it. Extended only after a send actually happened.
            Cache::put(
                RegisterSubscriberAction::signupKey($phone),
                $signup,
                RegisterSubscriberAction::SIGNUP_TTL_SECONDS
            );

            return [
                'phone' => $phone,
                'purpose' => OtpPurpose::REGISTER->value,
                'resend_in' => $this->otp->secondsUntilResend($phone, OtpPurpose::REGISTER),
            ];
        }

        return ['purpose' => OtpPurpose::RESET->value, ...$this->sendReset($phone)];
    }

    /**
     * Every code costs a WhatsApp message. An account that cannot log in once
     * it holds the code has no use for one, so it is refused before the
     * provider is called and told why instead.
     *
     * @return array{phone: string, resend_in: int}
     */
    public function sendReset(string $phone): array
    {
        $phone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $phone)->first()
            ?? throw ValidationException::withMessages(['phone' => 'لا يوجد حساب بهذا الرقم']);

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $this->otp->send($phone, OtpPurpose::RESET);

        return [
            'phone' => $phone,
            'resend_in' => $this->otp->secondsUntilResend($phone, OtpPurpose::RESET),
        ];
    }
}
