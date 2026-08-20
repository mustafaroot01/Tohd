<?php

namespace App\Exceptions;

class InvalidOtpException extends DomainException
{
    public function __construct(string $message = 'رمز التحقق غير صحيح')
    {
        parent::__construct($message, 'OTP_INVALID', 422);
    }
}
