<?php

namespace App\Services;

use App\Enums\GameSessionStatus;
use App\Models\Axis;
use App\Models\GameSession;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkillProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProgressCalculationService
{
    /**
     * Calculate and return overall progress summary for a user.
     */
    public function getOverallProgress(User $user): array
    {
        $completedSessions = GameSession::where('user_id', $user->id)
            ->where('status', GameSessionStatus::COMPLETED)
            ->with(['game.axis', 'game.skill'])
            ->get();

        $totalSessions = $completedSessions->count();
        $totalDurationSeconds = (int) $completedSessions->sum('duration_seconds');
        $uniqueGamesCompleted = $completedSessions->pluck('game_id')->unique()->count();
        $averageAccuracy = $totalSessions > 0 ? round($completedSessions->avg('accuracy'), 1) : 0;
        $totalScore = (int) $completedSessions->sum('score');

        return [
            'total_sessions' => $totalSessions,
            'total_games_completed' => $uniqueGamesCompleted,
            'total_duration_seconds' => $totalDurationSeconds,
            'total_duration_minutes' => round($totalDurationSeconds / 60, 1),
            'average_accuracy' => $averageAccuracy,
            'total_score' => $totalScore,
            'axes_progress' => $this->getAxisProgressBreakdown($user, $completedSessions),
            'skills_progress' => $this->getSkillProgressBreakdown($user, $completedSessions),
        ];
    }

    /**
     * Calculate skill-level breakdown and update the user_skill_progress cache table.
     */
    public function updateSkillProgressForUser(User $user, Skill $skill): UserSkillProgress
    {
        $sessions = GameSession::where('user_id', $user->id)
            ->where('status', GameSessionStatus::COMPLETED)
            ->whereHas('game', fn ($q) => $q->where('skill_id', $skill->id))
            ->get();

        $totalSessions = $sessions->count();
        $uniqueGames = $sessions->pluck('game_id')->unique()->count();
        $totalDuration = (int) $sessions->sum('duration_seconds');
        $avgAccuracy = $totalSessions > 0 ? round($sessions->avg('accuracy'), 1) : 0;
        $bestScore = (int) ($sessions->max('score') ?? 0);
        $lastPlayedAt = $sessions->max('completed_at');

        return UserSkillProgress::updateOrCreate(
            ['user_id' => $user->id, 'skill_id' => $skill->id],
            [
                'games_completed' => $uniqueGames,
                'total_sessions' => $totalSessions,
                'total_duration_seconds' => $totalDuration,
                'average_accuracy' => $avgAccuracy,
                'best_score' => $bestScore,
                'last_played_at' => $lastPlayedAt,
            ]
        );
    }

    /**
     * Get axis-level progress breakdown.
     */
    public function getAxisProgressBreakdown(User $user, ?Collection $completedSessions = null): array
    {
        if (! $completedSessions) {
            $completedSessions = GameSession::where('user_id', $user->id)
                ->where('status', GameSessionStatus::COMPLETED)
                ->with(['game.axis'])
                ->get();
        }

        $axes = Axis::with('skills')->orderBy('sort_order')->get();

        return $axes->map(function (Axis $axis) use ($completedSessions) {
            $axisSessions = $completedSessions->filter(fn ($s) => $s->game?->axis_id === $axis->id);
            $totalSessions = $axisSessions->count();
            $avgAccuracy = $totalSessions > 0 ? round($axisSessions->avg('accuracy'), 1) : 0;

            return [
                'axis_id' => $axis->id,
                'name' => $axis->name,
                'slug' => $axis->slug,
                'total_sessions' => $totalSessions,
                'average_accuracy' => $avgAccuracy,
                'total_duration_seconds' => (int) $axisSessions->sum('duration_seconds'),
            ];
        })->values()->toArray();
    }

    /**
     * Get skill-level progress breakdown.
     */
    public function getSkillProgressBreakdown(User $user, ?Collection $completedSessions = null): array
    {
        if (! $completedSessions) {
            $completedSessions = GameSession::where('user_id', $user->id)
                ->where('status', GameSessionStatus::COMPLETED)
                ->with(['game.skill'])
                ->get();
        }

        $skills = Skill::with('axis')->orderBy('sort_order')->get();

        return $skills->map(function (Skill $skill) use ($completedSessions) {
            $skillSessions = $completedSessions->filter(fn ($s) => $s->game?->skill_id === $skill->id);
            $totalSessions = $skillSessions->count();
            $avgAccuracy = $totalSessions > 0 ? round($skillSessions->avg('accuracy'), 1) : 0;

            return [
                'skill_id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'axis_name' => $skill->axis?->name,
                'total_sessions' => $totalSessions,
                'average_accuracy' => $avgAccuracy,
                'best_score' => (int) ($skillSessions->max('score') ?? 0),
                'total_duration_seconds' => (int) $skillSessions->sum('duration_seconds'),
            ];
        })->values()->toArray();
    }

    /**
     * Get periodic progress summary (Daily / Weekly / Monthly).
     */
    public function getPeriodicProgress(User $user, string $period = 'daily'): array
    {
        $startDate = match ($period) {
            'daily' => Carbon::now()->startOfDay(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfDay(),
        };

        $sessions = GameSession::where('user_id', $user->id)
            ->where('status', GameSessionStatus::COMPLETED)
            ->where('completed_at', '>=', $startDate)
            ->with(['game.axis', 'game.skill'])
            ->get();

        $totalSessions = $sessions->count();
        $totalDuration = (int) $sessions->sum('duration_seconds');
        $avgAccuracy = $totalSessions > 0 ? round($sessions->avg('accuracy'), 1) : 0;
        $totalScore = (int) $sessions->sum('score');

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'total_sessions' => $totalSessions,
            'total_duration_seconds' => $totalDuration,
            'total_duration_minutes' => round($totalDuration / 60, 1),
            'average_accuracy' => $avgAccuracy,
            'total_score' => $totalScore,
        ];
    }
}
