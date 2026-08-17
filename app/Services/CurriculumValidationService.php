<?php

namespace App\Services;

use App\Exceptions\CurriculumNotPublishedException;
use App\Models\Curriculum;

class CurriculumValidationService
{
    /**
     * Validate curriculum structure before publishing.
     *
     * @throws CurriculumNotPublishedException
     */
    public function validate(Curriculum $curriculum): bool
    {
        $curriculum->load(['months.weeks.days.games']);

        if ($curriculum->months->isEmpty()) {
            throw new CurriculumNotPublishedException('لا يمكن نشر المنهج: يجب أن يحتوي المنهج على شهر تدريبي واحد على الأقل.');
        }

        foreach ($curriculum->months as $month) {
            if ($month->weeks->isEmpty()) {
                throw new CurriculumNotPublishedException("الشهر رقم ({$month->month_number}) لا يحتوي على أي أسابيع تدريبية.");
            }

            foreach ($month->weeks as $week) {
                if ($week->days->isEmpty()) {
                    throw new CurriculumNotPublishedException("الأسبوع رقم ({$week->week_number}) في الشهر ({$month->month_number}) لا يحتوي على أي أيام تدريبية.");
                }

                foreach ($week->days as $day) {
                    if ($day->games->isEmpty()) {
                        throw new CurriculumNotPublishedException("اليوم التدريبي رقم ({$day->day_number}) في الأسبوع ({$week->week_number}) لا يحتوي على أي ألعاب تدريبية.");
                    }

                    foreach ($day->games as $game) {
                        if (! $game->isPlayable()) {
                            throw new CurriculumNotPublishedException("اللعبة ({$game->name} - {$game->code}) المدرجة في اليوم ({$day->day_number}) غير منشورة (Published).");
                        }
                    }
                }
            }
        }

        return true;
    }
}
