<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The supplementary details asked for after the account exists — one row per
 * account. Every field is nullable: a row may be half-filled while the parent
 * is still on the form, and `completed_at` is what marks it finished.
 */
class SubscriberProfile extends Model
{
    use HasUuids;

    /** The fields that must all be present for the profile to count as complete. */
    public const REQUIRED_FIELDS = ['governorate_id', 'gender', 'age', 'family_order', 'delivery_type'];

    protected $fillable = [
        'subscriber_id',
        'governorate_id',
        'gender',
        'age',
        'family_order',
        'delivery_type',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'delivery_type' => DeliveryType::class,
            'age' => 'integer',
            'family_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /** @return list<string> the required fields still empty */
    public function missingFields(): array
    {
        return array_values(array_filter(
            self::REQUIRED_FIELDS,
            fn (string $field) => blank($this->getAttribute($field))
        ));
    }

    public function isComplete(): bool
    {
        return $this->missingFields() === [];
    }
}
