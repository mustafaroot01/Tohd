<?php

namespace App\Actions\Sessions;

use App\Enums\GameSessionStatus;
use App\Enums\SubscriberActivityType;
use App\Events\GameSessionStarted;
use App\Exceptions\GameNotPublishedException;
use App\Models\CurriculumDay;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Subscriber;
use App\Services\SubscriberActivityLogger;

class StartGameSessionAction
{
    public function __construct(
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Start a new game session.
     *
     * @throws GameNotPublishedException
     */
    public function execute(Subscriber $user, Game $game, ?CurriculumDay $curriculumDay = null, ?array $metadata = null): GameSession
    {
        if (! $game->isPlayable()) {
            throw new GameNotPublishedException('هذه اللعبة غير متاحة للعب حالياً.');
        }

        $session = GameSession::create([
            'subscriber_id' => $user->id,
            'game_id' => $game->id,
            'curriculum_day_id' => $curriculumDay?->id,
            'started_at' => now(),
            'status' => GameSessionStatus::STARTED,
            'metadata' => $metadata,
        ]);

        $user->update(['last_activity_at' => now()]);

        $this->activityLogger->log($user, SubscriberActivityType::GAME_STARTED, [
            'session_id' => $session->id,
            'game_id' => $game->id,
        ]);

        event(new GameSessionStarted($session));

        return $session->load(['game.axis', 'game.skill', 'curriculumDay']);
    }
}
