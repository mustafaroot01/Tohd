<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case EXPIRED = 'EXPIRED';
    case PAUSED = 'PAUSED';

    public function isValid(): bool
    {
        return $this === self::ACTIVE;
    }
}
