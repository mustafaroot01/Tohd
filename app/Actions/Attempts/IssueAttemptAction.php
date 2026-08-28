<?php

namespace App\Actions\Attempts;

use App\Exceptions\GameNotPublishedException;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameProgress;
use App\Services\AttemptTokenService;
use App\Services\CurriculumDayResolver;
use App\Services\ScoringService;

/**
 * The child presses "start". Nothing is written: the server seals the moment
 * into a token and grades the attempt from it when the result comes back.
 */
class IssueAttemptAction
{
    public function __construct(
        protected AttemptTokenService $tokens,
        protected CurriculumDayResolver $days,
        protected ScoringService $scoring,
    ) {}

    /**
     * @throws GameNotPublishedException
     */
    public function execute(Subscriber $user, Game $game, ?string $claimedDayId = null): array
    {
        if (! $game->isPlayable()) {
            throw new GameNotPublishedException('هذه اللعبة غير متاحة للعب حالياً.');
        }

        $now = now();
        $day = $this->days->forGame($user, $game, $claimedDayId);
        $issued = $this->tokens->issue($user, $game, $day, $now);
        $lifetime = $this->scoring->tokenLifetimeSeconds($game);

        $row = SubscriberGameProgress::query()
            ->where('subscriber_id', $user->id)
            ->where('game_id', $game->id)
            ->first();

        $progress = $this->scoring->progressFor($game, $row);
        $progress['status_label'] = $progress['status']->label();
        $progress['status'] = $progress['status']->value;

        return [
            'attempt_token' => $issued['token'],
            'started_at' => $now->toISOString(),
            'expires_at' => $now->copy()->addSeconds($lifetime)->toISOString(),
            'expires_in_seconds' => $lifetime,
            'curriculum_day_id' => $day?->id,
            'game' => [
                'id' => $game->id,
                'code' => $game->code,
                'name' => $game->name,
                'required_seconds' => $this->scoring->requiredSeconds($game),
                'required_score' => $this->scoring->requiredScore($game),
                // an attempt shorter than this is recorded but not counted as a try
                'min_counted_seconds' => $this->scoring->minCountedSeconds($game),
            ],
            'progress' => $progress,
        ];
    }
}
