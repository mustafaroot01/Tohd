<?php

namespace App\Enums;

enum AssetRole: string
{
    case ANIMATION = 'ANIMATION';
    case BACKGROUND = 'BACKGROUND';
    case IMAGE = 'IMAGE';
    /** Plays when the game opens. */
    case START_AUDIO = 'START_AUDIO';

    /** Plays when the game finishes. */
    case END_AUDIO = 'END_AUDIO';

    case INSTRUCTION_AUDIO = 'INSTRUCTION_AUDIO';
    case SUCCESS_AUDIO = 'SUCCESS_AUDIO';
    case ERROR_AUDIO = 'ERROR_AUDIO';
    case DEMONSTRATION_VIDEO = 'DEMONSTRATION_VIDEO';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::ANIMATION => 'رسوم متحركة',
            self::BACKGROUND => 'خلفية',
            self::IMAGE => 'صورة',
            self::START_AUDIO => 'صوت بدء اللعبة',
            self::END_AUDIO => 'صوت نهاية اللعبة',
            self::INSTRUCTION_AUDIO => 'صوت التعليمات',
            self::SUCCESS_AUDIO => 'صوت النجاح',
            self::ERROR_AUDIO => 'صوت الخطأ',
            self::DEMONSTRATION_VIDEO => 'فيديو توضيحي',
            self::OTHER => 'أخرى',
        };
    }
}
