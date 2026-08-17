<?php

namespace App\Enums;

enum CurriculumStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function isPlayable(): bool
    {
        return $this === self::PUBLISHED;
    }
}
