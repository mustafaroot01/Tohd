<?php

namespace App\Exceptions;

class AttemptAlreadyUsedException extends DomainException
{
    public function __construct(string $message = 'هذه المحاولة احتُسبت مسبقاً')
    {
        parent::__construct($message, 'ATTEMPT_ALREADY_USED', 409);
    }
}
