<?php

namespace App\Traits;

use App\Enums\AuditSeverity;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

/**
 * Auditable — writes a row to audit_logs after model events.
 * For model-level changes (create/update/delete), spatie/laravel-activitylog
 * is also wired up; this trait is for explicit action logging
 * (login, approve, reject, promote, generate-id, ...).
 */
trait Auditable
{
    public function recordAudit(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $old = null,
        ?array $new = null,
        string $category = 'general',
        AuditSeverity $severity = AuditSeverity::Info,
    ): AuditLog {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return AuditLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'entity_type' => $entityType ?? static::class,
            'entity_id'   => $entityId ?? ($this->id ?? null),
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
            'category'    => $category,
            'severity'    => $severity->value,
        ]);
    }
}
