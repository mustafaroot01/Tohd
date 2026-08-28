<?php

namespace App\Services;

use App\Enums\GameProgressStatus;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Support\WeekWindow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The weekly evaluation, game by game.
 *
 * Recorded daily, summed weekly: a week is never stored, it is seven daily
 * rows added up — so the definition of a week (Saturday to Friday, Baghdad)
 * can change without touching a single row, and a month is the same sum over
 * thirty.
 */
class WeeklyReportService
{
    public function __construct(
        protected ProgressBoardService $board,
        protected ProgressCalculationService $progress,
    ) {}

    public function build(Subscriber $subscriber, WeekWindow $week): array
    {
        $previous = $week->previous();

        // both weeks in one contiguous read
        $rows = $this->board->dailyRows($subscriber, $previous->start->toDateString(), $week->end->toDateString());
        $thisWeek = $rows->filter(fn (SubscriberGameDaily $r) => $week->contains($r->date));
        $lastWeek = $rows->filter(fn (SubscriberGameDaily $r) => $previous->contains($r->date));

        $games = Game::query()
            ->whereIn('id', $rows->pluck('game_id')->unique())
            ->with(['axis:id,name', 'skill:id,name'])
            ->get()
            ->keyBy('id');

        $summary = $this->progress->summarize($thisWeek);
        $previousSummary = $lastWeek->isEmpty() ? null : $this->progress->summarize($lastWeek);

        $gameRows = $thisWeek->groupBy('game_id')
            ->map(fn (Collection $days, string $gameId) => $this->gameRow(
                $games->get($gameId),
                $days,
                $lastWeek->where('game_id', $gameId),
                $week,
            ))
            ->sortBy(fn (array $g) => [$g['game']['axis']['name'] ?? '', $g['game']['name'] ?? ''])
            ->values();

        return [
            'period' => 'weekly',
            'week' => $week->toArray(),
            'previous_week' => $previous->toArray(),
            'summary' => $summary,
            'previous_summary' => $previousSummary,
            'delta' => $previousSummary ? $this->delta($summary, $previousSummary) : null,
            'days' => $this->days($week, $thisWeek),
            'games' => $gameRows,
            'axes' => $this->groupRows($gameRows, 'axis'),
            'skills' => $this->groupRows($gameRows, 'skill'),
        ];
    }

    /**
     * @param  Collection<int, SubscriberGameDaily>  $days  this week's rows for the game
     * @param  Collection<int, SubscriberGameDaily>  $previous  last week's rows for the game
     */
    private function gameRow(?Game $game, Collection $days, Collection $previous, WeekWindow $week): array
    {
        $latest = $days->sortByDesc('date')->first();
        $daysPlayed = $days->where('attempts', '>', 0)->count();
        $passedDays = $days->whereNotNull('passed_at')->count();
        $skippedDays = $days->whereNotNull('skipped_at')->count();
        $bestScores = $days->where('attempts', '>', 0)->pluck('best_score')->filter(fn ($s) => $s !== null);

        $status = match (true) {
            $passedDays > 0 => GameProgressStatus::PASSED,
            $skippedDays > 0 => GameProgressStatus::SKIPPED,
            $daysPlayed > 0 => GameProgressStatus::IN_PROGRESS,
            default => GameProgressStatus::NOT_STARTED,
        };

        $current = [
            'days_played' => $daysPlayed,
            'attempts' => (int) $days->sum('attempts'),
            'failed' => (int) $days->sum('failed'),
            'short' => (int) $days->sum('short'),
            'best_score' => $bestScores->isNotEmpty() ? (int) $bestScores->max() : null,
            'average_best_score' => $bestScores->isNotEmpty() ? round($bestScores->avg(), 1) : null,
            'attention_seconds' => (int) $days->sum('attention_seconds'),
            'days_passed' => $passedDays,
            'days_skipped' => $skippedDays,
        ];

        $prevBest = $previous->where('attempts', '>', 0)->pluck('best_score')->filter(fn ($s) => $s !== null);
        $prev = $previous->isEmpty() ? null : [
            'days_played' => $previous->where('attempts', '>', 0)->count(),
            'best_score' => $prevBest->isNotEmpty() ? (int) $prevBest->max() : null,
            'average_best_score' => $prevBest->isNotEmpty() ? round($prevBest->avg(), 1) : null,
            'attention_seconds' => (int) $previous->sum('attention_seconds'),
            'days_passed' => $previous->whereNotNull('passed_at')->count(),
        ];

        $byDate = $days->keyBy(fn (SubscriberGameDaily $d) => $d->date->toDateString());

        return [
            'game' => [
                'id' => $game?->id,
                'code' => $game?->code,
                'name' => $game?->name,
                'axis' => $game?->axis ? ['id' => $game->axis->id, 'name' => $game->axis->name] : null,
                'skill' => $game?->skill ? ['id' => $game->skill->id, 'name' => $game->skill->name] : null,
            ],
            'required_score' => (int) ($latest?->required_score ?? 0),
            'required_seconds' => (int) ($latest?->required_seconds ?? 0),
            'status' => $status->value,
            'status_label' => $status->label(),
            ...$current,
            'previous' => $prev,
            'delta' => $prev ? [
                'best_score' => $this->diff($current['best_score'], $prev['best_score']),
                'average_best_score' => $this->diff($current['average_best_score'], $prev['average_best_score']),
                'attention_seconds' => $current['attention_seconds'] - $prev['attention_seconds'],
                'days_played' => $current['days_played'] - $prev['days_played'],
            ] : null,
            'per_day' => collect($week->dates())->map(function (string $date) use ($byDate) {
                $d = $byDate->get($date);

                return [
                    'date' => $date,
                    'attempts' => (int) ($d?->attempts ?? 0),
                    'best_score' => $d?->best_score,
                    'attention_seconds' => (int) ($d?->attention_seconds ?? 0),
                    'is_passed' => $d?->passed_at !== null,
                    'is_skipped' => $d?->skipped_at !== null,
                ];
            })->values()->all(),
        ];
    }

