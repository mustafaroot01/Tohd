<?php

namespace App\Exceptions;

class UnauthorizedGameSessionException extends DomainException
{
    public function __construct(string $message = 'غير مصرح بالوصول إلى جلسة اللعب هذه')
    {
        parent::__construct($message, 'UNAUTHORIZED_GAME_SESSION', 403);
    }
}
