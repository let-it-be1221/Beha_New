<?php

namespace App\Enums;

/**
 * User level enum — vertical sales hierarchy (spec §3).
 */
enum UserLevel: int
{
    case TeamMember        = 1;
    case SeniorTeamMember  = 2;
    case TeamLeader        = 3;
    case BranchLeader      = 4;
    case GenerationLeader  = 5;

    public function label(): string
    {
        return match ($this) {
            self::TeamMember       => 'Team Member',
            self::SeniorTeamMember  => 'Senior Team Member',
            self::TeamLeader        => 'Team Leader',
            self::BranchLeader      => 'Branch Leader',
            self::GenerationLeader  => 'Generation Leader',
        };
    }

    public function isTeamMemberRange(): bool
    {
        return in_array($this, [self::TeamMember, self::SeniorTeamMember], true);
    }

    public function next(): ?self
    {
        return match ($this) {
            self::TeamMember       => self::SeniorTeamMember,
            self::SeniorTeamMember => self::TeamLeader,
            self::TeamLeader        => self::BranchLeader,
            self::BranchLeader      => self::GenerationLeader,
            self::GenerationLeader  => null,
        };
    }
}
