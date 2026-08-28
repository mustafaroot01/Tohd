<?php

namespace App\Actions\Subscribers;

use App\Enums\SubscriberActivityType;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriberActivityLogger;
use Illuminate\Support\Facades\Hash;

class UpdateSubscriberAction
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected SubscriberActivityLogger $activityLogger,
    ) {}

    /**
     * An admin edit is trusted: a phone set from the dashboard is taken as
     * verified, no code is sent. The change is logged on both the admin audit
     * trail and the subscriber's own timeline.
     */
    public function execute(Subscriber $subscriber, array $data, ?User $admin = null): Subscriber
    {
        $oldValues = $subscriber->only(['name', 'phone', 'address', 'status']);

        $phoneChanged = isset($data['phone']) && $data['phone'] !== $subscriber->phone;

        $updates = array_intersect_key($data, array_flip(['name', 'phone', 'address', 'status']));

        if (isset($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        if ($phoneChanged) {
            $updates['phone_verified_at'] = now();
        }

        $subscriber->update($updates);

        $this->auditLog->log('SUBSCRIBER_UPDATED', 'Subscriber', $subscriber->id, $oldValues, $subscriber->only(['name', 'phone', 'address', 'status']), $admin);

        if ($phoneChanged) {
            $this->activityLogger->log($subscriber, SubscriberActivityType::PHONE_CHANGED, ['new_phone' => $subscriber->phone, 'by_admin' => true]);
        }

        return $subscriber->fresh();
    }
}
