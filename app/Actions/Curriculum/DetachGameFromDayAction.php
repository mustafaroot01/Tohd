<?php

namespace App\Actions\Curriculum;

use App\Models\CurriculumDay;
use App\Models\Game;

class DetachGameFromDayAction
{
    public function execute(CurriculumDay $day, Game $game): bool
    {
        return (bool) $day->dayGames()->where('game_id', $game->id)->delete();
    }
}
