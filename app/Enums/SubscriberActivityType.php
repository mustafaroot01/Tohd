<?php

namespace App\Enums;

enum SubscriberActivityType: string
{
    case REGISTERED = 'REGISTERED';
    case ACCOUNT_ACTIVATED = 'ACCOUNT_ACTIVATED';
    case LOGIN = 'LOGIN';
    case SERIAL_ACTIVATED = 'SERIAL_ACTIVATED';
    case SUBSCRIPTION_STARTED = 'SUBSCRIPTION_STARTED';
    case GAME_STARTED = 'GAME_STARTED';
    case GAME_COMPLETED = 'GAME_COMPLETED';
    case SUBSCRIPTION_EXPIRED = 'SUBSCRIPTION_EXPIRED';
    case SUBSCRIPTION_CANCELLED = 'SUBSCRIPTION_CANCELLED';
    case ACCOUNT_SUSPENDED = 'ACCOUNT_SUSPENDED';
    case ACCOUNT_REACTIVATED = 'ACCOUNT_REACTIVATED';
    case PASSWORD_RESET = 'PASSWORD_RESET';
    case PHONE_CHANGED = 'PHONE_CHANGED';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'التسجيل',
            self::ACCOUNT_ACTIVATED => 'تفعيل الحساب',
            self::LOGIN => 'تسجيل الدخول',
            self::SERIAL_ACTIVATED => 'تفعيل السيريال',
            self::SUBSCRIPTION_STARTED => 'بدء الاشتراك',
            self::GAME_STARTED => 'بدء اللعبة',
            self::GAME_COMPLETED => 'إنهاء اللعبة',
            self::SUBSCRIPTION_EXPIRED => 'انتهاء الاشتراك',
            self::SUBSCRIPTION_CANCELLED => 'إلغاء الاشتراك',
            self::ACCOUNT_SUSPENDED => 'إيقاف الحساب',
            self::ACCOUNT_REACTIVATED => 'إعادة تفعيل الحساب',
            self::PASSWORD_RESET => 'إعادة تعيين كلمة المرور',
            self::PHONE_CHANGED => 'تغيير رقم الهاتف',
        };
    }
}
