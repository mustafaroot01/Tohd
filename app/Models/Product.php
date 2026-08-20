<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'curriculum_id',
        'duration_days',
        'status',
        'price',
        'metadata',
    ];

    /**
     * The platform operates in Iraqi Dinar (IQD) only — there is no per-product
     * currency selection, so revenue aggregation never mixes units.
     */
    public const CURRENCY = 'IQD';

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::ACTIVE;
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function activationCodes(): HasMany
    {
        return $this->hasMany(ActivationCode::class);
    }
}
