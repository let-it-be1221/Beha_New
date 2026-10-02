<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->visibleTo($request->user())
            ->with(['salesAgent', 'team', 'branch', 'generation'])
            ->latest()
            ->paginate(20);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $this->authorize('create', Customer::class);
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = \DB::transaction(function () use ($request) {
            $user = $request->user();
            $team = $user->teamMember?->team;

            $customer = Customer::create(array_merge($request->validated(), [
                'status'               => \App\Enums\CustomerStatus::Draft,
                'sales_agent_user_id' => $user->id,
                'team_id'             => $team?->id,
                'branch_id'           => $team?->branch_id,
                'generation_id'       => $team?->branch?->generation_id,
            ]));

            $customer->startWorkflow('customer_registration', creatorId: $user->id);

            return $customer;
        });

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer registered and routed to Team Leader for evaluation.');
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['salesAgent', 'documents', 'reviews', 'team', 'branch', 'generation']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        return view('customers.edit', compact('customer'));
    }
}
