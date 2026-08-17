<?php

namespace App\Actions\Sessions;

use App\Enums\GameSessionStatus;
use App\Exceptions\UnauthorizedGameSessionException;
use App\Models\GameSession;
use App\Models\User;

class AbandonGameSessionAction
{
    /**
     * Abandon an active game session.
     *
     * @throws UnauthorizedGameSessionException
     */
    public function execute(GameSession $session, User $user): GameSession
    {
        if ($session->user_id !== $user->id) {
            throw new UnauthorizedGameSessionException('غير مصرح لك بتعديل جلسة مستخدم آخر.');
        }

        if ($session->status === GameSessionStatus::STARTED) {
            $session->update([
                'status' => GameSessionStatus::ABANDONED,
                'completed_at' => now(),
            ]);
        }

        return $session;
    }
}
