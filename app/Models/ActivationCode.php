<?php

namespace App\Models;

use App\Enums\ActivationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivationCode extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'product_id',
        'status',
        'activated_by',
        'activated_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ActivationStatus::AVAILABLE);
    }

    public function isRedeemable(): bool
    {
        return $this->status === ActivationStatus::AVAILABLE
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'activated_by');
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(UserCurriculumAssignment::class, 'activation_id');
    }
}
