<?php

namespace App\Exceptions;

class AttemptExpiredException extends DomainException
{
    public function __construct(string $message = 'انتهت صلاحية هذه المحاولة، ابدأ اللعبة من جديد')
    {
        parent::__construct($message, 'ATTEMPT_EXPIRED', 410);
    }
}
