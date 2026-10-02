<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Evaluation::class);

        $evaluations = Evaluation::query()
            ->with(['template', 'teamMember', 'subject', 'evaluator', 'scores.criterion'])
            ->when($request->input('subject_user_id'), fn($q, $id) => $q->where('subject_user_id', $id))
            ->when($request->input('decision'), fn($q, $d) => $q->where('decision', $d))
            ->latest()
            ->paginate(20);

        return response()->json($evaluations);
    }

    public function show(Request $request, Evaluation $evaluation)
    {
        $this->authorize('view', $evaluation);
        $evaluation->load(['template', 'teamMember', 'subject', 'evaluator', 'scores.criterion']);
        return response()->json($evaluation);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Evaluation::class);
        $data = $request->validate([
            'template_id'              => ['required', 'exists:evaluation_templates,id'],
            'team_member_id'           => ['required', 'exists:team_members,id'],
            'subject_user_id'          => ['required', 'exists:users,id'],
            'evaluation_period_start' => ['required', 'date'],
            'evaluation_period_end'   => ['required', 'date', 'after_or_equal:evaluation_period_start'],
            'scores'                   => ['required', 'array'],
            'scores.*.criterion_id'    => ['required', 'exists:evaluation_criteria,id'],
            'scores.*.score'           => ['required', 'numeric'],
        ]);

        $evaluation = \DB::transaction(function () use ($data, $request) {
            $evaluation = Evaluation::create([
                'template_id'              => $data['template_id'],
                'team_member_id'           => $data['team_member_id'],
                'subject_user_id'          => $data['subject_user_id'],
                'evaluator_user_id'       => $request->user()->id,
                'evaluation_period_start' => $data['evaluation_period_start'],
                'evaluation_period_end'   => $data['evaluation_period_end'],
                'decision'                => 'pending',
            ]);
            foreach ($data['scores'] as $score) {
                $evaluation->scores()->create($score);
            }
            return $evaluation;
        });

        return response()->json($evaluation->load(['scores.criterion']), 201);
    }

    public function destroy(Request $request, Evaluation $evaluation)
    {
        $this->authorize('delete', $evaluation);
        $evaluation->delete();
        return response()->json(['message' => 'Evaluation deleted.']);
    }
}
