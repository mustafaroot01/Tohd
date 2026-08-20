<?php

namespace App\Exceptions;

class OtpDeliveryFailedException extends DomainException
{
    public function __construct(string $message = 'تعذر إرسال رمز التحقق حالياً، يرجى المحاولة لاحقاً')
    {
        parent::__construct($message, 'OTP_DELIVERY_FAILED', 503);
    }
}
