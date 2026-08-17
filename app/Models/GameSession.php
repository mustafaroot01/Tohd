<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'game_id',
        'curriculum_day_id',
        'started_at',
        'completed_at',
        'duration_seconds',
        'attempts',
        'correct_attempts',
        'incorrect_attempts',
        'score',
        'accuracy',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'attempts' => 'integer',
            'correct_attempts' => 'integer',
            'incorrect_attempts' => 'integer',
            'score' => 'integer',
            'accuracy' => 'float',
            'metadata' => 'array',
        ];
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', GameSessionStatus::COMPLETED);
    }

    public function isCompleted(): bool
    {
        return $this->status === GameSessionStatus::COMPLETED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function curriculumDay(): BelongsTo
    {
        return $this->belongsTo(CurriculumDay::class, 'curriculum_day_id');
    }
}
