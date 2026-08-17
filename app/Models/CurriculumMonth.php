<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumMonth extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'curriculum_id',
        'month_number',
        'name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'month_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(CurriculumWeek::class)->orderBy('week_number');
    }
}
