<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
