<?php

namespace App\Models;

use App\Enums\CurriculumStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'curriculums';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'status',
        'version',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CurriculumStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CurriculumStatus::PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === CurriculumStatus::PUBLISHED;
    }

    public function months(): HasMany
    {
        return $this->hasMany(CurriculumMonth::class)->orderBy('month_number');
    }

    public function weeks(): HasManyThrough
    {
        return $this->hasManyThrough(CurriculumWeek::class, CurriculumMonth::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserCurriculumAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
