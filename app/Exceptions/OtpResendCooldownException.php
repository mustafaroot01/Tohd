<?php

namespace App\Exceptions;

class OtpResendCooldownException extends DomainException
{
    public function __construct(string $message = 'يرجى الانتظار قبل طلب إعادة إرسال رمز التحقق مرة أخرى')
    {
        parent::__construct($message, 'OTP_RESEND_COOLDOWN', 429);
    }
}
