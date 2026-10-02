<?php

namespace App\Enums;

/**
 * Applicant onboarding status (spec §14).
 */
enum ApplicantStatus: string
{
    case Draft                = 'draft';
    case Submitted            = 'submitted';
    case UnderReview          = 'under_review';
    case TeamLeaderScreening  = 'team_leader_screening';
    case BranchAssignment     = 'branch_assignment';
    case RecordVerification   = 'record_verification';
    case AccountCreation      = 'account_creation';
    case Approved             = 'approved';
    case Rejected             = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft                => 'Draft',
            self::Submitted            => 'Submitted',
            self::UnderReview           => 'Under Review',
            self::TeamLeaderScreening   => 'Team Leader Screening',
            self::BranchAssignment      => 'Branch Assignment',
            self::RecordVerification    => 'Record Verification',
            self::AccountCreation       => 'Account Creation',
            self::Approved              => 'Approved',
            self::Rejected              => 'Rejected',
        };
    }
}
