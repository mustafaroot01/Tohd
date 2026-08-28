<?php

namespace App\Exceptions;

class GameSkipNotAllowedException extends DomainException
{
    public function __construct(string $message = 'لا يمكن تخطّي هذه اللعبة بعد')
    {
        parent::__construct($message, 'GAME_SKIP_NOT_ALLOWED', 422);
    }
}
