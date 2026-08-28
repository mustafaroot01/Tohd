<?php

namespace App\Services;

use App\Enums\GameProgressStatus;
use App\Models\Game;
use App\Models\SubscriberGameProgress;

/**
 * Turns a watched animation into a grade out of ten.
 *
 * The trained skill is sustained attention, so the measure is the time the
 * child stayed with the game against the time the game asks for. Everything
 * here is arithmetic on numbers the server measured itself; the app's own
 * duration is accepted only when it is smaller.
 *
 * Behaviour is driven entirely by config/scoring.php.
 */
class ScoringService
{
    /** How long this game asks the child to stay with it. */
    public function requiredSeconds(?Game $game): int
    {
        return max(1, (int) ($game?->duration_seconds ?? 60));
    }

    public function threshold(?Game $game): float
    {
        return (float) ($game?->config['success_threshold'] ?? config('game.default_success_threshold', 0.8));
    }

    /** The pass mark as a whole grade, from the game's success_threshold. */
    public function requiredScore(?Game $game): int
    {
        return (int) round($this->threshold($game) * $this->maxScore());
    }

    public function maxScore(): int
    {
        return max(1, (int) config('scoring.max_score', 10));
    }

    /**
     * The seconds that actually count.
     *
     * @param  int  $elapsed  server-measured: token issued → result received
     * @param  int|null  $claimed  what the app says the child watched
     * @param  int|null  $floor  seconds since the child's previous graded attempt —
     *                           time before that was already credited once
     */
    public function effectiveSeconds(int $elapsed, ?int $claimed, ?Game $game, ?int $floor = null): int
    {
        $seconds = config('scoring.trust_client_duration')
            ? ($claimed ?? $elapsed)
            : min($claimed ?? $elapsed, $elapsed);

        if ($floor !== null) {
            $seconds = min($seconds, $floor);
        }

        if (config('scoring.cap_at_game_duration')) {
            $seconds = min($seconds, $this->requiredSeconds($game));
        }

        return max(0, (int) $seconds);
    }

    /** Attention held, as whole tenths — floored, so 7.5 tenths reads 7. */
    public function score(int $effectiveSeconds, int $requiredSeconds): int
    {
        if ($requiredSeconds <= 0) {
            return 0;
        }

        $tenths = (int) floor(($effectiveSeconds / $requiredSeconds) * $this->maxScore() + 1e-9);

        return max(0, min($this->maxScore(), $tenths));
    }

    public function passes(int $score, ?Game $game): bool
    {
        return $score >= $this->requiredScore($game);
    }

    /** Below this an attempt is recorded but is not a real try. */
    public function minCountedSeconds(?Game $game): int
    {
        $ratio = (float) config('scoring.min_counted_ratio', 0.25);

        return max(
            (int) config('scoring.min_counted_seconds', 5),
            (int) ceil($ratio * $this->requiredSeconds($game))
        );
    }

    public function counts(int $effectiveSeconds, ?Game $game): bool
    {
        return $effectiveSeconds >= $this->minCountedSeconds($game);
    }

    /** How long a start token stays redeemable. */
    public function tokenLifetimeSeconds(?Game $game): int
    {
        return $this->requiredSeconds($game) + max(0, (int) config('scoring.attempt_grace_seconds', 600));
    }

    /**
     * Where the child stands on a game, read from its board row.
     *
     * @return array{status: GameProgressStatus, score: int|null, required_score: int, attempts: int, failed_attempts: int, short_attempts: int, can_skip: bool, best_seconds: int, required_seconds: int}
     */
    public function progressFor(?Game $game, ?SubscriberGameProgress $row): array
    {
        $strategy = (string) config('scoring.strategy', 'best_of_day');
        $today = $row?->isForToday() ?? false;
        $required = $this->requiredScore($game);

        // "passed" means passed today: the plan is daily practice, and a game
        // the child beat last week still has to be played today. Only the
        // all-time strategy treats an old pass as a pass.
        $passed = $strategy === 'best_all_time'
            ? $row?->passed_at !== null
            : ($today && $row->today_best_score !== null && (int) $row->today_best_score >= $required);

        [$score, $seconds] = match ($strategy) {
            'best_all_time' => [$row?->best_score, (int) ($row?->best_seconds ?? 0)],
            'latest' => [$row?->last_score, (int) ($row?->last_seconds ?? 0)],
            // best_of_day; with nothing played today, show the most recent grade
            // rather than a blank
            default => $today
                ? [$row->today_best_score, (int) $row->today_best_seconds]
                : [$row?->last_score, (int) ($row?->last_seconds ?? 0)],
        };

        if ($strategy === 'best_all_time') {
            $attempts = (int) ($row?->total_attempts ?? 0);
            $failed = max(0, $attempts - (int) ($row?->total_passed ?? 0) - (int) ($row?->total_short ?? 0));
            $short = (int) ($row?->total_short ?? 0);
        } else {
            $attempts = $today ? (int) $row->today_attempts : 0;
            $failed = $today ? (int) $row->today_failed : 0;
            $short = $today ? (int) $row->today_short : 0;
        }

        $skippedToday = $today && $row->skipped_at !== null;
        $unlockAfter = (int) config('scoring.attempts_before_unlock', 3);

        $status = match (true) {
            $passed => GameProgressStatus::PASSED,
            (int) ($row?->total_attempts ?? 0) > 0 => GameProgressStatus::IN_PROGRESS,
            default => GameProgressStatus::NOT_STARTED,
        };

        // a game the child could not pass after enough real tries stops being a wall
        $canSkip = ! $passed && $unlockAfter > 0 && $failed >= $unlockAfter;

        // SKIPPED is only ever a recorded decision. Failures alone leave the
        // game IN_PROGRESS with the skip offer showing — the child may still pass.
        if ($skippedToday && ! $passed) {
            $status = GameProgressStatus::SKIPPED;
            $canSkip = false;
        }

        return [
            'status' => $status,
            'score' => $score !== null ? (int) $score : null,
            'required_score' => $required,
            'attempts' => $attempts,
            'failed_attempts' => $failed,
            'short_attempts' => $short,
            'can_skip' => $canSkip,
            'best_seconds' => $seconds,
            'required_seconds' => $this->requiredSeconds($game),
        ];
    }

    /**
     * Does this game count toward the day being finished?
     *
     * With the gate off, simply having tried it is enough.
     */
    public function countsAsDone(array $progress): bool
    {
        if (! config('scoring.require_pass_to_advance')) {
            return $progress['attempts'] > 0 || $progress['status'] !== GameProgressStatus::NOT_STARTED;
        }

        return $progress['status'] instanceof GameProgressStatus
            && $progress['status']->countsAsDone();
    }

    /**
     * May the child move on to the next game? A pass or a recorded skip both
     * unblock the day; only a pass counts toward completing it.
     */
    public function unblocksNext(array $progress): bool
    {
        if ($this->countsAsDone($progress)) {
            return true;
        }

        return $progress['status'] === GameProgressStatus::SKIPPED;
    }
}
