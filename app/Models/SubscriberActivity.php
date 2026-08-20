<?php

namespace App\Models;

use App\Enums\SubscriberActivityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriberActivity extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'subscriber_id',
        'type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => SubscriberActivityType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
