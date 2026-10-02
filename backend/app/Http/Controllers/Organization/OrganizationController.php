<?php

namespace App\Http\Controllers\Organization;

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
        $generations = Generation::with(['leader', 'branches'])->paginate(20);
        return view('organization.generations', compact('generations'));
    }

    public function branches(Request $request)
    {
        $this->authorize('viewAny', Branch::class);
        $branches = Branch::with(['generation', 'leader', 'teams'])->paginate(20);
        return view('organization.branches', compact('branches'));
    }

    public function teams(Request $request)
    {
        $this->authorize('viewAny', Team::class);
        $teams = Team::with(['branch.generation', 'leader', 'members'])->paginate(20);
        return view('organization.teams', compact('teams'));
    }

    public function hierarchy(Request $request)
    {
        $this->authorize('viewAny', Generation::class);
        $generations = Generation::with(['branches.teams.members.user'])->get();
        return view('organization.hierarchy', compact('generations'));
    }
}
