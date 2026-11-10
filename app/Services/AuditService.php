<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an audit log entry for system activities and regulatory compliance.
     *
     * @param string $action Action name (e.g. SHIPMENT_CREATED, STATUS_TRANSITION, EXCEPTION_REPORTED)
     * @param Model|null $entity The target eloquent model being acted upon
     * @param array|null $oldValues Previous attributes before update
     * @param array|null $newValues New attributes after update
     * @param User|null $actor The user performing the action
     * @return AuditLog
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? auth()->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : 'SYSTEM',
            'entity_id' => $entity?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent() ? substr(Request::userAgent(), 0, 500) : null,
        ]);
    }
}
