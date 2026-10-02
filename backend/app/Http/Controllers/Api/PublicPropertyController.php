<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Property\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;

/**
 * PublicPropertyController — public property portal via API.
 * Spec §31 — only published properties, no internal confidential info.
 */
class PublicPropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::published()
            ->with(['images' => fn($q) => $q->orderBy('is_primary', 'desc')])
            ->when($request->input('city'), fn($q, $city) => $q->where('city', $city))
            ->when($request->input('type'),  fn($q, $type)  => $q->where('property_type', $type))
            ->when($request->filled('min_price'), fn($q) => $q->where('price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn($q) => $q->where('price', '<=', $request->input('max_price')))
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return PropertyResource::collection($properties);
    }

    public function show(Property $property)
    {
        abort_unless($property->is_published, 404);
        $property->load(['images', 'documents' => fn($q) => $q->where('document_type', 'public_brochure')]);
        return new PropertyResource($property);
    }
}
