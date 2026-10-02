<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\Property\PropertyResource;
use App\Models\Property;
use App\Services\ID\AssetCodeGenerator;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $properties = Property::query()
            ->visibleTo($request->user())
            ->with(['registeredBy', 'images'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('asset_code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return PropertyResource::collection($properties);
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

        return new PropertyResource($property->load(['registeredBy', 'images']));
    }

    public function show(Request $request, Property $property)
    {
        $this->authorize('view', $property);
        $property->load(['registeredBy', 'images', 'documents', 'verifications', 'asset', 'publication']);
        return new PropertyResource($property);
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);
        $property->update($request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]));
        return new PropertyResource($property->fresh());
    }

    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);
        $property->delete();
        return response()->json(['message' => 'Property deleted.']);
    }

    public function verify(Request $request, Property $property)
    {
        $this->authorize('verify', $property);
        $data = $request->validate([
            'decision' => ['required', 'in:verify,reject,correction_required'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);
        $property->verifications()->create([
            'verifier_user_id' => $request->user()->id,
            'decision'         => $data['decision'],
            'comment'          => $data['comment'] ?? null,
        ]);
        if ($data['decision'] === 'verify') {
            $property->update(['status' => \App\Enums\PropertyStatus::Verified]);
        }
        return new PropertyResource($property->fresh());
    }

    public function assignAssetCode(Request $request, Property $property, AssetCodeGenerator $generator)
    {
        $this->authorize('assignAssetCode', $property);

        $assetCode = \DB::transaction(function () use ($property, $request, $generator) {
            $code = $generator->next(now()->year, $property->id, $request->user()->id);

            \App\Models\PropertyAsset::create([
                'property_id' => $property->id,
                'asset_code'  => $code,
                'assigned_by' => $request->user()->id,
            ]);

            $property->update([
                'asset_code' => $code,
                'status'     => \App\Enums\PropertyStatus::PendingAssetCoding,
            ]);

            return $code;
        });

        return response()->json([
            'message'    => 'Asset code assigned.',
            'asset_code' => $assetCode,
            'property'   => new PropertyResource($property->fresh()),
        ]);
    }

    public function publish(Request $request, Property $property)
    {
        $this->authorize('publish', $property);
        abort_if(!$property->asset_code, 422, 'Property must have an asset code before publication (spec §41 rule #7).');

        \DB::transaction(function () use ($property, $request) {
            \App\Models\PropertyPublication::create([
                'property_id'  => $property->id,
                'published_by' => $request->user()->id,
            ]);
            $property->update([
                'is_published' => true,
                'published_at' => now(),
                'status'       => \App\Enums\PropertyStatus::Published,
            ]);
        });

        return new PropertyResource($property->fresh());
    }
}
