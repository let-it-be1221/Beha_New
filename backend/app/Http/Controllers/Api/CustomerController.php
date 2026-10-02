<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Enums\CustomerStatus;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->visibleTo($request->user())
            ->with(['salesAgent', 'team', 'branch', 'generation'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('reference_code', 'like', "%{$search}%")
                      ->orWhere('full_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = \DB::transaction(function () use ($request) {
            $user = $request->user();
            $team = $user->teamMember?->team;

            $customer = Customer::create(array_merge($request->validated(), [
                'status'               => CustomerStatus::Draft,
                'sales_agent_user_id' => $user->id,
                'team_id'             => $team?->id,
                'branch_id'           => $team?->branch_id,
                'generation_id'       => $team?->branch?->generation_id,
            ]));

            $customer->startWorkflow('customer_registration', creatorId: $user->id);

            return $customer;
        });

        return new CustomerResource($customer->load(['salesAgent', 'team', 'branch', 'generation']));
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['salesAgent', 'documents', 'reviews', 'team', 'branch', 'generation']);
        return new CustomerResource($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $customer->update($request->validate([
            'full_name'       => ['sometimes', 'string', 'max:191'],
            'email'           => ['sometimes', 'nullable', 'email:rfc,dns', 'max:191'],
            'phone'           => ['sometimes', 'string', 'max:32'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]));
        return new CustomerResource($customer->fresh());
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();
        return response()->json(['message' => 'Customer deactivated.']);
    }

    public function evaluate(Request $request, Customer $customer)
    {
        $this->authorize('evaluate', $customer);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,correction_required'],
            'comment'   => ['nullable', 'string', 'max:2000'],
        ]);
        $customer->reviews()->create([
            'reviewer_user_id' => $request->user()->id,
            'review_stage'     => 'team_leader',
            'decision'         => $data['decision'],
            'comment'          => $data['comment'] ?? null,
        ]);
        return new CustomerResource($customer->fresh());
    }

    public function approve(Request $request, Customer $customer)
    {
        $this->authorize('approve', $customer);
        $customer->update(['status' => CustomerStatus::Registered]);
        return new CustomerResource($customer->fresh());
    }

    public function reject(Request $request, Customer $customer)
    {
        $this->authorize('reject', $customer);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $customer->update(['status' => CustomerStatus::Rejected, 'notes' => $data['reason']]);
        return new CustomerResource($customer->fresh());
    }
}
