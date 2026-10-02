<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'asset_code'     => $this->asset_code,
            'name'           => $this->name,
            'property_type'  => $this->property_type,
            'city'           => $this->city,
            'region'         => $this->region,
            'country'        => $this->country,
            'price'          => (float) $this->price,
            'bedrooms'       => $this->bedrooms,
            'bathrooms'      => $this->bathrooms,
            'size_sqm'       => $this->size_sqm ? (float) $this->size_sqm : null,
            'status'         => $this->status?->value,
            'status_label'   => $this->status?->label(),
            'is_published'   => (bool) $this->is_published,
            'published_at'   => $this->published_at?->toIso8601String(),
            'primary_image'  => $this->images->firstWhere('is_primary', true)?->file_path,
            'amenities'      => $this->amenities,
            'registered_by'  => $this->registeredBy?->only(['id', 'official_id', 'username']),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
