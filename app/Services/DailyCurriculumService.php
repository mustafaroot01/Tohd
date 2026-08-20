<?php

namespace App\Services;

use App\Models\Subscriber;

class DailyCurriculumService
{
    public function __construct(
        protected CurriculumProgressResolver $resolver
    ) {}

    /**
     * Get the full today's curriculum plan for the user.
     */
    public function getTodayPlan(Subscriber $user): array
    {
        $context = $this->resolver->resolve($user);
        $currentDay = $context['current_day'];
        $completedGameIds = $context['completed_game_ids'];

        $games = $currentDay->dayGames->map(function ($dayGame) use ($completedGameIds) {
            $game = $dayGame->game;
            $isCompleted = $completedGameIds->contains($game->id);

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
