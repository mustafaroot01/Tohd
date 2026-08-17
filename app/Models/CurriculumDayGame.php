<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumDayGame extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'curriculum_day_games';

    protected $fillable = [
        'curriculum_day_id',
        'game_id',
        'sort_order',
        'is_required',
        'config_override',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_required' => 'boolean',
            'config_override' => 'array',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(CurriculumDay::class, 'curriculum_day_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
