<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case EXPIRED = 'EXPIRED';
    case PAUSED = 'PAUSED';
    case CANCELLED = 'CANCELLED';

    public function isValid(): bool
    {
        return $this === self::ACTIVE;
    }
}
