<?php

namespace App\Actions\Subscribers;

use App\Actions\Otp\SendOtpAction;
use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
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
        protected SendOtpAction $sendOtp
    ) {}

    public function execute(Subscriber $subscriber, array $data, ?User $admin = null): Subscriber
    {
        $oldValues = $subscriber->only(['name', 'phone', 'address', 'status']);

        $phoneChanged = isset($data['phone']) && $data['phone'] !== $subscriber->phone;

        $updates = array_intersect_key($data, array_flip(['name', 'phone', 'address', 'status']));

        if (isset($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        if ($phoneChanged) {
            // Changing the phone always forces re-verification, regardless of any
            // status value submitted in the same request.
            $updates['phone_verified_at'] = null;
            $updates['status'] = SubscriberStatus::UNVERIFIED;
        }

        $subscriber->update($updates);

        $this->auditLog->log('SUBSCRIBER_UPDATED', 'Subscriber', $subscriber->id, $oldValues, $subscriber->only(['name', 'phone', 'address', 'status']), $admin);

        if ($phoneChanged) {
            $this->activityLogger->log($subscriber, SubscriberActivityType::PHONE_CHANGED, ['new_phone' => $subscriber->phone]);
            $this->sendOtp->execute($subscriber, $subscriber->phone, OtpPurpose::PHONE_VERIFICATION);
        }

        return $subscriber->fresh();
    }
}
