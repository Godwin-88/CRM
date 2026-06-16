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

    public function variables(): JsonResponse
    {
        return response()->json([
            ['key' => '{{contact.first_name}}', 'label' => 'Contact First Name'],
            ['key' => '{{contact.last_name}}', 'label' => 'Contact Last Name'],
            ['key' => '{{contact.email}}', 'label' => 'Contact Email'],
            ['key' => '{{account.name}}', 'label' => 'Account Name'],
            ['key' => '{{agent.name}}', 'label' => 'Agent Name'],
            ['key' => '{{unsubscribe_link}}', 'label' => 'Unsubscribe Link'],
        ]);
    }

    public function duplicate(CampaignTemplate $template): JsonResponse
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->status = 'draft';
        $newTemplate->version = 1;
        $newTemplate->is_active = true;
        $newTemplate->created_by = request()->user()->id;
        $newTemplate->reviewed_by = null;
        $newTemplate->reviewed_at = null;
        $newTemplate->save();

        return response()->json($newTemplate->load(['creator']), 201);
    }

    public function publish(CampaignTemplate $template): JsonResponse
    {
        $template->update(['status' => 'published', 'is_active' => true]);

        CampaignTemplate::where('id', '!=', $template->id)
            ->where('name', $template->name)
            ->update(['is_active' => false, 'status' => 'archived']);

        return response()->json($template);
    }

    public function archive(CampaignTemplate $template): JsonResponse
    {
        $template->update(['status' => 'archived', 'is_active' => false]);

        return response()->json($template);
    }

    public function restoreVersion(Request $request, CampaignTemplate $template): JsonResponse
    {
        $versionId = $request->validate(['version_id' => 'required|string'])['version_id'];

        $newTemplate = $template->replicate();
        $newTemplate->version = ($template->version ?? 1) + 1;
        $newTemplate->status = 'draft';
        $newTemplate->created_by = request()->user()->id;
        $newTemplate->save();

        return response()->json($newTemplate->load(['creator']), 201);
    }
}
