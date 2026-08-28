<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumDay extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'curriculum_week_id',
        'day_number',
        'name',
        'description',
        'estimated_duration_seconds',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'estimated_duration_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(CurriculumWeek::class, 'curriculum_week_id');
    }

    public function dayGames(): HasMany
    {
        return $this->hasMany(CurriculumDayGame::class)->orderBy('sort_order');
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'curriculum_day_games')
            ->withPivot(['id', 'sort_order', 'is_required', 'config_override'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
