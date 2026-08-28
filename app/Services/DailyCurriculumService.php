<?php

namespace App\Services;

use App\Models\Subscriber;

class DailyCurriculumService
{
    public function __construct(
        protected CurriculumProgressResolver $resolver,
        protected ScoringService $scoring,
        protected ProgressBoardService $board
    ) {}

    /**
     * Get the full today's curriculum plan for the user.
     */
    public function getTodayPlan(Subscriber $user): array
    {
        $context = $this->resolver->resolve($user);
        $currentDay = $context['current_day'];

        // One query: the board row for each of today's games. Cost is the size
        // of the day (~5 rows), never the child's play history.
        $gameIds = $currentDay->dayGames->pluck('game_id')->filter()->values();
        $board = $this->board->rowsFor($user, $gameIds);

        // the day is walked in order: a game is locked until everything before
        // it was passed or skipped
        $previousUnblocked = true;

        $games = $currentDay->dayGames->sortBy('sort_order')->map(function ($dayGame) use ($board, &$previousUnblocked) {
            $game = $dayGame->game;
            $progress = $this->scoring->progressFor($game, $board->get($game->id));
            $isCompleted = $this->scoring->countsAsDone($progress);
            $isLocked = config('scoring.require_pass_to_advance') && ! $previousUnblocked;
            $previousUnblocked = $previousUnblocked && $this->scoring->unblocksNext($progress);

            return [
                'id' => $game->id,
                'code' => $game->code,
                'name' => $game->name,
                'type' => $game->type->value,
                'level' => $game->level,
                'difficulty' => $game->difficulty,
                'duration_seconds' => $game->duration_seconds,
                'sort_order' => $dayGame->sort_order,
                'is_required' => (bool) $dayGame->is_required,
                'is_completed' => $isCompleted,
                'status' => $progress['status']->value,
                'status_label' => $progress['status']->label(),
                'score' => $progress['score'],
                'required_score' => $progress['required_score'],
                'attempts' => $progress['attempts'],
                'failed_attempts' => $progress['failed_attempts'],
                // attempts too short to count as a real try
                'short_attempts' => $progress['short_attempts'],
                // after enough failed tries a hard game stops being a dead end
                'can_skip' => $progress['can_skip'],
                // earlier games in the day must be passed or skipped first
                'is_locked' => $isLocked,
                'best_seconds' => $progress['best_seconds'],
                'required_seconds' => $progress['required_seconds'],
                'config_override' => $dayGame->config_override,
                'axis' => [
                    'id' => $game->axis_id,
                    'name' => $game->axis?->name,
                ],
                'skill' => [
                    'id' => $game->skill_id,
                    'name' => $game->skill?->name,
                ],
            ];
        })->values();

        $totalGames = $games->count();
        $completedCount = $games->where('is_completed', true)->count();
        $completionRate = $totalGames > 0 ? round(($completedCount / $totalGames) * 100, 1) : 0;

        return [
            'curriculum' => [
                'id' => $context['curriculum']->id,
                'code' => $context['curriculum']->code,
                'name' => $context['curriculum']->name,
            ],
            'month' => [
                'id' => $currentDay->week->month->id,
                'number' => $currentDay->week->month->month_number,
                'name' => $currentDay->week->month->name,
            ],
            'week' => [
                'id' => $currentDay->week->id,
                'number' => $currentDay->week->week_number,
                'name' => $currentDay->week->name,
            ],
            'day' => [
                'id' => $currentDay->id,
                'number' => $currentDay->day_number,
                'name' => $currentDay->name,
                'description' => $currentDay->description,
                'estimated_duration_seconds' => $currentDay->estimated_duration_seconds,
                'is_completed' => $context['is_day_completed'],
                'completion_rate' => $completionRate,
            ],
            'games' => $games,
        ];
    }
}
