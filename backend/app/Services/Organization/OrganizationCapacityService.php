<?php

namespace App\Services\Organization;

use App\Models\Branch;
use App\Models\Generation;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Validation\ValidationException;

/**
 * OrganizationCapacityService — enforces the 10-person hierarchical structure.
 *
 * Spec §4 + §16 — capacity limits are configurable via `beha.capacity.*`
 * (defaults: 10 members per team, 10 teams per branch, 10 branches per generation).
 *
 * All checks throw ValidationException with a translatable message so the
 * frontend can render them as form errors.
 */
class OrganizationCapacityService
{
    public function teamMembersPerTeam(): int
    {
        return (int) config('beha.capacity.team_members_per_team', 10);
    }

    public function teamsPerBranch(): int
    {
        return (int) config('beha.capacity.teams_per_branch', 10);
    }

    public function branchesPerGeneration(): int
    {
        return (int) config('beha.capacity.branches_per_generation', 10);
    }

    /**
     * @throws ValidationException when adding would exceed team capacity.
     */
    public function ensureTeamCanAcceptMember(Team $team): void
    {
        $current = $team->members()->active()->count();
        $max = $this->teamMembersPerTeam();
        if ($current >= $max) {
            throw ValidationException::withMessages([
                'team_id' => __("Team :name is at full capacity (:current/:max members).", [
                    'name'    => $team->name,
                    'current' => $current,
                    'max'     => $max,
                ]),
            ]);
        }
    }

    public function ensureBranchCanAcceptTeam(Branch $branch): void
    {
        $current = $branch->teams()->active()->count();
        $max = $this->teamsPerBranch();
        if ($current >= $max) {
            throw ValidationException::withMessages([
                'branch_id' => __("Branch :name is at full capacity (:current/:max teams).", [
                    'name'    => $branch->name,
                    'current' => $current,
                    'max'     => $max,
                ]),
            ]);
        }
    }

    public function ensureGenerationCanAcceptBranch(Generation $generation): void
    {
        $current = $generation->branches()->active()->count();
        $max = $this->branchesPerGeneration();
        if ($current >= $max) {
            throw ValidationException::withMessages([
                'generation_id' => __("Generation :name is at full capacity (:current/:max branches).", [
                    'name'    => $generation->name,
                    'current' => $current,
                    'max'     => $max,
                ]),
            ]);
        }
    }

    /**
     * Capacity snapshot for a generation / branch / team — used by the UI
     * to show "8/10 members" style indicators.
     */
    public function snapshot(?int $generationId = null, ?int $branchId = null, ?int $teamId = null): array
    {
        $result = [
            'limits' => [
                'team_members_per_team'        => $this->teamMembersPerTeam(),
                'teams_per_branch'              => $this->teamsPerBranch(),
                'branches_per_generation'      => $this->branchesPerGeneration(),
            ],
        ];

        if ($teamId) {
            $team = Team::withCount(['members as active_members_count' => fn($q) => $q->active()])->find($teamId);
            if ($team) {
                $result['team'] = [
                    'id'        => $team->id,
                    'name'      => $team->name,
                    'members'   => $team->active_members_count,
                    'capacity'  => $this->teamMembersPerTeam(),
                    'is_full'   => $team->active_members_count >= $this->teamMembersPerTeam(),
                ];
            }
        }

        if ($branchId) {
            $branch = Branch::withCount(['teams as active_teams_count' => fn($q) => $q->active()])->find($branchId);
            if ($branch) {
                $result['branch'] = [
                    'id'        => $branch->id,
                    'name'      => $branch->name,
                    'teams'     => $branch->active_teams_count,
                    'capacity'  => $this->teamsPerBranch(),
                    'is_full'   => $branch->active_teams_count >= $this->teamsPerBranch(),
                ];
            }
        }

        if ($generationId) {
            $gen = Generation::withCount(['branches as active_branches_count' => fn($q) => $q->active()])->find($generationId);
            if ($gen) {
                $result['generation'] = [
                    'id'        => $gen->id,
                    'name'      => $gen->name,
                    'branches'  => $gen->active_branches_count,
                    'capacity'  => $this->branchesPerGeneration(),
                    'is_full'   => $gen->active_branches_count >= $this->branchesPerGeneration(),
                ];
            }
        }

        return $result;
    }
}
