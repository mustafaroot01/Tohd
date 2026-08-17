<?php

namespace App\Exceptions;

class InvalidLottieFileException extends DomainException
{
    public function __construct(string $message = 'ملف Lottie JSON غير صالح أو يحتوي على بنية تالفة')
    {
        parent::__construct($message, 'INVALID_LOTTIE_FILE', 422);
    }
}
