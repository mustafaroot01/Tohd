<?php

namespace App\Enums;

enum GameStatus: string
{
    case DRAFT = 'DRAFT';
    case TESTING = 'TESTING';
    case APPROVED = 'APPROVED';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function isPlayable(): bool
    {
        return $this === self::PUBLISHED;
    }
}
