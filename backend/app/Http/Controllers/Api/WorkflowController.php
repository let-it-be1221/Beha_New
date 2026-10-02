<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkflowAction;
use App\Http\Controllers\Controller;
use App\Services\Workflow\WorkflowEngine;
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
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->whereNull('finalized_at')
            ->latest()
            ->paginate(20);

        return response()->json($instances);
    }

    public function show(Request $request, WorkflowInstance $instance)
    {
        $this->authorize('view', $instance);
        $instance->load(['steps', 'actions', 'comments', 'attachments', 'history']);
        return response()->json($instance);
    }

    public function advance(Request $request, WorkflowInstance $instance, WorkflowEngine $engine)
    {
        $this->authorize('advance', $instance);

        $data = $request->validate([
            'action'   => ['required', 'string'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        $action = WorkflowAction::from($data['action']);
        $instance = $engine->advance($instance, $request->user(), $action, $data['comment'] ?? null);

        return response()->json([
            'message'  => 'Workflow advanced.',
            'instance' => $instance->load(['steps', 'actions', 'currentOwner']),
        ]);
    }

    public function reject(Request $request, WorkflowInstance $instance, WorkflowEngine $engine)
    {
        $this->authorize('reject', $instance);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $instance = $engine->reject($instance, $request->user(), $data['reason']);
        return response()->json(['message' => 'Workflow rejected.', 'instance' => $instance->fresh()]);
    }

    public function comment(Request $request, WorkflowInstance $instance)
    {
        $this->authorize('view', $instance);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $comment = $instance->comments()->create([
            'author_user_id' => $request->user()->id,
            'body'           => $data['body'],
        ]);
        return response()->json($comment, 201);
    }
}
