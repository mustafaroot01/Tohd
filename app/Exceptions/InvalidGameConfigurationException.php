<?php

namespace App\Exceptions;

class InvalidGameConfigurationException extends DomainException
{
    public function __construct(string $message = 'إعدادات اللعبة غير متوافقة مع المعايير المعتمدة')
    {
        parent::__construct($message, 'INVALID_GAME_CONFIGURATION', 422);
    }
}
