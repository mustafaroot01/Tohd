<?php

namespace App\Exceptions;

class GameNotPublishedException extends DomainException
{
    public function __construct(string $message = 'اللعبة غير منشورة أو غير متاحة في المنهج الحالي')
    {
        parent::__construct($message, 'GAME_NOT_PUBLISHED', 403);
    }
}
