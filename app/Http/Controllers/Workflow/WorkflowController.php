<?php

namespace App\Http\Controllers\Workflow;

use App\Enums\WorkflowAction;
use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', WorkflowInstance::class);

        $instances = WorkflowInstance::query()
            ->visibleTo($request->user())
            ->with(['subject', 'currentOwner', 'createdBy'])
            ->whereNull('finalized_at')
            ->latest()
            ->paginate(20);

        return view('workflows.index', compact('instances'));
    }

    public function show(WorkflowInstance $instance)
    {
        $this->authorize('view', $instance);
        $instance->load(['steps', 'actions', 'comments', 'attachments', 'history']);
        return view('workflows.show', compact('instance'));
    }

    public function advance(Request $request, WorkflowInstance $instance)
    {
        $this->authorize('advance', $instance);

        $data = $request->validate([
            'action'   => ['required', 'string'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        $action = WorkflowAction::from($data['action']);
        $instance = app(\App\Services\Workflow\WorkflowEngine::class)
            ->advance($instance, $request->user(), $action, $data['comment'] ?? null);

        return redirect()->route('workflows.show', $instance)
            ->with('success', 'Workflow advanced.');
    }

    public function reject(Request $request, WorkflowInstance $instance)
    {
        $this->authorize('reject', $instance);

        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        $instance = app(\App\Services\Workflow\WorkflowEngine::class)
            ->reject($instance, $request->user(), $data['reason']);

        return redirect()->route('workflows.show', $instance)
            ->with('warning', 'Workflow rejected.');
    }
}
