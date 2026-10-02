<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\StoreApplicantRequest;
use App\Http\Resources\Applicant\ApplicantResource;
use App\Models\Applicant;
use App\Enums\ApplicantStatus;
use App\Services\ID\ConfidentialIdGenerator;
use App\Services\ID\IdSequenceService;
use App\Services\ID\OfficialIdGenerator;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Applicant::class);

        $applicants = Applicant::query()
            ->visibleTo($request->user())
            ->with(['assignedGeneration', 'assignedBranch', 'assignedTeam'])
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return ApplicantResource::collection($applicants);
    }

    public function apply(StoreApplicantRequest $request, IdSequenceService $sequences)
    {
        $applicant = \DB::transaction(function () use ($request, $sequences) {
            $year = now()->year;
            $key  = "app:{$year}";
            $sequences->ensureExists($key, 'APP', 6);
            $seqValue = $sequences->next($key);
            $code = "APP-{$year}-" . str_pad((string) $seqValue, 6, '0', STR_PAD_LEFT);

            return Applicant::create(array_merge($request->validated(), [
                'application_code'    => $code,
                'status'              => ApplicantStatus::Submitted,
                'terms_accepted_at'   => now(),
            ]));
        });

        return response()->json([
            'message'    => 'Application submitted. A Team Leader will be in touch shortly.',
            'applicant' => new ApplicantResource($applicant),
        ], 201);
    }

    public function show(Request $request, Applicant $applicant)
    {
        $this->authorize('view', $applicant);
        $applicant->load(['documents', 'reviews', 'assignments', 'assignedBranch', 'assignedTeam']);
        return new ApplicantResource($applicant);
    }

    public function screen(Request $request, Applicant $applicant)
    {
        $this->authorize('screen', $applicant);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,correction_required'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant->reviews()->create([
            'reviewer_user_id' => $request->user()->id,
            'review_stage'     => 'team_screening',
            'decision'         => $data['decision'],
            'comment'          => $data['comment'] ?? null,
        ]);

        $applicant->update([
            'reviewed_by_team_leader_id' => $request->user()->id,
            'status'                     => $data['decision'] === 'approve'
                ? ApplicantStatus::BranchAssignment
                : ($data['decision'] === 'reject'
                    ? ApplicantStatus::Rejected
                    : ApplicantStatus::TeamLeaderScreening),
        ]);

        return new ApplicantResource($applicant->fresh());
    }

    public function assign(Request $request, Applicant $applicant)
    {
        $this->authorize('assignBranch', $applicant);
        $data = $request->validate([
            'generation_id' => ['required', 'exists:generations,id'],
            'branch_id'      => ['required', 'exists:branches,id'],
            'team_id'        => ['required', 'exists:teams,id'],
        ]);

        \DB::transaction(function () use ($applicant, $data, $request) {
            $applicant->update([
                'assigned_branch_leader_id' => $request->user()->id,
                'assigned_generation_id'    => $data['generation_id'],
                'assigned_branch_id'        => $data['branch_id'],
                'assigned_team_id'          => $data['team_id'],
                'status'                    => ApplicantStatus::RecordVerification,
            ]);
            $applicant->assignments()->create([
                'assigner_user_id' => $request->user()->id,
                'generation_id'   => $data['generation_id'],
                'branch_id'       => $data['branch_id'],
                'team_id'         => $data['team_id'],
            ]);
        });

        return new ApplicantResource($applicant->fresh());
    }

    public function generateIds(
        Request $request,
        Applicant $applicant,
        OfficialIdGenerator $officialGenerator,
        ConfidentialIdGenerator $confidentialGenerator,
    ) {
        $this->authorize('generateIds', $applicant);

        $year = $applicant->created_at->year ?? now()->year;
        $officialId = $officialGenerator->forApplicant($applicant->id);

        $confidentialId = $confidentialGenerator->forApplicant(
            applicantId: $applicant->id,
            year: $year,
            parts: [
                'generation' => $applicant->assigned_generation_id ?? 1,
                'branch'      => $applicant->assignedBranch?->branch_number ?? 1,
                'team'        => $applicant->assignedTeam?->team_number ?? 1,
                'rank'        => 1, // default rank; recalculated by Performance service later
            ],
        );

        $applicant->update([
            'generated_official_id'         => $officialId,
            'generated_confidential_id'     => encrypt($confidentialId), // store encrypted
            'status'                         => ApplicantStatus::AccountCreation,
            'record_officer_id'              => $request->user()->id,
        ]);

        return response()->json([
            'message'     => 'IDs generated. Awaiting account creation by System Administrator.',
            'official_id' => $officialId,
            'applicant'   => new ApplicantResource($applicant->fresh()),
        ]);
    }

    public function createAccount(Request $request, Applicant $applicant)
    {
        $this->authorize('createAccount', $applicant);
        abort_if(!$applicant->generated_official_id, 422, 'IDs must be generated first (spec §17).');

        $data = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'email'    => ['required', 'email:rfc,dns', 'max:191', 'unique:users,email'],
        ]);

        $tempPassword = \Illuminate\Support\Str::random(24);

        $user = \DB::transaction(function () use ($applicant, $data, $tempPassword) {
            $user = \App\Models\User::create([
                'official_id'            => $applicant->generated_official_id,
                'confidential_id'        => $applicant->generated_confidential_id, // already encrypted
                'username'               => $data['username'],
                'email'                  => $data['email'],
                'password'               => $tempPassword,
                'must_change_password'  => true,
                'is_active'              => true,
                'level'                  => 1, // start at Level 1 — Team Member
            ]);
            $user->assignRole('team_member');

            \App\Models\TeamMember::create([
                'user_id'   => $user->id,
                'team_id'   => $applicant->assigned_team_id,
                'level'     => 1,
                'joined_at' => now(),
            ]);

            $applicant->update([
                'status'       => ApplicantStatus::Approved,
                'approved_at'  => now(),
            ]);

            return $user;
        });

        return response()->json([
            'message'        => 'Account created. The applicant will receive credentials via email.',
            'temp_password'  => $tempPassword, // returned ONCE — must be transmitted to the user out-of-band
            'user'           => new \App\Http\Resources\UserResource($user),
        ], 201);
    }
}
