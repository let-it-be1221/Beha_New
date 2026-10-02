<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PublicPropertyController extends Controller
{
    /**
     * Spec §31 — public property portal.
     * Only published properties are visible to the public.
     */
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

        return view('public.properties.index', compact('properties'));
    }

    public function show(Property $property)
    {
        abort_unless($property->is_published, 404);

        $property->load(['images', 'documents' => fn($q) => $q->where('document_type', 'public_brochure')]);
        return view('public.properties.show', compact('property'));
    }
}
