<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DripSequence;
use App\Models\DripSequenceStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DripSequenceController extends Controller
{
    public function index(): JsonResponse
    {
        $sequences = DripSequence::with(['creator', 'steps.emailTemplate', 'steps.segment', 'steps.agent'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($sequences);
    }

    public function show(DripSequence $sequence): JsonResponse
    {
        $sequence->load(['creator', 'steps.emailTemplate', 'steps.segment', 'steps.agent', 'enrolments.contact']);

        return response()->json($sequence);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger' => 'required|in:contact_created,contact_stage_changed,deal_stage_changed,contact_field_changed,form_submission,manual_enrolment',
            'trigger_conditions' => 'nullable|array',
            'allow_re_enrolment' => 'boolean',
            'status' => 'required|in:draft,active,inactive',
        ]);

        $sequence = DripSequence::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($sequence->load(['creator']), 201);
    }

    public function update(Request $request, DripSequence $sequence): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'trigger_conditions' => 'sometimes|array',
            'allow_re_enrolment' => 'sometimes|boolean',
        ]);

        $sequence->update($validated);

        return response()->json($sequence->load(['creator']));
    }

    public function destroy(DripSequence $sequence): JsonResponse
    {
        $sequence->delete();

        return response()->json(null, 204);
    }

    public function addStep(Request $request, DripSequence $sequence): JsonResponse
    {
        $validated = $request->validate([
            'action_type' => 'required|in:send_email,send_sms,send_in_app,create_activity,update_contact_field,add_to_segment,remove_from_segment,notify_agent',
            'email_template_id' => 'nullable|exists:campaign_templates,id',
            'delay_type' => 'required|in:immediate,n_hours,n_days',
            'delay_value' => 'integer|min:0',
            'sms_content' => 'nullable|string',
            'in_app_title' => 'nullable|string',
            'in_app_content' => 'nullable|string',
            'activity_type' => 'nullable|string',
            'field_key' => 'nullable|string',
            'field_value' => 'nullable|string',
            'segment_id' => 'nullable|exists:segments,id',
            'agent_id' => 'nullable|exists:users,id',
            'exit_conditions' => 'nullable|array',
        ]);

        $position = $sequence->steps()->max('position') ?? 0;

        $step = DripSequenceStep::create([
            ...$validated,
            'drip_sequence_id' => $sequence->id,
            'position' => $position + 1,
        ]);

        return response()->json($step->load(['emailTemplate', 'segment', 'agent']));
    }
}
