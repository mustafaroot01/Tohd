<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasUuids;

    /** So a freshly created row reports the same defaults the table applies. */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Hidden governorates stay linked to existing profiles; they just stop being offered. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(SubscriberProfile::class);
    }
}
