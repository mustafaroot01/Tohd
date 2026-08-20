<?php

namespace App\Actions\Auth;

use App\Enums\SubscriberActivityType;
use App\Events\SubscriberLoggedIn;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Services\SubscriberActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;

class LoginSubscriberAction
{
    public function __construct(
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Authenticate a subscriber and issue a Sanctum token.
     *
     * @return array{user: Subscriber, token: string}
     *
     * @throws DomainException
     */
    public function execute(string $phone, string $password, ?string $deviceName = 'mobile_app'): array
    {
        $normalizedPhone = PhoneNumber::normalize($phone);

        $subscriber = Subscriber::where('phone', $normalizedPhone)->first();

        if (! $subscriber || ! Hash::check($password, $subscriber->password)) {
            throw new DomainException('بيانات الدخول غير صحيحة، يرجى التأكد من رقم الهاتف وكلمة المرور', 'INVALID_CREDENTIALS', 401);
        }

        if ($subscriber->isSuspended()) {
            throw new DomainException('تم إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        if ($subscriber->isUnverified()) {
            throw new DomainException('لم يتم التحقق من رقم الهاتف بعد، يرجى إكمال عملية التحقق', 'ACCOUNT_UNVERIFIED', 403);
        }

        $subscriber->update(['last_login_at' => now(), 'last_activity_at' => now()]);

        $token = $subscriber->createToken($deviceName ?: 'mobile_app')->plainTextToken;

        $this->activityLogger->log($subscriber, SubscriberActivityType::LOGIN);

        event(new SubscriberLoggedIn($subscriber));

        return [
            'user' => $subscriber,
            'token' => $token,
        ];
    }
}
