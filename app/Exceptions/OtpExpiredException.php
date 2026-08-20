<?php

namespace App\Exceptions;

class OtpExpiredException extends DomainException
{
    public function __construct(string $message = 'رمز التحقق منتهي الصلاحية أو غير موجود، يرجى طلب رمز جديد')
    {
        parent::__construct($message, 'OTP_EXPIRED', 422);
    }
}
