<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CustomerResource — API serialization.
 * Confidential fields are NEVER included unless the requesting user
 * passes UserPolicy::viewConfidential (spec §6, §41 rule #3).
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference_code' => $this->reference_code,
            'full_name'      => $this->full_name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'status'         => $this->status?->value,
            'status_label'   => $this->status?->label(),
            'customer_source'=> $this->customer_source,
            'sales_agent'    => [
                'id'           => $this->salesAgent?->id,
                'official_id'  => $this->salesAgent?->official_id,
                'username'     => $this->salesAgent?->username,
            ],
            'team_id'        => $this->team_id,
            'branch_id'      => $this->branch_id,
            'generation_id'  => $this->generation_id,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
