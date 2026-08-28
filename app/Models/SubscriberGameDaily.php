<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (child, day, game) — the history, at the resolution a specialist
 * evaluates at. Read-only through Eloquent: the composite primary key means
 * writes go through ProgressBoardService with the query builder.
 */
class SubscriberGameDaily extends Model
{
    protected $table = 'subscriber_game_daily';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'attempts' => 'integer',
            'failed' => 'integer',
            'short' => 'integer',
            'best_score' => 'integer',
            'best_seconds' => 'integer',
            'last_score' => 'integer',
            'last_seconds' => 'integer',
            'last_passed' => 'boolean',
            'score_sum' => 'integer',
            'attention_seconds' => 'integer',
            'required_score' => 'integer',
            'required_seconds' => 'integer',
            'passed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'first_played_at' => 'datetime',
            'last_played_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /** Guard against accidental writes through a model with a composite key. */
    public function save(array $options = []): bool
    {
        throw new \LogicException('subscriber_game_daily is written by ProgressBoardService only.');
    }
}
