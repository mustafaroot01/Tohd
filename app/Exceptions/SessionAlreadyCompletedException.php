<?php

namespace App\Exceptions;

class SessionAlreadyCompletedException extends DomainException
{
    public function __construct(string $message = 'تم إكمال أو إنهاء جلسة اللعب مسبقاً')
    {
        parent::__construct($message, 'SESSION_ALREADY_COMPLETED', 409);
    }
}
