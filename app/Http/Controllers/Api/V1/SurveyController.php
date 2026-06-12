<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Survey::query()->with(['segment', 'creator']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Survey::class);

        $validated = $request->validate([
            'segment_id' => 'nullable|exists:segments,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:nps,csat',
            'question_text' => 'required|string',
            'follow_up_question' => 'nullable|string',
            'channel' => 'required|in:email,sms',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $validated['created_by'] = auth()->id();

        $survey = Survey::create($validated);

        return response()->json($survey->load(['segment', 'creator']), 201);
    }

    public function show(Survey $survey): JsonResponse
    {
        return response()->json($survey->load(['segment', 'creator', 'responses.contact']));
    }

    public function update(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('update', $survey);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'question_text' => 'sometimes|string',
            'follow_up_question' => 'nullable|string',
            'status' => 'sometimes|in:draft,active,paused,completed',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $survey->update($validated);

        return response()->json($survey);
    }

    public function destroy(Survey $survey): JsonResponse
    {
        $this->authorize('delete', $survey);
        $survey->delete();

        return response()->json(null, 204);
    }

    public function send(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('send', $survey);

        if ($survey->status !== 'active') {
            return response()->json(['message' => 'Survey must be active to send.'], 422);
        }

        $recipients = $this->getRecipients($survey);

        if ($survey->channel === 'email') {
            foreach ($recipients as $contact) {
                \App\Jobs\SendSurveyInvitation::dispatch($survey, $contact);
            }
        } else {
            foreach ($recipients as $contact) {
                \App\Jobs\SendSurveySms::dispatch($survey, $contact);
            }
        }

        $survey->update(['sent_at' => now()]);

        return response()->json(['message' => "Survey sent to {$recipients->count()} contacts."]);
    }

    public function respond(Request $request, $surveyId): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'score' => 'required|integer',
            'open_text_answer' => 'nullable|string|max:5000',
            'channel' => 'required|in:email,sms,portal',
        ]);

        $survey = Survey::findOrFail($surveyId);

        if ($survey->status !== 'active' && $survey->status !== 'completed') {
            return response()->json(['message' => 'Survey not accepting responses.'], 422);
        }

        $existingResponse = SurveyResponse::where('survey_id', $surveyId)
            ->where('contact_id', $validated['contact_id'])
            ->first();

        if ($existingResponse) {
            return response()->json(['message' => 'Contact has already responded to this survey.'], 422);
        }

        $npsCategory = null;
        if ($survey->type === 'nps') {
            if ($validated['score'] >= 9) {
                $npsCategory = 'promoter';
            } elseif ($validated['score'] >= 7) {
                $npsCategory = 'passive';
            } else {
                $npsCategory = 'detractor';
            }
        }

        $response = SurveyResponse::create([
            'survey_id' => $surveyId,
            'contact_id' => $validated['contact_id'],
            'score' => $validated['score'],
            'open_text_answer' => $validated['open_text_answer'],
            'channel' => $validated['channel'],
            'nps_category' => $npsCategory,
            'responded_at' => now(),
        ]);

        activity()
            ->performedOn(\App\Models\Contact::findOrFail($validated['contact_id']))
            ->causedBy(auth()->user() ?? \App\Models\User::find(1))
            ->withProperties(['survey_id' => $surveyId, 'score' => $validated['score']])
            ->event('survey_responded')
            ->log('Contact responded to survey');

        return response()->json($response->load('contact'), 201);
    }

    // Survey public landing page response (no auth required)
    public function publicRespond(Request $request, $surveyId, $token): JsonResponse
    {
        $survey = Survey::findOrFail($surveyId);

        if ($survey->status !== 'active' && $survey->status !== 'completed') {
            return response()->json(['message' => 'Survey not accepting responses.'], 422);
        }

        $contactId = decrypt($token);
        $contact = \App\Models\Contact::findOrFail($contactId);

        $existingResponse = SurveyResponse::where('survey_id', $surveyId)
            ->where('contact_id', $contactId)
            ->first();

        if ($existingResponse) {
            return response()->json(['message' => 'You have already responded to this survey.', 'already_responded' => true]);
        }

        $validated = $request->validate([
            'score' => 'required|integer',
            'open_text_answer' => 'nullable|string|max:5000',
        ]);

        $npsCategory = null;
        if ($survey->type === 'nps') {
            if ($validated['score'] >= 9) {
                $npsCategory = 'promoter';
            } elseif ($validated['score'] >= 7) {
                $npsCategory = 'passive';
            } else {
                $npsCategory = 'detractor';
            }
        }

        $response = SurveyResponse::create([
            'survey_id' => $surveyId,
            'contact_id' => $contactId,
            'score' => $validated['score'],
            'open_text_answer' => $validated['open_text_answer'],
            'channel' => 'portal',
            'nps_category' => $npsCategory,
            'responded_at' => now(),
        ]);

        return response()->json(['message' => 'Thank you for your response.', 'success' => true]);
    }

    public function analytics(Survey $survey): JsonResponse
    {
        $responses = $survey->responses()->get();
        $totalResponses = $responses->count();

        if ($totalResponses === 0) {
            return response()->json([
                'total_responses' => 0,
                'average_score' => 0,
                'score_breakdown' => [],
                'response_rate' => 0,
                'open_text_responses' => [],
            ]);
        }

        $averageScore = round($responses->avg('score'), 2);

        $scoreBreakdown = [];
        for ($i = 0; $i <= 10; $i++) {
            $scoreBreakdown[$i] = $responses->where('score', $i)->count();
        }

        $npsScore = null;
        if ($survey->type === 'nps') {
            $promoters = $responses->where('nps_category', 'promoter')->count();
            $detractors = $responses->where('nps_category', 'detractor')->count();
            $npsScore = round((($promoters - $detractors) / $totalResponses) * 100, 2);
        }

        $csatScore = null;
        if ($survey->type === 'csat') {
            $satisfied = $responses->whereIn('score', [4, 5])->count();
            $csatScore = round(($satisfied / $totalResponses) * 100, 2);
        }

        $openTextResponses = $responses->whereNotNull('open_text_answer')
            ->map(fn($r) => [
                'contact_name' => $r->contact->full_name,
                'response' => $r->open_text_answer,
                'responded_at' => $r->responded_at,
                'score' => $r->score,
            ])
            ->values();

        return response()->json([
            'total_responses' => $totalResponses,
            'average_score' => $averageScore,
            'nps_score' => $npsScore,
            'csat_score' => $csatScore,
            'score_breakdown' => $scoreBreakdown,
            'open_text_responses' => $openTextResponses,
        ]);
    }

    private function getRecipients(Survey $survey)
    {
        if ($survey->contact_ids && count($survey->contact_ids) > 0) {
            return \App\Models\Contact::whereIn('id', $survey->contact_ids)->get();
        }

        if ($survey->segment_id) {
            $segment = $survey->segment;
            if ($segment && method_exists($segment, 'getContacts')) {
                return $segment->getContacts()->get();
            }
        }

        return \App\Models\Contact::all();
    }
}
