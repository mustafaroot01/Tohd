<?php

namespace App\Actions\Sessions;

use App\Enums\GameSessionStatus;
use App\Events\GameSessionStarted;
use App\Exceptions\GameNotPublishedException;
use App\Models\CurriculumDay;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;

class StartGameSessionAction
{
    /**
     * Start a new game session.
     *
     * @throws GameNotPublishedException
     */
    public function execute(User $user, Game $game, ?CurriculumDay $curriculumDay = null, ?array $metadata = null): GameSession
    {
        if (! $game->isPlayable()) {
            throw new GameNotPublishedException('هذه اللعبة غير متاحة للعب حالياً.');
        }

        $session = GameSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'curriculum_day_id' => $curriculumDay?->id,
            'started_at' => now(),
            'status' => GameSessionStatus::STARTED,
            'metadata' => $metadata,
        ]);

        event(new GameSessionStarted($session));

        return $session->load(['game.axis', 'game.skill', 'curriculumDay']);
    }
}
