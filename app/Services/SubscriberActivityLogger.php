<?php

namespace App\Services;

use App\Enums\SubscriberActivityType;
use App\Models\Subscriber;
use App\Models\SubscriberActivity;

class SubscriberActivityLogger
{
    /**
     * Log a subscriber-facing activity event for the account timeline.
     */
    public function log(Subscriber $subscriber, SubscriberActivityType $type, ?array $metadata = null): SubscriberActivity
    {
        return SubscriberActivity::create([
            'subscriber_id' => $subscriber->id,
            'type' => $type,
            'metadata' => $metadata,
        ]);
    }
}
