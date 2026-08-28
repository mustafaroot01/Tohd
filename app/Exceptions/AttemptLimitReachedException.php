<?php

namespace App\Exceptions;

class AttemptLimitReachedException extends DomainException
{
    public function __construct(string $message = 'وصلت إلى الحد الأقصى من المحاولات لهذه اللعبة اليوم')
    {
        parent::__construct($message, 'ATTEMPT_LIMIT_REACHED', 429);
    }
}
