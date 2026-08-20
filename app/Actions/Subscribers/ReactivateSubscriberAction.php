<?php

namespace App\Actions\Subscribers;

use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Exceptions\DomainException;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriberActivityLogger;

class ReactivateSubscriberAction
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
        if (! $subscriber->isSuspended()) {
            throw new DomainException('لا يمكن إعادة تفعيل حساب غير موقوف', 'NOT_SUSPENDED', 409);
        }

        $subscriber->update(['status' => SubscriberStatus::ACTIVE]);

        $this->auditLog->log('SUBSCRIBER_REACTIVATED', 'Subscriber', $subscriber->id, null, null, $admin);
        $this->activityLogger->log($subscriber, SubscriberActivityType::ACCOUNT_REACTIVATED);

        return $subscriber->fresh();
    }
}
