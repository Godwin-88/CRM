<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = CampaignTemplate::with(['creator', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($templates);
    }

    public function show(CampaignTemplate $template): JsonResponse
    {
        return response()->json($template->load(['creator', 'reviewer']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string',
            'html_content' => 'nullable|string',
            'raw_html' => 'nullable|string',
            'status' => 'sometimes|in:draft,in_review,approved,published,archived',
            'blocks' => 'nullable|array',
        ]);

        $template = CampaignTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($template->load(['creator']), 201);
    }

    public function update(Request $request, CampaignTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string',
            'html_content' => 'sometimes|string',
            'raw_html' => 'sometimes|string',
            'status' => 'sometimes|in:draft,in_review,approved,published,archived',
            'blocks' => 'sometimes|array',
        ]);

        $template->update($validated);

        return response()->json($template->load(['creator', 'reviewer']));
    }

    public function destroy(CampaignTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }

    public function submitForReview(CampaignTemplate $template): JsonResponse
    {
        $template->update(['status' => 'in_review']);

        return response()->json($template);
    }

    public function approve(Request $request, CampaignTemplate $template): JsonResponse
    {
        $template->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json($template->load('reviewer'));
    }
}
