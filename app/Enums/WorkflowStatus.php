<?php

namespace App\Enums;

/**
 * Generic workflow instance status (spec §23).
 */
enum WorkflowStatus: string
{
    case InProgress          = 'in_progress';
    case Approved            = 'approved';
    case Rejected            = 'rejected';
    case CorrectionRequired  = 'correction_required';
    case Cancelled            = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress         => 'In Progress',
            self::Approved           => 'Approved',
            self::Rejected           => 'Rejected',
            self::CorrectionRequired => 'Correction Required',
            self::Cancelled          => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled], true);
    }
}
