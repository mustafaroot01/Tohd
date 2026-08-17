<?php

namespace App\Exceptions;

class ProductInactiveException extends DomainException
{
    public function __construct(string $message = 'المنتج المطلوب غير متاح حالياً')
    {
        parent::__construct($message, 'PRODUCT_INACTIVE', 400);
    }
}
