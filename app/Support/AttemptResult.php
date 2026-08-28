<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * One graded attempt — what the server decided, in one place, so the board,
 * the daily row, the audit line and the API response all say the same thing.
 */
final class AttemptResult
{
    /** @param  array<string, mixed>  $progress  the game's standing after this attempt (ScoringService::progressFor) */
    public function __construct(
        public readonly string $gameId,
        public readonly ?string $curriculumDayId,
        public readonly Carbon $startedAt,
        public readonly Carbon $completedAt,
        public readonly ?int $claimedSeconds,
        public readonly int $elapsedSeconds,
        public readonly int $effectiveSeconds,
        public readonly int $requiredSeconds,
        public readonly int $score,
        public readonly int $requiredScore,
        public readonly bool $passed,
        public readonly bool $counted,
        public readonly bool $replayed = false,
        public array $progress = [],
    ) {}

    public function toArray(): array
    {
        $progress = $this->progress;
        if (isset($progress['status']) && $progress['status'] instanceof \App\Enums\GameProgressStatus) {
            $progress['status_label'] = $progress['status']->label();
            $progress['status'] = $progress['status']->value;
        }

        return [
            'attempt' => [
                'game_id' => $this->gameId,
                'curriculum_day_id' => $this->curriculumDayId,
                'started_at' => $this->startedAt->toISOString(),
                'completed_at' => $this->completedAt->toISOString(),
                'claimed_seconds' => $this->claimedSeconds,
                'elapsed_seconds' => $this->elapsedSeconds,
                'effective_seconds' => $this->effectiveSeconds,
                'required_seconds' => $this->requiredSeconds,
                'score' => $this->score,
                'required_score' => $this->requiredScore,
                'is_passed' => $this->passed,
                // false when the attempt was too short to count as a real try
                'is_counted' => $this->counted,
                // true when the app resent an attempt the server had already graded
                'is_replayed' => $this->replayed,
            ],
            'game' => $progress,
        ];
    }
}
