<?php

namespace App\Actions\Subscribers;

use App\Enums\SubscriberStatus;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;

class CreateSubscriberAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function execute(array $data, ?User $admin = null): Subscriber
    {
        $status = $data['status'] ?? SubscriberStatus::ACTIVE;

        $subscriber = Subscriber::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $status,
            'phone_verified_at' => $status === SubscriberStatus::ACTIVE ? now() : null,
        ]);

        $this->auditLog->log('SUBSCRIBER_CREATED', 'Subscriber', $subscriber->id, null, [
            'name' => $subscriber->name,
            'phone' => $subscriber->phone,
            'status' => $subscriber->status->value,
        ], $admin);

        return $subscriber;
    }
}
