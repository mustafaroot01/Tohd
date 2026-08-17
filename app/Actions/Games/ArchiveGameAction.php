<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;

class ArchiveGameAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function execute(Game $game, ?User $user = null): Game
    {
        $oldValues = $game->toArray();
        $game->update([
            'status' => GameStatus::ARCHIVED,
            'updated_by' => $user?->id,
        ]);

        $this->auditLog->log('GAME_ARCHIVED', 'Game', $game->id, $oldValues, $game->fresh()->toArray(), $user);

        return $game;
    }
}
