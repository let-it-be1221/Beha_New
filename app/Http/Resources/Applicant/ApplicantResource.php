<?php

namespace App\Http\Resources\Applicant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $showConfidential = $user && $user->can('users.view_confidential', $this);

        return [
            'id'                  => $this->id,
            'application_code'    => $this->application_code,
            'full_name'           => $this->full_name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'status'              => $this->status?->value,
            'status_label'        => $this->status?->label(),
            'assigned_generation' => $this->assignedGeneration?->only(['id', 'name', 'generation_number']),
            'assigned_branch'     => $this->assignedBranch?->only(['id', 'name', 'branch_number']),
            'assigned_team'       => $this->assignedTeam?->only(['id', 'name', 'team_number']),
            'approved_at'         => $this->approved_at?->toIso8601String(),
            'rejected_at'         => $this->rejected_at?->toIso8601String(),
            'rejection_reason'    => $this->rejection_reason,
            'official_id'         => $showConfidential ? $this->generated_official_id : null,
            // Confidential ID is intentionally OMITTED — even for authorized callers,
            // it must be fetched via a dedicated endpoint with explicit policy check.
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
