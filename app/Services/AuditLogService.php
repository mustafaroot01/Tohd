<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * Log an administrative or system action.
     */
    public function log(string $action, string $resourceType, string $resourceId, ?array $oldValues = null, ?array $newValues = null, ?User $user = null): AuditLog
    {
        // Sanitize sensitive keys
        $sanitizedOld = $oldValues ? $this->sanitize($oldValues) : null;
        $sanitizedNew = $newValues ? $this->sanitize($newValues) : null;

        return AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $sanitizedOld,
            'new_values' => $sanitizedNew,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'access_token', 'code', 'remember_token'];

        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
