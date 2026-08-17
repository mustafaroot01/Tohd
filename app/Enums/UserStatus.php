<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case INACTIVE = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'موقوف',
            self::INACTIVE => 'غير نشط',
        };
    }
}
