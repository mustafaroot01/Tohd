<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Events\GamePublished;
use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;

class PublishGameAction
{
    public function __construct(
        protected ValidateGameAction $validator,
        protected AuditLogService $auditLog
    ) {}

    public function execute(Game $game, ?User $user = null): Game
    {
        $this->validator->execute($game);

        $oldValues = $game->toArray();
        $game->update([
            'status' => GameStatus::PUBLISHED,
            'published_at' => now(),
            'updated_by' => $user?->id,
        ]);

        $this->auditLog->log('GAME_PUBLISHED', 'Game', $game->id, $oldValues, $game->fresh()->toArray(), $user);

        event(new GamePublished($game));

        return $game;
    }
}
