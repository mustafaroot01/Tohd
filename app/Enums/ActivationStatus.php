<?php

namespace App\Enums;

enum ActivationStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case ACTIVATED = 'ACTIVATED';
    case EXPIRED = 'EXPIRED';
    case REVOKED = 'REVOKED';

    public function isRedeemable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
