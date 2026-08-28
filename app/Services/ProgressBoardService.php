<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\SubscriberGameProgress;
use App\Support\AttemptResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The two places a result lands.
 *
 *   subscriber_game_progress — one row per (child, game): where the child stands now
 *   subscriber_game_daily    — one row per (child, day, game): the history
 *
 * Every method that writes expects to be called inside a transaction that
 * already holds the child's subscriber row FOR UPDATE (see
 * CompleteAttemptAction). That single lock is what makes the read-modify-write
 * below safe without any further locking on the daily table.
 */
class ProgressBoardService
{
    /**
     * The (child, game) row, locked. The row is created first if missing:
     * SELECT … FOR UPDATE on a row that does not exist takes a gap lock, and
     * two children whose first rows fall in the same gap would deadlock on
     * their inserts. A record lock on an existing row cannot.
     */
    public function rowForUpdate(string $subscriberId, string $gameId): SubscriberGameProgress
    {
        SubscriberGameProgress::query()->insertOrIgnore([
            'id' => (string) Str::uuid7(),
            'subscriber_id' => $subscriberId,
            'game_id' => $gameId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SubscriberGameProgress::query()
            ->where('subscriber_id', $subscriberId)
            ->where('game_id', $gameId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** First write of a new calendar day clears the today_* counters. */
    public function rollDayIfNeeded(SubscriberGameProgress $row, Carbon $at): void
    {
        $date = $at->toDateString();

        if ($row->today_date?->toDateString() === $date) {
            return;
        }

        $row->today_date = $date;
        $row->today_best_score = null;
        $row->today_best_seconds = 0;
        $row->today_attempts = 0;
        $row->today_failed = 0;
        $row->today_short = 0;
        $row->skipped_at = null;
    }

    /** Fold one graded attempt into the board row and the day's row. */
    public function recordAttempt(SubscriberGameProgress $row, Game $game, AttemptResult $result): void
    {
        $at = $result->completedAt;
        $this->rollDayIfNeeded($row, $at);

        $score = $result->score;
        $seconds = $result->effectiveSeconds;
        $passed = $result->passed;
        $counted = $result->counted;
        $failed = $counted && ! $passed;
        $short = ! $counted;

        $row->today_attempts++;
        $row->today_failed += $failed ? 1 : 0;
        $row->today_short += $short ? 1 : 0;

        // "best" is the best real try. A short attempt is recorded (attempts,
        // short, attention, last_*) but never sets a best — otherwise a
        // two-second glance could read as the day's grade, or as a pass on a
        // game with a very low bar.
        if ($counted) {
            $row->today_best_score = max((int) $row->today_best_score, $score);
            $row->today_best_seconds = max((int) $row->today_best_seconds, $seconds);
            $row->best_score = max((int) $row->best_score, $score);
            $row->best_seconds = max((int) $row->best_seconds, $seconds);
        }
        if ($passed && $row->passed_at === null) {
            $row->passed_at = $at;
        }

        $row->last_score = $score;
        $row->last_seconds = $seconds;
        $row->last_passed = $passed;
        $row->last_played_at = $at;

        $row->total_attempts++;
        $row->total_passed += $passed ? 1 : 0;
        $row->total_short += $short ? 1 : 0;
        $row->total_score_sum += $score;
        $row->total_attention_seconds += $seconds;

        $row->save();

        $this->writeDaily($row->subscriber_id, $game, $at, $result->curriculumDayId, function (?object $day) use ($score, $seconds, $passed, $failed, $short, $at, $counted) {
            return [
                'attempts' => (int) ($day->attempts ?? 0) + 1,
                'failed' => (int) ($day->failed ?? 0) + ($failed ? 1 : 0),
                'short' => (int) ($day->short ?? 0) + ($short ? 1 : 0),
                'best_score' => $counted ? max((int) ($day->best_score ?? 0), $score) : $day?->best_score,
                'best_seconds' => $counted ? max((int) ($day->best_seconds ?? 0), $seconds) : (int) ($day->best_seconds ?? 0),
                'last_score' => $score,
                'last_seconds' => $seconds,
                'last_passed' => $passed,
                'score_sum' => (int) ($day->score_sum ?? 0) + $score,
                'attention_seconds' => (int) ($day->attention_seconds ?? 0) + $seconds,
                'passed_at' => $day?->passed_at ?? ($passed ? $at : null),
                'first_played_at' => $day?->first_played_at ?? $at,
                'last_played_at' => $at,
            ];
        }, $result->requiredScore, $result->requiredSeconds);
    }

    /** The child chose to move past this game today. */
    public function recordSkip(SubscriberGameProgress $row, Game $game, Carbon $at, ?string $curriculumDayId, int $requiredScore, int $requiredSeconds): void
    {
        $this->rollDayIfNeeded($row, $at);
        $row->skipped_at = $at;
        $row->save();

        $this->writeDaily($row->subscriber_id, $game, $at, $curriculumDayId, fn (?object $day) => [
            'skipped_at' => $day?->skipped_at ?? $at,
        ], $requiredScore, $requiredSeconds);
    }

    /**
     * Board rows for a set of games, keyed by game_id. One query.
     *
     * @param  iterable<int, string>  $gameIds
     * @return Collection<string, SubscriberGameProgress>
     */
    public function rowsFor(Subscriber $subscriber, iterable $gameIds): Collection
    {
        return SubscriberGameProgress::query()
            ->where('subscriber_id', $subscriber->id)
            ->whereIn('game_id', collect($gameIds)->all())
            ->get()
            ->keyBy('game_id');
    }

    /**
     * Daily rows between two dates inclusive — one contiguous range on the
     * primary key.
     *
     * @return Collection<int, SubscriberGameDaily>
     */
    public function dailyRows(Subscriber $subscriber, string $fromDate, string $toDate): Collection
    {
        return SubscriberGameDaily::query()
            ->where('subscriber_id', $subscriber->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Rebuild a child's board from the daily rows. A repair tool, never on the
     * hot path. The replay watermark lives on the subscriber row and is left
     * alone, so an outstanding token cannot be redeemed twice across a rebuild.
     */
    public function rebuildFor(Subscriber $subscriber): int
    {
        return DB::transaction(function () use ($subscriber) {
            Subscriber::whereKey($subscriber->id)->lockForUpdate()->first();
            SubscriberGameProgress::where('subscriber_id', $subscriber->id)->delete();

            $today = now()->toDateString();
            $count = 0;

            SubscriberGameDaily::query()
                ->where('subscriber_id', $subscriber->id)
                ->orderBy('date')
                ->get()
                ->groupBy('game_id')
                ->each(function (Collection $days, string $gameId) use ($subscriber, $today, &$count) {
                    $latest = $days->sortByDesc(fn ($d) => $d->last_played_at?->getTimestamp() ?? 0)->first();
                    $todayRow = $days->firstWhere(fn ($d) => $d->date->toDateString() === $today);
                    $firstPass = $days->whereNotNull('passed_at')->sortBy(fn ($d) => $d->passed_at->getTimestamp())->first();

                    SubscriberGameProgress::create([
                        'subscriber_id' => $subscriber->id,
                        'game_id' => $gameId,
                        'best_score' => $days->max('best_score'),
                        'best_seconds' => (int) $days->max('best_seconds'),
                        'passed_at' => $firstPass?->passed_at,
                        'today_date' => $todayRow ? $today : null,
                        'today_best_score' => $todayRow?->best_score,
                        'today_best_seconds' => (int) ($todayRow?->best_seconds ?? 0),
                        'today_attempts' => (int) ($todayRow?->attempts ?? 0),
                        'today_failed' => (int) ($todayRow?->failed ?? 0),
                        'today_short' => (int) ($todayRow?->short ?? 0),
                        'skipped_at' => $todayRow?->skipped_at,
                        'last_score' => $latest?->last_score,
                        'last_seconds' => (int) ($latest?->last_seconds ?? 0),
                        'last_passed' => (bool) ($latest?->last_passed ?? false),
                        'last_played_at' => $latest?->last_played_at,
                        'total_attempts' => (int) $days->sum('attempts'),
                        // every attempt is exactly one of passed / failed / short
                        'total_passed' => (int) $days->sum(fn ($d) => $d->attempts - $d->failed - $d->short),
                        'total_short' => (int) $days->sum('short'),
                        'total_score_sum' => (int) $days->sum('score_sum'),
                        'total_attention_seconds' => (int) $days->sum('attention_seconds'),
                    ]);
                    $count++;
                });

            return $count;
        });
    }

    /**
     * Read-modify-write of the (child, date, game) row. Safe only because the
     * caller holds the child's subscriber row — the daily table itself is never
     * locked.
     *
     * @param  callable(?object): array<string, mixed>  $changes
     */
    private function writeDaily(string $subscriberId, Game $game, Carbon $at, ?string $curriculumDayId, callable $changes, int $requiredScore, int $requiredSeconds): void
    {
        $key = ['subscriber_id' => $subscriberId, 'date' => $at->toDateString(), 'game_id' => $game->id];

        $existing = DB::table('subscriber_game_daily')->where($key)->first();
        $values = $this->datesToStrings($changes($existing));

        if ($existing) {
            DB::table('subscriber_game_daily')->where($key)->update($values);

            return;
        }

        DB::table('subscriber_game_daily')->insert(array_merge($key, [
            'curriculum_day_id' => $curriculumDayId,
            'required_score' => $requiredScore,
            'required_seconds' => $requiredSeconds,
        ], $values));
    }

    /** @param  array<string, mixed>  $values */
    private function datesToStrings(array $values): array
    {
        foreach ($values as $k => $v) {
            if ($v instanceof \DateTimeInterface) {
                $values[$k] = Carbon::instance($v)->toDateTimeString();
            }
        }

        return $values;
    }
}
