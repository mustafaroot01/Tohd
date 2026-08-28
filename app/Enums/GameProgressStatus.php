<?php

namespace App\Enums;

/**
 * Where a child stands on one game of today's plan.
 *
 * Deliberately three states rather than a boolean: a game that was skipped after
 * repeated failures is neither "done" nor "untouched", and flattening it into
 * either one hides exactly the signal a specialist needs.
 */
enum GameProgressStatus: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case PASSED = 'PASSED';
    case SKIPPED = 'SKIPPED';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'لم تبدأ',
            self::IN_PROGRESS => 'قيد المحاولة',
            self::PASSED => 'ناجحة',
            self::SKIPPED => 'تم تخطّيها',
        };
    }

    /** Only a pass counts toward the day's completion. */
    public function countsAsDone(): bool
    {
        return $this === self::PASSED;
    }
}
