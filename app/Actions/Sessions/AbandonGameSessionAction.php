<?php

namespace App\Actions\Sessions;

use App\Enums\GameSessionStatus;
use App\Exceptions\UnauthorizedGameSessionException;
use App\Models\GameSession;
use App\Models\Subscriber;

class AbandonGameSessionAction
{
    /**
     * Abandon an active game session.
     *
     * @throws UnauthorizedGameSessionException
     */
    public function execute(GameSession $session, Subscriber $user): GameSession
    {
        if ($session->subscriber_id !== $user->id) {
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
