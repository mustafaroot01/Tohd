<?php

namespace App\Actions\Attempts;

use App\Enums\GameProgressStatus;
use App\Enums\SubscriberActivityType;
use App\Exceptions\GameSkipNotAllowedException;
use App\Models\Game;
use App\Models\Subscriber;
use App\Services\AttemptAuditLog;
use App\Services\CurriculumDayResolver;
use App\Services\ProgressBoardService;
use App\Services\ScoringService;
use App\Services\SubscriberActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Records that a child chose to move past a game they could not pass.
 *
 * Skipping is a decision, not an inference: three counted failures alone tell
 * us the game was hard, but only an explicit skip tells us the child kept
 * going rather than closing the app. The two mean very different things to a
 * specialist, so the choice is written down — on the board, on the day's row,
 * and as a line in the account timeline.
 */
class SkipGameAction
{
    public function __construct(
        protected ScoringService $scoring,
        protected ProgressBoardService $board,
        protected CurriculumDayResolver $days,
        protected SubscriberActivityLogger $activityLogger,
        protected AttemptAuditLog $audit,
    ) {}

    /**
     * @return array{game_id: string, skipped_at: string, failed_attempts: int, best_score: int|null, game: array<string, mixed>}
     *
     * @throws GameSkipNotAllowedException
     */
    public function execute(Subscriber $user, Game $game, ?string $claimedDayId = null): array
    {
        $day = $this->days->forGame($user, $game, $claimedDayId);

        return DB::transaction(function () use ($user, $game, $day) {
            // same lock order as completing: subscriber first, then the board
            $child = Subscriber::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $row = $this->board->rowForUpdate($child->id, $game->id);
            $now = now();

            // one skip per game per day; a repeat is answered, not duplicated
            if ($row->skipped_at !== null && $row->isForToday()) {
                return $this->response($game, $row, $row->skipped_at);
            }

            $progress = $this->scoring->progressFor($game, $row);

            if ($progress['status'] === GameProgressStatus::PASSED) {
                throw new GameSkipNotAllowedException('هذه اللعبة ناجحة بالفعل ولا تحتاج إلى تخطٍّ.');
            }

            if (! $progress['can_skip']) {
                $unlockAfter = (int) config('scoring.attempts_before_unlock', 3);

                throw new GameSkipNotAllowedException(
                    $unlockAfter > 0
                        ? "يمكن تخطّي اللعبة بعد {$unlockAfter} محاولات، وقد أُجريت {$progress['failed_attempts']} حتى الآن."
                        : 'تخطّي الألعاب غير مفعّل.'
                );
            }

            $this->board->recordSkip(
                $row,
                $game,
                $now,
                $day?->id,
                $this->scoring->requiredScore($game),
                $this->scoring->requiredSeconds($game),
            );

            $this->activityLogger->log($child, SubscriberActivityType::GAME_SKIPPED, [
                'game_id' => $game->id,
                'game_code' => $game->code,
                'curriculum_day_id' => $day?->id,
                'failed_attempts' => $progress['failed_attempts'],
                'best_score' => $progress['score'],
                'required_score' => $progress['required_score'],
            ]);

            DB::afterCommit(fn () => $this->audit->skipped($child, $game, $day?->id, $progress));

            return $this->response($game, $row, $now);
        }, attempts: 3);
    }

    private function response(Game $game, $row, \DateTimeInterface $skippedAt): array
    {
        $progress = $this->scoring->progressFor($game, $row);
        $progress['status_label'] = $progress['status']->label();
        $progress['status'] = $progress['status']->value;

        return [
            'game_id' => $game->id,
            'skipped_at' => \Illuminate\Support\Carbon::instance($skippedAt)->toISOString(),
            'failed_attempts' => $progress['failed_attempts'],
            'best_score' => $progress['score'],
            'game' => $progress,
        ];
    }
}
