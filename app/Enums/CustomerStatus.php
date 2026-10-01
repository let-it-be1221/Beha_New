<?php

namespace App\Enums;

/**
 * Customer workflow status (spec §8–§9).
 */
enum CustomerStatus: string
{
    case Draft                     = 'draft';
    case Submitted                 = 'submitted';
    case PendingTeamEvaluation    = 'pending_team_evaluation';
    case ApprovedByTeam            = 'approved_by_team';
    case PendingRecordApproval    = 'pending_record_approval';
    case Registered                = 'registered';
    case Rejected                  = 'rejected';
    case CorrectionRequired       = 'correction_required';

    public function label(): string
    {
        return match ($this) {
            self::Draft                  => 'Draft',
            self::Submitted              => 'Submitted',
            self::PendingTeamEvaluation  => 'Pending Team Evaluation',
            self::ApprovedByTeam          => 'Approved by Team Leader',
            self::PendingRecordApproval  => 'Pending Record Approval',
            self::Registered             => 'Registered',
            self::Rejected                => 'Rejected',
            self::CorrectionRequired     => 'Correction Required',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Submitted             => 'gray',
            self::PendingTeamEvaluation, self::PendingRecordApproval, self::ApprovedByTeam
                => 'amber',
            self::Registered                          => 'green',
            self::Rejected                            => 'red',
            self::CorrectionRequired                  => 'orange',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Registered, self::Rejected], true);
    }
}
