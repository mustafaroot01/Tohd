<?php

namespace App\Exceptions;

class InvalidActivationCodeException extends DomainException
{
    public function __construct(string $message = 'كود التفعيل غير صالح')
    {
        parent::__construct($message, 'INVALID_ACTIVATION_CODE', 404);
    }
}
