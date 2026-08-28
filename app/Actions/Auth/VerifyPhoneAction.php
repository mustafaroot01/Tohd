<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Events\SubscriberRegistered;
use App\Models\Subscriber;
use App\Services\OtpService;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The code proved the phone, so the account is created — active from birth —
 * and the parent is signed straight in. There is no "verify an existing
 * account" path: an account exists only once its phone has been proven.
 *
 * Two guards stand before the account is made: the caller must hold the
 * signup token handed out by register (so only whoever started this signup can
 * finish it), and the phone must still be free at this exact moment (ten
 * minutes is long enough for an admin to have created it meanwhile).
 */
class VerifyPhoneAction
{
    public function __construct(
        protected OtpService $otp,
        protected SubscriberActivityLogger $activityLogger,
    ) {}

    /**
     * @return array{user: Subscriber, token: string}
     */
    public function execute(string $phone, string $code, ?string $signupToken = null): array
    {
        $phone = PhoneNumber::normalize($phone);

        $signup = Cache::get(RegisterSubscriberAction::signupKey($phone));

        // one message for "no signup" and "not your signup": a stranger must
        // not learn whether a registration for this number is under way
        if (! $signup || ! $this->tokenMatches($signup, $signupToken)) {
            throw ValidationException::withMessages([
                'phone' => 'انتهت جلسة التسجيل، أعد إدخال بياناتك',
            ]);
        }

        $this->otp->verify($phone, OtpPurpose::REGISTER, $code);

        $alreadyRegistered = function () use ($phone) {
            Cache::forget(RegisterSubscriberAction::signupKey($phone));

            throw ValidationException::withMessages([
                'phone' => 'رقم الهاتف مسجّل بالفعل، سجّل الدخول أو استعد كلمة المرور',
            ]);
        };

        $subscriber = DB::transaction(function () use ($signup, $phone, $alreadyRegistered) {
            if (Subscriber::where('phone', $phone)->exists()) {
                $alreadyRegistered();
            }

            try {
                $subscriber = RegisterSubscriberAction::createFromSignup($signup);
            } catch (UniqueConstraintViolationException) {
                // two confirms in flight at once: the unique index is the real
                // gate, and the loser must read the same message, not a 500
                $alreadyRegistered();
            }

            $this->activityLogger->log($subscriber, SubscriberActivityType::REGISTERED);
            $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_ACTIVATED);

            return $subscriber;
        });

        Cache::forget(RegisterSubscriberAction::signupKey($phone));

        event(new SubscriberRegistered($subscriber));

        return [
            'user' => $subscriber,
            'token' => $subscriber->createToken('mobile_app')->plainTextToken,
        ];
    }

    private function tokenMatches(array $signup, ?string $signupToken): bool
    {
        $expected = $signup['token'] ?? null;

        if (! is_string($expected) || ! is_string($signupToken) || $signupToken === '') {
            return false;
        }

        return hash_equals($expected, hash('sha256', $signupToken));
    }
}
