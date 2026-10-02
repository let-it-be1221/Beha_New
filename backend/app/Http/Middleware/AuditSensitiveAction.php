<?php

namespace App\Http\Middleware;

use App\Enums\AuditSeverity;
use App\Services\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditSensitiveAction middleware — wraps POST/PUT/DELETE routes
 * that touch sensitive resources. Writes an audit_log entry per request.
 *
 * Spec §24.
 */
class AuditSensitiveAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next, ?string $label = null): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $request->user()
        ) {
            $this->audit->log(
                action: 'http.' . strtolower($request->method()),
                entityType: $label ?? $request->path(),
                entityId: null,
                newValues: $request->except(['password', 'password_confirmation', '_token']),
                category: 'http',
                severity: $response->isSuccessful() ? AuditSeverity::Info : AuditSeverity::Warning,
            );
        }

        return $response;
    }
}
