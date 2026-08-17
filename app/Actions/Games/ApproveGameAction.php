<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;

class ApproveGameAction
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
            'status' => GameStatus::APPROVED,
            'updated_by' => $user?->id,
        ]);

        $this->auditLog->log('GAME_APPROVED', 'Game', $game->id, $oldValues, $game->fresh()->toArray(), $user);

        return $game;
    }
}
