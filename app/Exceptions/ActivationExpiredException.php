<?php

namespace App\Exceptions;

class ActivationExpiredException extends DomainException
{
    public function __construct(string $message = 'انتهت صلاحية كود التفعيل')
    {
        parent::__construct($message, 'ACTIVATION_EXPIRED', 410);
    }
}
