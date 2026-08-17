<?php

namespace App\Exceptions;

class CurriculumNotPublishedException extends DomainException
{
    public function __construct(string $message = 'المنهج التعليمي غير منشور بعد')
    {
        parent::__construct($message, 'CURRICULUM_NOT_PUBLISHED', 400);
    }
}
