<?php

namespace App\Exceptions;

class OtpAttemptsExceededException extends DomainException
{
    public function __construct(string $message = 'تجاوزت عدد المحاولات المسموح بها، يرجى طلب رمز تحقق جديد')
    {
        parent::__construct($message, 'OTP_ATTEMPTS_EXCEEDED', 429);
    }
}
