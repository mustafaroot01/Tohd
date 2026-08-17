<?php

namespace App\Enums;

enum AssetType: string
{
    case LOTTIE = 'LOTTIE';
    case IMAGE = 'IMAGE';
    case AUDIO = 'AUDIO';
    case VIDEO = 'VIDEO';
    case BACKGROUND = 'BACKGROUND';
    case CHARACTER = 'CHARACTER';
    case OBJECT = 'OBJECT';
    case OTHER = 'OTHER';
}
