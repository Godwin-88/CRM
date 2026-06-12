<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function __construct(protected SegmentService $segmentService) {}

    /**
     * List all segments.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Segment::class);

        return response()->json(
            Segment::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Create a new segment.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Segment::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:demographic,psychographic,behavioral,geographic',
            'criteria' => 'required|array',
            'criteria.rules' => 'required|array|min:1',
            'criteria.join_operator' => 'sometimes|in:and,or',
        ]);

        $segment = Segment::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'criteria' => $validated['criteria'],
            'join_operator' => $validated['criteria']['join_operator'] ?? 'and',
            'contact_count' => 0,
        ]);

        // Calculate initial count
        $this->segmentService->refreshCount($segment);

        return response()->json($segment->fresh(), 201);
    }

    /**
     * Get a single segment.
     */
    public function show(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);

        return response()->json($segment);
    }

    /**
     * Update a segment.
     */
    public function update(Request $request, Segment $segment): JsonResponse
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:demographic,psychographic,behavioral,geographic',
            'criteria' => 'sometimes|array',
            'criteria.rules' => 'required_with:criteria|array|min:1',
            'criteria.join_operator' => 'sometimes|in:and,or',
        ]);

        $segment->update($validated);

        // Refresh count
        $this->segmentService->refreshCount($segment);

        return response()->json($segment->fresh());
    }

    /**
     * Delete a segment.
     */
    public function destroy(Segment $segment): JsonResponse
    {
        $this->authorize('delete', $segment);
        $segment->delete();
        return response()->json(null, 204);
    }

    /**
     * Preview contacts matching segment criteria.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Segment::class);

        $request->validate([
            'criteria' => 'required|array',
            'criteria.rules' => 'required|array|min:1',
            'criteria.join_operator' => 'sometimes|in:and,or',
        ]);

        $preview = $this->segmentService->getPreview($request->criteria);

        return response()->json($preview);
    }

    /**
     * Preview for an existing segment.
     */
    public function previewSegment(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);

        $criteria = $segment->criteria ?: ['rules' => []];
        $preview = $this->segmentService->getPreview($criteria);

        return response()->json($preview);
    }
}