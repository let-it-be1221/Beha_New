<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — API serialization for users.
 *
 * CRITICAL (spec §6 + §41 rule #3): confidential_id is OMITTED entirely
 * (not nulled) unless the requesting user passes UserPolicy::viewConfidential.
 * Even then, it should be served from a dedicated endpoint with a strong
 * policy check — not embedded in list responses.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $showConfidential = $viewer
            ? app(\App\Policies\UserPolicy::class)->viewConfidential($viewer, $this->resource)
            : false;

        return [
            'id'                    => $this->id,
            'official_id'           => $this->official_id,
            'username'              => $this->username,
            'email'                 => $this->email,
            'is_active'             => (bool) $this->is_active,
            'level'                 => $this->level,
            'roles'                 => $this->roles->pluck('name'),
            'permissions'           => $this->getAllPermissions()->pluck('name'),
            'last_login_at'         => $this->last_login_at?->toIso8601String(),
            'email_verified_at'     => $this->email_verified_at?->toIso8601String(),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),

            // Confidential ID — only included when policy explicitly allows.
            // For audit safety, prefer NOT returning it from list endpoints.
            'confidential_id' => $showConfidential ? $this->confidential_id : null,
        ];
    }
}
