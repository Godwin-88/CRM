<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SurveyWebController extends Controller
{
    public function index(): Response
    {
        $surveys = Survey::orderBy('created_at', 'desc')->get();
        $segments = \App\Models\Segment::orderBy('name')->get(['id', 'name']);
        $contacts = \App\Models\Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return Inertia::render('Admin/Surveys', [
            'surveys' => $surveys,
            'segments' => $segments,
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:nps,csat,ces,custom',
            'status' => 'required|in:draft,active,closed',
            'segment_id' => 'nullable|exists:segments,id',
            'question_text' => 'required|string|max:2000',
            'follow_up_question' => 'nullable|string|max:2000',
            'channel' => 'nullable|string|max:100',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'trigger_event' => 'nullable|string|max:100',
            'sent_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
        ]);

        $data = $request->only([
            'name', 'type', 'status', 'segment_id', 'question_text', 'follow_up_question',
            'channel', 'trigger_event', 'sent_at', 'closed_at',
        ]);
        $data['contact_ids'] = $request->input('contact_ids', []);
        $data['created_by'] = auth()->id();
        $data['type'] = strtolower($data['type']);
        $data['status'] = strtolower($data['status']);

        Survey::create($data);

        return redirect()->route('admin.surveys.index')->with('success', 'Survey created successfully.');
    }

    public function update(Request $request, Survey $survey)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:nps,csat,ces,custom',
            'status' => 'required|in:draft,active,closed',
            'segment_id' => 'nullable|exists:segments,id',
            'question_text' => 'required|string|max:2000',
            'follow_up_question' => 'nullable|string|max:2000',
            'channel' => 'nullable|string|max:100',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'trigger_event' => 'nullable|string|max:100',
            'sent_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
        ]);

        $data = $request->only([
            'name', 'type', 'status', 'segment_id', 'question_text', 'follow_up_question',
            'channel', 'trigger_event', 'sent_at', 'closed_at',
        ]);
        if ($request->has('contact_ids')) {
            $data['contact_ids'] = $request->input('contact_ids', []);
        }
        $data['type'] = strtolower($data['type']);
        $data['status'] = strtolower($data['status']);

        $survey->update($data);

        return redirect()->route('admin.surveys.index')->with('success', 'Survey updated successfully.');
    }

    public function responses(): Response
    {
        $responses = SurveyResponse::with(['survey', 'contact'])->orderBy('created_at', 'desc')->limit(500)->get();

        return Inertia::render('Admin/SurveyResponses', [
            'responses' => $responses,
        ]);
    }
}
