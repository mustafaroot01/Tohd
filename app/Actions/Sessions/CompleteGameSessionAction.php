<?php

namespace App\Actions\Sessions;

use App\Enums\GameSessionStatus;
use App\Events\GameSessionCompleted;
use App\Exceptions\SessionAlreadyCompletedException;
use App\Exceptions\UnauthorizedGameSessionException;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteGameSessionAction
{
    /**
     * Complete a game session with client telemetry and calculate results.
     *
     * @throws UnauthorizedGameSessionException
     * @throws SessionAlreadyCompletedException
     */
    public function execute(GameSession $session, array $telemetry, User $user): GameSession
    {
        if ($session->user_id !== $user->id) {
            throw new UnauthorizedGameSessionException('غير مصرح لك بإنهاء جلسة تخص مستخدماً آخر.');
        }

        if ($session->status !== GameSessionStatus::STARTED) {
            throw new SessionAlreadyCompletedException('تم إنهاء هذه الجلسة مسبقاً.');
        }

        return DB::transaction(function () use ($session, $telemetry) {
            $attempts = max(1, (int) ($telemetry['attempts'] ?? 1));
            $correct = max(0, min($attempts, (int) ($telemetry['correct_attempts'] ?? 0)));
            $incorrect = max(0, (int) ($telemetry['incorrect_attempts'] ?? ($attempts - $correct)));
            $duration = max(1, (int) ($telemetry['duration_seconds'] ?? 0));
            $score = (int) ($telemetry['score'] ?? 0);
            $accuracy = round(($correct / $attempts) * 100, 2);

            $metadata = array_merge($session->metadata ?? [], $telemetry['metadata'] ?? []);

            $session->update([
                'completed_at' => now(),
                'duration_seconds' => $duration,
                'attempts' => $attempts,
                'correct_attempts' => $correct,
                'incorrect_attempts' => $incorrect,
                'score' => $score,
                'accuracy' => $accuracy,
                'status' => GameSessionStatus::COMPLETED,
                'metadata' => $metadata,
            ]);

            event(new GameSessionCompleted($session));

            return $session->fresh(['game.axis', 'game.skill', 'curriculumDay']);
        });
    }
}
