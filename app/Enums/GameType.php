<?php

namespace App\Enums;

enum GameType: string
{
    case TAP = 'TAP';
    case CHOOSE = 'CHOOSE';
    case MATCH = 'MATCH';
    case TRACKING = 'TRACKING';
    case MEMORY = 'MEMORY';
    case EMOTION = 'EMOTION';
    case SEQUENCE = 'SEQUENCE';
    case DRAG_DROP = 'DRAG_DROP';
    case ORDER = 'ORDER';

    public function label(): string
    {
        return match ($this) {
            self::TAP => 'نقر مباشر',
            self::CHOOSE => 'اختيار من متعدد',
            self::MATCH => 'مطابقة العناصر',
            self::TRACKING => 'تتبع بصري',
            self::MEMORY => 'ذاكرة واسترجاع',
            self::EMOTION => 'تمييز المشاعر',
            self::SEQUENCE => 'تسلسل منطقي',
            self::DRAG_DROP => 'سحب وإفلات',
            self::ORDER => 'ترتيب العناصر',
        };
    }
}
