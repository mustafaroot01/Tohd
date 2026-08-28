<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where a child stands on one game — the row every hot read consults.
 *
 * Written only by ProgressBoardService, inside a transaction that holds the
 * child's subscriber row, so two results for the same child never interleave.
 */
class SubscriberGameProgress extends Model
{
    use HasUuids;

    protected $table = 'subscriber_game_progress';

    protected $fillable = [
        'subscriber_id', 'game_id',
        'best_score', 'best_seconds', 'passed_at',
        'today_date', 'today_best_score', 'today_best_seconds', 'today_attempts', 'today_failed', 'today_short', 'skipped_at',
        'last_score', 'last_seconds', 'last_passed', 'last_played_at',
        'total_attempts', 'total_passed', 'total_short', 'total_score_sum', 'total_attention_seconds',
    ];

    protected function casts(): array
    {
        return [
            'best_score' => 'integer',
            'best_seconds' => 'integer',
            'passed_at' => 'datetime',
            'today_date' => 'date',
            'today_best_score' => 'integer',
            'today_best_seconds' => 'integer',
            'today_attempts' => 'integer',
            'today_failed' => 'integer',
            'today_short' => 'integer',
            'skipped_at' => 'datetime',
            'last_score' => 'integer',
            'last_seconds' => 'integer',
            'last_passed' => 'boolean',
            'last_played_at' => 'datetime',
            'total_attempts' => 'integer',
            'total_passed' => 'integer',
            'total_short' => 'integer',
            'total_score_sum' => 'integer',
            'total_attention_seconds' => 'integer',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** The today_* columns only mean anything on the day they were written. */
    public function isForToday(): bool
    {
        return $this->today_date !== null && $this->today_date->toDateString() === now()->toDateString();
    }
}
