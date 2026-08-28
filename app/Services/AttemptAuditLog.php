<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Subscriber;
use App\Support\AttemptResult;
use App\Support\AttemptToken;
use Illuminate\Support\Facades\Log;

/**
 * One JSON line per graded attempt and per skip, to the 'attempts' log
 * channel. This is the only per-attempt record the system keeps: the board and
 * the daily table hold the numbers, this holds the sequence — for a dispute,
 * or for a specialist who wants to see exactly what a hard afternoon looked
 * like. Written after commit; a failure here must never fail the request.
 */
class AttemptAuditLog
{
    public function completed(Subscriber $child, Game $game, AttemptToken $token, AttemptResult $result): void
    {
        $this->write('attempt.completed', [
            'subscriber_id' => $child->id,
            'game_id' => $game->id,
            'game_code' => $game->code,
            'curriculum_day_id' => $token->curriculumDayId,
            'nonce' => $token->nonce,
            'started_us' => $token->startedUs,
            'started_at' => $result->startedAt->toIso8601String(),
            'completed_at' => $result->completedAt->toIso8601String(),
            'claimed_seconds' => $result->claimedSeconds,
            'elapsed_seconds' => $result->elapsedSeconds,
            'effective_seconds' => $result->effectiveSeconds,
            'required_seconds' => $result->requiredSeconds,
            'score' => $result->score,
            'required_score' => $result->requiredScore,
            'passed' => $result->passed,
            'counted' => $result->counted,
        ]);
    }

    public function skipped(Subscriber $child, Game $game, ?string $curriculumDayId, array $progress): void
    {
        $this->write('attempt.skipped', [
            'subscriber_id' => $child->id,
            'game_id' => $game->id,
            'game_code' => $game->code,
            'curriculum_day_id' => $curriculumDayId,
            'failed_attempts' => $progress['failed_attempts'] ?? null,
            'best_score' => $progress['score'] ?? null,
            'required_score' => $progress['required_score'] ?? null,
            'at' => now()->toIso8601String(),
        ]);
    }

    private function write(string $event, array $context): void
    {
        try {
            Log::channel((string) config('scoring.audit_channel', 'attempts'))->info($event, $context);
        } catch (\Throwable $e) {
            Log::warning('attempts log unavailable', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
