<?php

namespace App\Services;

use App\Models\CurriculumDay;
use App\Models\Game;
use App\Models\Subscriber;

/**
 * Which curriculum day an attempt belongs to.
 *
 * The app says which day it is showing; the server only accepts that claim
 * when it is the day the child is actually on and that day contains the game.
 * Anything else is free play (null) — recorded, but not attributed to a day
 * of the plan. Two small queries, no curriculum tree loaded.
 */
class CurriculumDayResolver
{
    public function forGame(Subscriber $subscriber, Game $game, ?string $claimedDayId): ?CurriculumDay
    {
        if ($claimedDayId === null) {
            return null;
        }

        $assignment = $subscriber->activeCurriculumAssignment;
        if ($assignment === null) {
            return null;
        }

        $dayIds = CurriculumDay::query()
            ->whereHas('week.month', fn ($q) => $q->where('curriculum_id', $assignment->curriculum_id))
            ->orderBy('curriculum_week_id')
            ->orderBy('day_number')
            ->pluck('id');

        if ($dayIds->isEmpty()) {
            return null;
        }

        $currentDayId = $dayIds[CurriculumProgressResolver::dayIndex($assignment, $dayIds->count())];
        if ($claimedDayId !== $currentDayId) {
            return null;
        }

        return CurriculumDay::query()
            ->whereKey($currentDayId)
            ->whereHas('dayGames', fn ($q) => $q->where('game_id', $game->id))
            ->first();
    }
}
