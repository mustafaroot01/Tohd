<?php

namespace App\Actions\Subscribers;

use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriberActivityLogger;

class SuspendSubscriberAction
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * @throws DomainException
     */
    public function execute(Subscriber $subscriber, ?User $admin = null): Subscriber
    {
        if ($subscriber->isSuspended()) {
            throw new DomainException('الحساب موقوف بالفعل', 'ALREADY_SUSPENDED', 409);
        }

        $subscriber->update(['status' => SubscriberStatus::SUSPENDED]);

        $this->auditLog->log('SUBSCRIBER_SUSPENDED', 'Subscriber', $subscriber->id, null, null, $admin);
        $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_SUSPENDED);

        return $subscriber->fresh();
    }
}
