<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_skill_progress';

    protected $fillable = [
        'user_id',
        'skill_id',
        'games_completed',
        'total_sessions',
        'total_duration_seconds',
        'average_accuracy',
        'best_score',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'games_completed' => 'integer',
            'total_sessions' => 'integer',
            'total_duration_seconds' => 'integer',
            'average_accuracy' => 'float',
            'best_score' => 'integer',
            'last_played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
