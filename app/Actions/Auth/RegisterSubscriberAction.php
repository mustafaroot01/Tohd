<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Events\SubscriberRegistered;
use App\Models\Subscriber;
use App\Models\SystemSetting;
use App\Services\OtpService;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Nothing is created here. The signup is held for ten minutes and the code is
 * sent; the account is born only when the code proves the phone
 * (VerifyPhoneAction), so a half-made row never lingers and there is no
 * "awaiting verification" state to exist at all. The password is hashed
 * before it is cached, so a plain one is never stored anywhere.
 *
 * Two rules keep a stranger from registering *your* number and walking away
 * with the account when you enter the code that reaches your phone:
 *
 *   1. the code is sent BEFORE anything is written, so a refused send (the
 *      one-minute cooldown, an unreachable provider) never leaves a signup
 *      behind;
 *   2. register hands back a one-time signup token, and only the client
 *      holding it can complete the account — whoever else overwrote the
 *      pending signup cannot finish it.
 *
 * When an admin has switched OTP off (a demo environment), the account is
 * created and activated on the spot instead.
 */
class RegisterSubscriberAction
{
    public const SIGNUP_TTL_SECONDS = 600;

    public function __construct(
        protected OtpService $otp,
        protected SubscriberActivityLogger $activityLogger,
    ) {}

    /**
     * @return array{phone: string, signup_token: string, resend_in: int}|array{user: Subscriber, token: string}
     */
    public function execute(array $data): array
    {
        $phone = PhoneNumber::normalize($data['phone']);

        if (Subscriber::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الهاتف مسجّل بالفعل، سجّل الدخول أو استعد كلمة المرور',
            ]);
        }

        $signup = [
            'name' => $data['name'],
            'phone' => $phone,
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
        ];

        if (! SystemSetting::get()->otp_enabled) {
            return $this->createActivated($signup);
        }

        // send first: a request that ends in an error must not have touched a
        // pending signup on its way out
        $this->otp->send($phone, OtpPurpose::REGISTER);

        $signupToken = Str::random(48);
        // only the hash is stored, so a leaked cache cannot complete a signup
        $signup['token'] = hash('sha256', $signupToken);

        Cache::put(self::signupKey($phone), $signup, self::SIGNUP_TTL_SECONDS);

        return [
            'phone' => $phone,
            'signup_token' => $signupToken,
            'resend_in' => $this->otp->secondsUntilResend($phone, OtpPurpose::REGISTER),
        ];
    }

    /** @return array{user: Subscriber, token: string} */
    private function createActivated(array $signup): array
    {
        $subscriber = self::createFromSignup($signup);

        $this->activityLogger->log($subscriber, SubscriberActivityType::REGISTERED);
        $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_ACTIVATED);

        event(new SubscriberRegistered($subscriber));

        return [
            'user' => $subscriber,
            'token' => $subscriber->createToken('mobile_app')->plainTextToken,
        ];
    }

    /** The account, active from birth. */
    public static function createFromSignup(array $signup): Subscriber
    {
        $subscriber = new Subscriber;

        // forceFill: the password is already hashed
        $subscriber->forceFill([
            'name' => $signup['name'],
            'phone' => $signup['phone'],
            'address' => $signup['address'] ?? null,
            'password' => $signup['password'],
            'status' => SubscriberStatus::ACTIVE,
            'phone_verified_at' => now(),
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        return $subscriber;
    }

    public static function signupKey(string $phone): string
    {
        return 'signup:'.$phone;
    }
}
