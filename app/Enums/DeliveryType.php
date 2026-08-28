<?php

namespace App\Enums;

enum DeliveryType: string
{
    case NATURAL = 'NATURAL';
    case CESAREAN = 'CESAREAN';

    public function label(): string
    {
        return match ($this) {
            self::NATURAL => 'طبيعية',
            self::CESAREAN => 'قيصرية',
        };
    }
}
