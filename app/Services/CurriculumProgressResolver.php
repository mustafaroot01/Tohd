<?php

namespace App\Services;

use App\Exceptions\NoActiveCurriculumException;
use App\Models\CurriculumDay;
use App\Models\Subscriber;
use App\Models\UserCurriculumAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CurriculumProgressResolver
{
    public function __construct(
        protected ScoringService $scoring,
        protected ProgressBoardService $board,
    ) {}

    /**
     * Which day of the plan a child is on: days elapsed since the assignment
     * started, wrapping around when the plan is shorter than the subscription.
     */
    public static function dayIndex(UserCurriculumAssignment $assignment, int $dayCount, ?Carbon $now = null): int
    {
        $now ??= now();
        $daysElapsed = (int) max(1, $assignment->starts_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) + 1);

        return ($daysElapsed - 1) % max(1, $dayCount);
    }

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

        $daysElapsed = (int) max(1, $startsAt->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) + 1);

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

        $currentDay = $allDays[self::dayIndex($assignment, $allDays->count(), $now)];

        // The games that count as done today, judged exactly as the plan
        // judges them (ScoringService): with the pass gate on only a game
        // passed TODAY counts — an old pass, or a completed-but-failed try, does
        // not, otherwise a child could close the day without reaching the bar.
        $gameIds = $currentDay->dayGames->pluck('game_id')->filter()->values();
        $rows = $this->board->rowsFor($user, $gameIds);
        $completedGameIds = $currentDay->dayGames
            ->filter(fn ($dg) => $dg->game && $this->scoring->countsAsDone($this->scoring->progressFor($dg->game, $rows->get($dg->game_id))))
            ->pluck('game_id')
            ->unique()
            ->values();

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
