<?php

namespace App\Services\Audit;

use App\Enums\AuditSeverity;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

/**
 * AuditLogger — single entry point for explicit action audit logging.
 *
 * Spec §24.
 *
 * (Model-level change tracking is handled separately via
 * spatie/laravel-activitylog; this logger is for workflow actions,
 * login, ID generation, and other explicit events.)
 */
class AuditLogger
{
    public function log(
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
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
            'category'    => $category,
            'severity'    => $severity->value,
        ]);
    }
}
