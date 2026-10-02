<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Generation;
use App\Models\Team;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
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

    /**
     * Interactive org tree — used by frontend OrgHierarchy component (spec §30).
     */
    public function tree(Request $request)
    {
        $this->authorize('viewAny', Generation::class);
        $generations = Generation::with(['branches.teams.members.user'])->active()->get();
        return response()->json(['generations' => $generations]);
    }
}
