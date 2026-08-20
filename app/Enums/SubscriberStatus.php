<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    case ACTIVE = 'ACTIVE';
    case UNVERIFIED = 'UNVERIFIED';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'فعال',
            self::UNVERIFIED => 'غير مفعل',
            self::SUSPENDED => 'موقوف',
        };
    }
}
