<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\GameType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'type',
        'axis_id',
        'skill_id',
        'level_id',
        'level',
        'difficulty',
        'min_age',
        'max_age',
        'duration_seconds',
        'status',
        'version',
        'config',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => GameType::class,
            'status' => GameStatus::class,
            'level' => 'integer',
            'min_age' => 'integer',
            'max_age' => 'integer',
            'duration_seconds' => 'integer',
            'version' => 'integer',
            'config' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', GameStatus::PUBLISHED);
    }

    public function isPlayable(): bool
    {
        return $this->status === GameStatus::PUBLISHED;
    }

    public function axis(): BelongsTo
    {
        return $this->belongsTo(Axis::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function gameLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function gameAssets(): HasMany
    {
        return $this->hasMany(GameAsset::class)->orderBy('sort_order');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'game_assets')
            ->withPivot(['id', 'role', 'sort_order', 'metadata'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function curriculumDayGames(): HasMany
    {
        return $this->hasMany(CurriculumDayGame::class);
    }
}
