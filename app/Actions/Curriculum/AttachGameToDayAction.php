<?php

namespace App\Actions\Curriculum;

use App\Models\CurriculumDay;
use App\Models\CurriculumDayGame;
use App\Models\Game;

class AttachGameToDayAction
{
    public function execute(CurriculumDay $day, Game $game, array $attributes = []): CurriculumDayGame
    {
        $sortOrder = $attributes['sort_order'] ?? (($day->dayGames()->max('sort_order') ?? 0) + 1);

        return $day->dayGames()->updateOrCreate(
            ['game_id' => $game->id],
            [
                'sort_order' => $sortOrder,
                'is_required' => $attributes['is_required'] ?? true,
                'config_override' => $attributes['config_override'] ?? null,
            ]
        );
    }
}
