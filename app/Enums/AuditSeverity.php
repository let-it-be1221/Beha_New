<?php

namespace App\Enums;

/**
 * Audit log severity (spec §24).
 */
enum AuditSeverity: string
{
    case Info     = 'info';
    case Warning  = 'warning';
    case Critical = 'critical';
}
