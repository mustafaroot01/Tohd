<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumWeek extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'curriculum_month_id',
        'week_number',
        'name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(CurriculumMonth::class, 'curriculum_month_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(CurriculumDay::class)->orderBy('day_number');
    }
}
