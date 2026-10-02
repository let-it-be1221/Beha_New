<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreBranchRequest;
use App\Http\Requests\Organization\StoreGenerationRequest;
use App\Http\Requests\Organization\StoreTeamRequest;
use App\Http\Requests\Organization\UpdateBranchRequest;
use App\Http\Requests\Organization\UpdateGenerationRequest;
use App\Http\Requests\Organization\UpdateTeamRequest;
use App\Models\Branch;
use App\Models\Generation;
use App\Models\Team;
use App\Services\Organization\OrganizationCapacityService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * OrganizationController — full CRUD for generations / branches / teams.
 *
 * Spec §4, §16, §30.
 *
 * Capacity validation is delegated to OrganizationCapacityService.
 * Authorization is enforced via Policy (role-based — sys_admin + executive
 * can manage generations; branch_leader can manage their branch's teams).
 */
class OrganizationController extends Controller
{
    public function __construct(private OrganizationCapacityService $capacity) {}

    // ═════════════════════════════════════════════════════════════════════
    //  GENERATIONS
    // ═════════════════════════════════════════════════════════════════════

    public function generations(Request $request)
    {
        $this->authorize('viewAny', Generation::class);
        return response()->json(Generation::with(['leader', 'branches'])->paginate(20));
    }

    public function generation(Request $request, $id)
    {
        $this->authorize('viewAny', Generation::class);
        $gen = Generation::with(['leader', 'branches.teams'])->findOrFail($id);
        return response()->json($gen);
    }

    public function storeGeneration(StoreGenerationRequest $request)
    {
        $this->authorize('create', Generation::class);
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $gen = Generation::create($data);
        $gen->load('leader');
        return response()->json($gen, 201);
    }

    public function updateGeneration(UpdateGenerationRequest $request, $id)
    {
        $this->authorize('update', Generation::class);
        $gen = Generation::findOrFail($id);
        $gen->update($request->validated());
        $gen->load('leader');
        return response()->json($gen->fresh());
    }

    public function destroyGeneration(Request $request, $id)
    {
        $this->authorize('delete', Generation::class);
        $gen = Generation::findOrFail($id);
        if ($gen->branches()->exists()) {
            throw ValidationException::withMessages([
                'generation' => 'Cannot delete a generation that has branches. Reassign or delete branches first.',
            ]);
        }
        $gen->delete();
        return response()->json(['message' => 'Generation deleted.']);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  BRANCHES
    // ═════════════════════════════════════════════════════════════════════

    public function branches(Request $request)
    {
        $this->authorize('viewAny', Branch::class);
        return response()->json(Branch::with(['generation', 'leader', 'teams'])->paginate(20));
    }

    public function branch(Request $request, $id)
    {
        $this->authorize('viewAny', Branch::class);
        return response()->json(Branch::with(['generation', 'leader', 'teams.members'])->findOrFail($id));
    }

    public function storeBranch(StoreBranchRequest $request)
    {
        $this->authorize('create', Branch::class);
        $gen = Generation::findOrFail($request->input('generation_id'));
        $this->capacity->ensureGenerationCanAcceptBranch($gen);

        // Branch number uniqueness within generation
        $existing = Branch::where('generation_id', $gen->id)
            ->where('branch_number', $request->input('branch_number'))
            ->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'branch_number' => "Branch number {$request->input('branch_number')} already exists in this generation.",
            ]);
        }

        $branch = Branch::create(array_merge($request->validated(), ['is_active' => $request->boolean('is_active', true)]));
        $branch->load(['generation', 'leader']);
        return response()->json($branch, 201);
    }

    public function updateBranch(UpdateBranchRequest $request, $id)
    {
        $this->authorize('update', Branch::class);
        $branch = Branch::findOrFail($id);
        $branch->update($request->validated());
        $branch->load(['generation', 'leader']);
        return response()->json($branch->fresh());
    }

    public function destroyBranch(Request $request, $id)
    {
        $this->authorize('delete', Branch::class);
        $branch = Branch::findOrFail($id);
        if ($branch->teams()->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'Cannot delete a branch that has teams. Reassign or delete teams first.',
            ]);
        }
        $branch->delete();
        return response()->json(['message' => 'Branch deleted.']);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  TEAMS
    // ═════════════════════════════════════════════════════════════════════

    public function teams(Request $request)
    {
        $this->authorize('viewAny', Team::class);
        return response()->json(Team::with(['branch.generation', 'leader', 'members'])->paginate(20));
    }

    public function team(Request $request, $id)
    {
        $this->authorize('viewAny', Team::class);
        return response()->json(Team::with(['branch.generation', 'leader', 'members.user'])->findOrFail($id));
    }

    public function storeTeam(StoreTeamRequest $request)
    {
        $this->authorize('create', Team::class);
        $branch = Branch::findOrFail($request->input('branch_id'));
        $this->capacity->ensureBranchCanAcceptTeam($branch);

        $existing = Team::where('branch_id', $branch->id)
            ->where('team_number', $request->input('team_number'))
            ->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'team_number' => "Team number {$request->input('team_number')} already exists in this branch.",
            ]);
        }

        $team = Team::create(array_merge($request->validated(), ['is_active' => $request->boolean('is_active', true)]));
        $team->load(['branch.generation', 'leader', 'members']);
        return response()->json($team, 201);
    }

    public function updateTeam(UpdateTeamRequest $request, $id)
    {
        $this->authorize('update', Team::class);
        $team = Team::findOrFail($id);
        $team->update($request->validated());
        $team->load(['branch.generation', 'leader', 'members']);
        return response()->json($team->fresh());
    }

    public function destroyTeam(Request $request, $id)
    {
        $this->authorize('delete', Team::class);
        $team = Team::findOrFail($id);
        if ($team->members()->exists()) {
            throw ValidationException::withMessages([
                'team' => 'Cannot delete a team that has members. Reassign members first.',
            ]);
        }
        $team->delete();
        return response()->json(['message' => 'Team deleted.']);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ORG TREE + CAPACITY
    // ═════════════════════════════════════════════════════════════════════

    public function tree(Request $request)
    {
        $this->authorize('viewAny', Generation::class);
        $generations = Generation::with(['branches.teams.members.user'])->active()->get();
        return response()->json(['generations' => $generations]);
    }

    public function capacity(Request $request)
    {
        $this->authorize('viewAny', Generation::class);
        return response()->json($this->capacity->snapshot(
            generationId: $request->integer('generation_id') ?: null,
            branchId: $request->integer('branch_id') ?: null,
            teamId: $request->integer('team_id') ?: null,
        ));
    }
}
