<?php

namespace App\Enums;

enum AssetRole: string
{
    case ANIMATION = 'ANIMATION';
    case BACKGROUND = 'BACKGROUND';
    case IMAGE = 'IMAGE';
    case INSTRUCTION_AUDIO = 'INSTRUCTION_AUDIO';
    case SUCCESS_AUDIO = 'SUCCESS_AUDIO';
    case ERROR_AUDIO = 'ERROR_AUDIO';
    case DEMONSTRATION_VIDEO = 'DEMONSTRATION_VIDEO';
    case OTHER = 'OTHER';
}
