<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialPostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = SocialPost::with(['campaign'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'nullable|exists:campaigns,id',
            'channel' => 'required|in:linkedin,x,facebook',
            'content' => 'required|string',
            'media_url' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'channel_specific_data' => 'nullable|array',
        ]);

        // Validate character limits
        $maxChars = match ($validated['channel']) {
            'linkedin' => 3000,
            'x' => 280,
            'facebook' => 63206,
            default => 280,
        };

        if (strlen($validated['content']) > $maxChars) {
            return response()->json([
                'message' => "Content exceeds {$maxChars} character limit for {$validated['channel']}",
            ], 422);
        }

        $post = SocialPost::create($validated);

        return response()->json($post->load(['campaign']), 201);
    }

    public function update(Request $request, SocialPost $socialPost): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'sometimes|string',
            'media_url' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'status' => 'sometimes|in:draft,scheduled,published,failed',
        ]);

        $socialPost->update($validated);

        return response()->json($socialPost->load(['campaign']));
    }

    public function destroy(SocialPost $socialPost): JsonResponse
    {
        $socialPost->delete();
        return response()->json(null, 204);
    }
}
