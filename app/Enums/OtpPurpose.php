<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case PHONE_VERIFICATION = 'PHONE_VERIFICATION';
    case PASSWORD_RESET = 'PASSWORD_RESET';
}
