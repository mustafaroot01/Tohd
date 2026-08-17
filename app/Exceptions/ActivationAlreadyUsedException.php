<?php

namespace App\Exceptions;

class ActivationAlreadyUsedException extends DomainException
{
    public function __construct(string $message = 'تم استخدام كود التفعيل مسبقاً')
    {
        parent::__construct($message, 'ACTIVATION_ALREADY_USED', 409);
    }
}
