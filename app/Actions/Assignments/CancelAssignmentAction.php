<?php

namespace App\Actions\Assignments;

use App\Enums\AssignmentStatus;
use App\Enums\SubscriberActivityType;
use App\Exceptions\DomainException;
use App\Models\UserCurriculumAssignment;
use App\Services\SubscriberActivityLogger;

class CancelAssignmentAction
{
    public function __construct(
        protected SubscriberActivityLogger $activityLogger
    ) {}

    /**
     * Cancel an active subscription assignment.
     *
     * @throws DomainException
     */
    public function execute(UserCurriculumAssignment $assignment, ?string $reason = null): UserCurriculumAssignment
    {
        if ($assignment->status !== AssignmentStatus::ACTIVE) {
            throw new DomainException('لا يمكن إلغاء اشتراك غير فعال', 'ASSIGNMENT_NOT_ACTIVE', 409);
        }

        $assignment->update([
            'status' => AssignmentStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->activityLogger->log($assignment->user, SubscriberActivityType::SUBSCRIPTION_CANCELLED, [
            'assignment_id' => $assignment->id,
            'reason' => $reason,
        ]);

        return $assignment->fresh(['curriculum', 'activation']);
    }
}
