<?php

namespace App\Services;

use App\Enums\GameSessionStatus;
use App\Exceptions\NoActiveCurriculumException;
use App\Models\CurriculumDay;
use App\Models\GameSession;
use App\Models\Subscriber;
use App\Models\UserCurriculumAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CurriculumProgressResolver
{
    /**
     * Resolve the current training context and active day for a user.
     *
     * @return array{
     *     assignment: UserCurriculumAssignment,
     *     curriculum: \App\Models\Curriculum,
     *     current_day: CurriculumDay,
     *     day_number: int,
     *     days_elapsed: int,
     *     completed_game_ids: Collection<int, string>,
     *     is_day_completed: bool
     * }
     *
     * @throws NoActiveCurriculumException
     */
    public function resolve(Subscriber $user, ?Carbon $asOfDate = null): array
    {
        $assignment = $user->activeCurriculumAssignment;

        if (! $assignment) {
            throw new NoActiveCurriculumException('المستخدم ليس لديه اشتراك تدريبي نشط.');
        }

        $curriculum = $assignment->curriculum;
        $now = $asOfDate ?: now();
        $startsAt = $assignment->starts_at;

        // Calculate days elapsed (1-indexed)
        $daysElapsed = (int) max(1, $startsAt->startOfDay()->diffInDays($now->startOfDay()) + 1);

        // Fetch all days in sequence
        $allDays = CurriculumDay::whereHas('week.month', function ($q) use ($curriculum) {
            $q->where('curriculum_id', $curriculum->id);
        })
            ->with(['week.month', 'dayGames.game.assets'])
            ->orderBy('curriculum_week_id')
            ->orderBy('day_number')
            ->get();

        if ($allDays->isEmpty()) {
            throw new NoActiveCurriculumException('المنهج التدريبي لا يحتوي على أيام مبرمجة.');
        }

        // Loop through all days or modulo if duration exceeds available days
        $dayIndex = ($daysElapsed - 1) % $allDays->count();
        $currentDay = $allDays[$dayIndex];

        // Fetch completed games for this day
        $completedGameIds = GameSession::where('subscriber_id', $user->id)
            ->where('curriculum_day_id', $currentDay->id)
            ->where('status', GameSessionStatus::COMPLETED)
            ->pluck('game_id')
            ->unique();

        $requiredGameIds = $currentDay->dayGames
            ->where('is_required', true)
            ->pluck('game_id');

        $isDayCompleted = $requiredGameIds->isNotEmpty()
            && $requiredGameIds->every(fn ($gid) => $completedGameIds->contains($gid));

        return [
            'assignment' => $assignment,
            'curriculum' => $curriculum,
            'current_day' => $currentDay,
            'day_number' => $currentDay->day_number,
            'days_elapsed' => $daysElapsed,
            'completed_game_ids' => $completedGameIds,
            'is_day_completed' => $isDayCompleted,
        ];
    }
}
