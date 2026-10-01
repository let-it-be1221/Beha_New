<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Applicant::class);

        $applicants = Applicant::query()
            ->visibleTo($request->user())
            ->with(['assignedGeneration', 'assignedBranch', 'assignedTeam'])
            ->latest()
            ->paginate(20);

        return view('applicants.index', compact('applicants'));
    }

    public function show(Applicant $applicant)
    {
        $this->authorize('view', $applicant);
        $applicant->load(['documents', 'reviews', 'assignments', 'assignedBranch', 'assignedTeam']);
        return view('applicants.show', compact('applicant'));
    }
}
