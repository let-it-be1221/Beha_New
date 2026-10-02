<?php

namespace App\Enums;

/**
 * Property workflow status (spec §10–§12).
 */
enum PropertyStatus: string
{
    case Draft               = 'draft';
    case PendingVerification = 'pending_verification';
    case Verified            = 'verified';
    case PendingAssetCoding  = 'pending_asset_coding';
    case Published           = 'published';
    case Rejected            = 'rejected';
    case CorrectionRequired  = 'correction_required';

    public function label(): string
    {
        return match ($this) {
            self::Draft                => 'Draft',
            self::PendingVerification   => 'Pending Verification',
            self::Verified             => 'Verified',
            self::PendingAssetCoding   => 'Pending Asset Coding',
            self::Published            => 'Published',
            self::Rejected              => 'Rejected',
            self::CorrectionRequired   => 'Correction Required',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Submitted                       => 'gray',
            self::PendingVerification, self::PendingAssetCoding => 'amber',
            self::Verified                                      => 'blue',
            self::Published                                     => 'green',
            self::Rejected                                      => 'red',
            self::CorrectionRequired                            => 'orange',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Published, self::Rejected], true);
    }
}
