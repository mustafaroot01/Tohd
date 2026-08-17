<?php

namespace App\Exceptions;

class NoActiveCurriculumException extends DomainException
{
    public function __construct(string $message = 'لا يوجد منهج تدريبي مفعل أو ساري الصلاحية لهذا الحساب')
    {
        parent::__construct($message, 'NO_ACTIVE_CURRICULUM', 403);
    }
}
