<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $properties = Property::query()
            ->visibleTo($request->user())
            ->with(['registeredBy', 'images'])
            ->latest()
            ->paginate(20);

        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        $this->authorize('create', Property::class);
        return view('properties.create');
    }

    public function store(StorePropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $property = \DB::transaction(function () use ($request) {
            $user = $request->user();

            $property = Property::create(array_merge($request->validated(), [
                'status'                => \App\Enums\PropertyStatus::Draft,
                'registered_by_user_id'=> $user->id,
                'generation_id'        => $user->teamMember?->team?->branch?->generation_id,
            ]));

            $property->startWorkflow('property_registration', creatorId: $user->id);

            return $property;
        });

        return redirect()->route('properties.show', $property)
            ->with('success', 'Property registered and routed to the Executive Officer for verification.');
    }

    public function show(Property $property)
    {
        $this->authorize('view', $property);
        $property->load(['registeredBy', 'images', 'documents', 'verifications', 'asset', 'publication']);
        return view('properties.show', compact('property'));
    }
}
