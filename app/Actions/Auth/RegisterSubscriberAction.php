<?php

namespace App\Actions\Auth;

use App\Actions\Otp\SendOtpAction;
use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Events\SubscriberRegistered;
use App\Models\Subscriber;
use App\Models\SystemSetting;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;

class RegisterSubscriberAction
{
    public function __construct(
        protected SendOtpAction $sendOtp,
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Register a new subscriber. When OTP verification is enabled system-wide
     * (the default), the account starts UNVERIFIED and a phone-verification OTP
     * is sent — no token is issued until verified. When an admin has disabled OTP
     * (e.g. for a staging/demo environment), the account is activated immediately.
     *
     * @return array{user: Subscriber, token: ?string}
     */
    public function execute(array $data): array
    {
        $phone = PhoneNumber::normalize($data['phone']);
        $otpEnabled = SystemSetting::get()->otp_enabled;

        $subscriber = Subscriber::create([
            'name' => $data['name'],
            'phone' => $phone,
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $otpEnabled ? SubscriberStatus::UNVERIFIED : SubscriberStatus::ACTIVE,
            'phone_verified_at' => $otpEnabled ? null : now(),
        ]);

        $this->activityLogger->log($subscriber, SubscriberActivityType::REGISTERED);

        $token = null;

        if ($otpEnabled) {
            $this->sendOtp->execute($subscriber, $phone, OtpPurpose::PHONE_VERIFICATION);
        } else {
            $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_ACTIVATED);
            $token = $subscriber->createToken('mobile_app')->plainTextToken;
        }

        event(new SubscriberRegistered($subscriber));

        return [
            'user' => $subscriber,
            'token' => $token,
        ];
    }
}
