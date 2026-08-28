<?php

namespace App\Services;

use App\Models\Axis;
use App\Models\Skill;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\SubscriberGameProgress;
use App\Support\WeekWindow;
use Illuminate\Support\Collection;

/**
 * Progress reports for one child.
 *
 * Two sources and nothing else: the board (one row per game the child has
 * touched) for "where do they stand", and the daily table for "how did the
 * period go". Neither grows with how often the child plays.
 */
class ProgressCalculationService
{
    public function __construct(protected ProgressBoardService $board) {}

    public function getOverallProgress(Subscriber $user): array
    {
        $rows = $this->boardRows($user);

        return [
            'total_attempts' => (int) $rows->sum('total_attempts'),
            'games_played' => $rows->count(),
            'games_passed' => $rows->whereNotNull('passed_at')->count(),
            // ever skipped, from the history — the board only remembers today's skip
            'games_skipped' => SubscriberGameDaily::query()->where('subscriber_id', $user->id)->whereNotNull('skipped_at')->distinct()->count('game_id'),
            'attention_seconds' => (int) $rows->sum('total_attention_seconds'),
            'attention_minutes' => round(((int) $rows->sum('total_attention_seconds')) / 60, 1),
            'grades' => $this->gradesFromBoard($rows),
            'axes_progress' => $this->getAxisProgressBreakdown($user, $rows),
            'skills_progress' => $this->getSkillProgressBreakdown($user, $rows),
        ];
    }

    /**
     * The attention grade rolled up over board rows.
     *
     * Three numbers kept apart so the grade stays readable to a parent: the
     * average (how they usually do), the best (what they can do), and the pass
     * rate (how often they reach the bar).
     *
     * @param  Collection<int, SubscriberGameProgress>  $rows
     * @return array{graded_attempts: int, short_attempts: int, average_score: float|null, best_score: int|null, passed_attempts: int, pass_rate: float|null, games_passed: int, attention_seconds: int}
     */
    public function gradesFromBoard(Collection $rows): array
    {
        $attempts = (int) $rows->sum('total_attempts');
        $passed = (int) $rows->sum('total_passed');

        return [
            'graded_attempts' => $attempts,
            'short_attempts' => (int) $rows->sum('total_short'),
            'average_score' => $attempts > 0 ? round($rows->sum('total_score_sum') / $attempts, 1) : null,
            'best_score' => $attempts > 0 ? (int) $rows->max('best_score') : null,
            'passed_attempts' => $passed,
            'pass_rate' => $attempts > 0 ? round(($passed / $attempts) * 100, 1) : null,
            'games_passed' => $rows->whereNotNull('passed_at')->count(),
            'attention_seconds' => (int) $rows->sum('total_attention_seconds'),
        ];
    }

    /** @param  Collection<int, SubscriberGameProgress>|null  $board */
    public function getAxisProgressBreakdown(Subscriber $user, ?Collection $board = null): array
    {
        $board ??= $this->boardRows($user);

        return Axis::orderBy('sort_order')->get()->map(function (Axis $axis) use ($board) {
            $rows = $board->filter(fn ($r) => $r->game?->axis_id === $axis->id);

            return [
                'axis_id' => $axis->id,
                'name' => $axis->name,
                'slug' => $axis->slug,
                'games_played' => $rows->count(),
                'grades' => $this->gradesFromBoard($rows),
            ];
        })->values()->toArray();
    }

    /** @param  Collection<int, SubscriberGameProgress>|null  $board */
    public function getSkillProgressBreakdown(Subscriber $user, ?Collection $board = null): array
    {
        $board ??= $this->boardRows($user);

        return Skill::with('axis')->orderBy('sort_order')->get()->map(function (Skill $skill) use ($board) {
            $rows = $board->filter(fn ($r) => $r->game?->skill_id === $skill->id);

            return [
                'skill_id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'axis_name' => $skill->axis?->name,
                'games_played' => $rows->count(),
                'grades' => $this->gradesFromBoard($rows),
            ];
        })->values()->toArray();
    }

    /**
     * Today / this month, summed from the daily rows. The week has its own
     * report (WeeklyReportService) because it is the unit of evaluation.
     */
    public function getPeriodicProgress(Subscriber $user, string $period = 'daily'): array
    {
        $now = now();

        [$from, $to] = match ($period) {
            'monthly' => [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()],
            'weekly' => [WeekWindow::containing($now)->start->toDateString(), $now->toDateString()],
            default => [$now->toDateString(), $now->toDateString()],
        };

        return [
            'period' => $period,
            'start_date' => $from,
            'end_date' => $to,
            ...$this->summarize($this->board->dailyRows($user, $from, $to)),
        ];
    }

    /**
     * One period of daily rows, added up.
     *
     * @param  Collection<int, SubscriberGameDaily>  $rows
     * @return array{days_active: int, attempts: int, failed: int, short: int, games_played: int, games_passed: int, games_skipped: int, attention_seconds: int, best_score: int|null, average_best_score: float|null, average_score: float|null, pass_rate: float|null}
     */
    public function summarize(Collection $rows): array
    {
        $played = $rows->where('attempts', '>', 0);
        $attempts = (int) $rows->sum('attempts');
        $passedDays = $rows->whereNotNull('passed_at')->count();
        $bestScores = $played->pluck('best_score')->filter(fn ($s) => $s !== null);

        return [
            'days_active' => $played->pluck('date')->map(fn ($d) => $d->toDateString())->unique()->count(),
            'attempts' => $attempts,
            'failed' => (int) $rows->sum('failed'),
            'short' => (int) $rows->sum('short'),
            'games_played' => $played->pluck('game_id')->unique()->count(),
            'games_passed' => $rows->whereNotNull('passed_at')->pluck('game_id')->unique()->count(),
            'games_skipped' => $rows->whereNotNull('skipped_at')->pluck('game_id')->unique()->count(),
            'attention_seconds' => (int) $rows->sum('attention_seconds'),
            'best_score' => $bestScores->isNotEmpty() ? (int) $bestScores->max() : null,
            // the mean of each day's best, per game — the grade a specialist reads
            'average_best_score' => $bestScores->isNotEmpty() ? round($bestScores->avg(), 1) : null,
            // the mean over every attempt, including the poor ones
            'average_score' => $attempts > 0 ? round($rows->sum('score_sum') / $attempts, 1) : null,
            'pass_rate' => $played->count() > 0 ? round(($passedDays / $played->count()) * 100, 1) : null,
        ];
    }

    /** @return Collection<int, SubscriberGameProgress> board rows with their game's axis/skill */
    private function boardRows(Subscriber $user): Collection
    {
        return SubscriberGameProgress::query()
            ->where('subscriber_id', $user->id)
            ->with('game:id,axis_id,skill_id')
            ->get();
    }
}
