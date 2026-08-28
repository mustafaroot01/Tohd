<?php

namespace App\Exceptions;

class InvalidAttemptTokenException extends DomainException
{
    public function __construct(string $message = 'رمز المحاولة غير صالح')
    {
        parent::__construct($message, 'INVALID_ATTEMPT_TOKEN', 422);
    }
}
