<?php

namespace App\Actions\Attempts;

use App\Exceptions\AttemptAlreadyUsedException;
use App\Exceptions\AttemptExpiredException;
use App\Exceptions\AttemptLimitReachedException;
use App\Exceptions\GameNotPublishedException;
use App\Exceptions\InvalidAttemptTokenException;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameProgress;
use App\Services\AttemptAuditLog;
use App\Services\AttemptTokenService;
use App\Services\ProgressBoardService;
use App\Services\ScoringService;
use App\Support\AttemptResult;
use App\Support\AttemptToken;
use Illuminate\Support\Facades\DB;

/**
 * The child's result comes back. Everything that decides the grade is
 * measured here, on the server, against the token the server issued:
 *
 *   elapsed   = now − token.started          (server clock both ends)
 *   floor     = now − child's previous result (time already credited once)
 *   effective = min(app's claim, elapsed, floor, game length)
 *
 * Then, in one transaction that holds the child's subscriber row:
 * the board row, the day's row and the child's watermarks. The subscriber lock
 * is taken first, always — skipping does the same — so two results for one
 * child can never interleave, and the floor is read from the locked row.
 */
class CompleteAttemptAction
{
    public function __construct(
        protected AttemptTokenService $tokens,
        protected ScoringService $scoring,
        protected ProgressBoardService $board,
        protected AttemptAuditLog $audit,
    ) {}

    /**
     * @param  array{attempt_token: string, duration_seconds?: int|null}  $input
     *
     * @throws InvalidAttemptTokenException
     * @throws AttemptExpiredException
     * @throws AttemptAlreadyUsedException
     * @throws AttemptLimitReachedException
     * @throws GameNotPublishedException
     */
    public function execute(Subscriber $user, Game $game, array $input): AttemptResult
    {
        $now = now();
        $nowUs = (int) $now->getPreciseTimestamp(6);

        $token = $this->tokens->parse($input['attempt_token'], $now);

        // one code for every "not your token" case — a leaked token must not
        // tell its holder whose it is or which game it opens
        if ($token->subscriberId !== $user->id || $token->gameId !== $game->id) {
            throw new InvalidAttemptTokenException;
        }

        if (! $game->isPlayable()) {
            throw new GameNotPublishedException('هذه اللعبة غير متاحة للعب حالياً.');
        }

        if ($nowUs - $token->startedUs > $this->scoring->tokenLifetimeSeconds($game) * 1_000_000) {
            throw new AttemptExpiredException;
        }

        $claimed = isset($input['duration_seconds']) ? max(0, (int) $input['duration_seconds']) : null;

        // three attempts: a deadlock between two children's first rows is
        // retried, and the closure re-reads every guard so it is safe to rerun
        return DB::transaction(function () use ($user, $game, $token, $claimed, $now, $nowUs) {
            // the locked instance, never the one Sanctum hydrated before the lock
            $child = Subscriber::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $row = $this->board->rowForUpdate($child->id, $game->id);

            if ($child->last_attempt_started_us !== null) {
                if ($token->startedUs < $child->last_attempt_started_us) {
                    throw new AttemptAlreadyUsedException;
                }
                // the app resent the attempt the server graded last (the
                // response was lost) — answer it again, write nothing. Same
                // microsecond with a different nonce is a different token.
                if ($token->startedUs === $child->last_attempt_started_us
                    && $token->nonce === $child->last_attempt_nonce) {
                    return $this->replay($token, $row, $game);
                }
            }

            $this->board->rollDayIfNeeded($row, $now);
            if ($row->today_attempts >= (int) config('scoring.max_attempts_per_day', 200)) {
                throw new AttemptLimitReachedException;
            }

            $elapsed = $token->elapsedSecondsAt($nowUs);
            $floor = $child->last_attempt_completed_us !== null
                ? max(0, intdiv($nowUs - $child->last_attempt_completed_us, 1_000_000))
                : null;

            $effective = $this->scoring->effectiveSeconds($elapsed, $claimed, $game, $floor);
            $required = $this->scoring->requiredSeconds($game);
            $score = $this->scoring->score($effective, $required);
            // every attempt is exactly one of passed / failed / short: an attempt
            // too short to count cannot pass, however low the game's bar is
            $counted = $this->scoring->counts($effective, $game);

            $result = new AttemptResult(
                gameId: $game->id,
                curriculumDayId: $token->curriculumDayId,
                startedAt: $token->startedAt(),
                completedAt: $now,
                claimedSeconds: $claimed,
                elapsedSeconds: $elapsed,
                effectiveSeconds: $effective,
                requiredSeconds: $required,
                score: $score,
                requiredScore: $this->scoring->requiredScore($game),
                passed: $counted && $this->scoring->passes($score, $game),
                counted: $counted,
            );

            $this->board->recordAttempt($row, $game, $result);

            $child->forceFill([
                'last_attempt_started_us' => $token->startedUs,
                'last_attempt_completed_us' => $nowUs,
                'last_attempt_nonce' => $token->nonce,
                'last_activity_at' => $now,
            ])->save();

            $result->progress = $this->scoring->progressFor($game, $row);

            DB::afterCommit(fn () => $this->audit->completed($child, $game, $token, $result));

            return $result;
        }, attempts: 3);
    }

    private function replay(AttemptToken $token, SubscriberGameProgress $row, Game $game): AttemptResult
    {
        $seconds = (int) $row->last_seconds;

        return new AttemptResult(
            gameId: $game->id,
            curriculumDayId: $token->curriculumDayId,
            startedAt: $token->startedAt(),
            completedAt: $row->last_played_at ?? now(),
            claimedSeconds: null,
            elapsedSeconds: $seconds,
            effectiveSeconds: $seconds,
            requiredSeconds: $this->scoring->requiredSeconds($game),
            score: (int) $row->last_score,
            requiredScore: $this->scoring->requiredScore($game),
            passed: (bool) $row->last_passed,
            counted: $this->scoring->counts($seconds, $game),
            replayed: true,
            progress: $this->scoring->progressFor($game, $row),
        );
    }
}
