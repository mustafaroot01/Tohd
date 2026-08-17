<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case STARTED = 'STARTED';
    case COMPLETED = 'COMPLETED';
    case ABANDONED = 'ABANDONED';
    case FAILED = 'FAILED';

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }
}
