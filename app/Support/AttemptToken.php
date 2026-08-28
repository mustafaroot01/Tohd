<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * What the server asserted when the child pressed "start": who, which game,
 * which curriculum day, and — the part everything is graded against — when,
 * on the server's clock, in epoch microseconds.
 */
final class AttemptToken
{
    public function __construct(
        public readonly string $subscriberId,
        public readonly string $gameId,
        public readonly ?string $curriculumDayId,
        public readonly int $startedUs,
        public readonly string $nonce,
    ) {}

    public function startedAt(): Carbon
    {
        return Carbon::createFromTimestampMs(intdiv($this->startedUs, 1000))
            ->setTimezone(config('app.timezone'));
    }

    /** Whole seconds elapsed at $nowUs — floored, so 47.9 s is 47 s. */
    public function elapsedSecondsAt(int $nowUs): int
    {
        return max(0, intdiv($nowUs - $this->startedUs, 1_000_000));
    }
}
