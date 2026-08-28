<?php

namespace App\Enums;

/**
 * The two moments a WhatsApp code is ever sent: to prove a phone once at
 * signup, and to prove it again before a password is replaced. Login never
 * sends one, and the phone itself is never changed from the app.
 */
enum OtpPurpose: string
{
    case REGISTER = 'register';
    case RESET = 'reset';

    public function label(): string
    {
        return match ($this) {
            self::REGISTER => 'تأكيد التسجيل',
            self::RESET => 'استعادة كلمة المرور',
        };
    }

    /** Arqam endpoint that sends this kind of code. */
    public function endpoint(): string
    {
        return match ($this) {
            self::REGISTER => '/sms/otp',
            self::RESET => '/sms/reset-password',
        };
    }
}