    /** @param  Collection<int, SubscriberGameDaily>  $rows */
    private function days(WeekWindow $week, Collection $rows): array
    {
        $byDate = $rows->groupBy(fn (SubscriberGameDaily $d) => $d->date->toDateString());
        $today = now()->toDateString();

        return collect($week->dates())->map(function (string $date) use ($byDate, $today) {
            $day = $byDate->get($date, collect());
            $played = $day->where('attempts', '>', 0);

            return [
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('ar')->dayName,
                'is_today' => $date === $today,
                'is_future' => $date > $today,
                'attempts' => (int) $day->sum('attempts'),
                'attention_seconds' => (int) $day->sum('attention_seconds'),
                'games_played' => $played->count(),
                'games_passed' => $day->whereNotNull('passed_at')->count(),
                'games_skipped' => $day->whereNotNull('skipped_at')->count(),
                'best_score' => $played->isNotEmpty() ? (int) $played->max('best_score') : null,
            ];
        })->values()->all();
    }

    /** Roll the per-game rows up by axis or by skill. */
    private function groupRows(Collection $gameRows, string $key): array
    {
        return $gameRows
            ->filter(fn (array $g) => $g['game'][$key] !== null)
            ->groupBy(fn (array $g) => $g['game'][$key]['id'])
            ->map(function (Collection $games) use ($key) {
                $best = $games->pluck('best_score')->filter(fn ($s) => $s !== null);
                $avg = $games->pluck('average_best_score')->filter(fn ($s) => $s !== null);

                return [
                    'id' => $games->first()['game'][$key]['id'],
                    'name' => $games->first()['game'][$key]['name'],
                    'games_played' => $games->where('days_played', '>', 0)->count(),
                    'games_passed' => $games->where('days_passed', '>', 0)->count(),
                    'games_skipped' => $games->where('days_skipped', '>', 0)->count(),
                    'attention_seconds' => (int) $games->sum('attention_seconds'),
                    'best_score' => $best->isNotEmpty() ? (int) $best->max() : null,
                    'average_best_score' => $avg->isNotEmpty() ? round($avg->avg(), 1) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function delta(array $current, array $previous): array
    {
        return [
            'days_active' => $current['days_active'] - $previous['days_active'],
            'attempts' => $current['attempts'] - $previous['attempts'],
            'attention_seconds' => $current['attention_seconds'] - $previous['attention_seconds'],
            'games_passed' => $current['games_passed'] - $previous['games_passed'],
            'average_best_score' => $this->diff($current['average_best_score'], $previous['average_best_score']),
            'best_score' => $this->diff($current['best_score'], $previous['best_score']),
        ];
    }

    private function diff(int|float|null $a, int|float|null $b): int|float|null
    {
        if ($a === null || $b === null) {
            return null;
        }

        return round($a - $b, 1);
    }
}
