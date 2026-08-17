<?php

namespace App\Actions\Activations;

use App\Enums\ActivationStatus;
use App\Models\ActivationCode;
use App\Models\User;
use App\Services\AuditLogService;

class RevokeActivationAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function execute(ActivationCode $activation, ?User $user = null): ActivationCode
    {
        $oldValues = $activation->toArray();

        $activation->update([
            'status' => ActivationStatus::REVOKED,
        ]);

        $this->auditLog->log('ACTIVATION_REVOKED', 'ActivationCode', $activation->id, $oldValues, $activation->fresh()->toArray(), $user);

        return $activation;
    }
}
