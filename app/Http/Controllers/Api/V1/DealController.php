<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Activity;
use App\Models\DealComment;
use App\Models\DealCommentMention;
use App\Models\DemoTrial;
use App\Models\WinLossReason;
use App\Events\DealStageMoved;
use App\Events\NewDealComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::query()
            ->with(['account', 'contact', 'owner', 'pipeline', 'winLossReason'])
            ->latest();

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->pipeline_id);
        }
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }
        if ($request->filled('expected_close_from')) {
            $query->whereDate('expected_close_date', '>=', $request->expected_close_from);
        }
        if ($request->filled('expected_close_to')) {
            $query->whereDate('expected_close_date', '<=', $request->expected_close_to);
        }
        if ($request->filled('value_min')) {
            $query->where('value', '>=', $request->value_min);
        }
        if ($request->filled('value_max')) {
            $query->where('value', '<=', $request->value_max);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Deal::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'pipeline_id' => 'nullable|exists:pipelines,id',
            'stage' => 'nullable|string',
            'value' => 'numeric|min:0',
            'currency' => 'string|max:3|default:USD',
            'expected_close_date' => 'nullable|date',
            'owner_id' => 'required|exists:users,id',
        ]);

        DB::transaction(function () use (&$deal, $validated) {
            $defaultPipeline = Pipeline::where('is_default', true)->first();
            $validated['pipeline_id'] = $validated['pipeline_id'] ?? $defaultPipeline?->id;

            $stage = $validated['stage'] ?? $validated['pipeline_id'] 
                ? PipelineStage::where('pipeline_id', $validated['pipeline_id'])->orderBy('position')->first()?->name 
                : null;
            $validated['stage'] = $stage;

            $deal = Deal::create($validated);

            activity()
                ->performedOn($deal)
                ->causedBy(auth()->user())
                ->withProperties(['new_values' => $validated])
                ->event('created')
                ->log('Deal created');
        });

        return response()->json($deal->load(['account', 'contact', 'owner', 'pipeline']), 201);
    }

    public function show(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);

        $deal->load([
            'account',
            'contact',
            'owner',
            'pipeline.stages',
            'winLossReason',
            'activities' => function ($q) { $q->latest(); },
            'quotes' => function ($q) { $q->latest(); },
            'demoTrials' => function ($q) { $q->latest(); },
            'comments' => function ($q) { $q->latest()->with('user', 'mentions'); },
        ]);

        $deal->unread_comments_count = DealCommentMention::whereHas('comment', function ($q) use ($deal) {
            $q->where('deal_id', $deal->id);
        })->where('user_id', auth()->id())->whereNull('read_at')->count();

        return response()->json($deal);
    }

    public function update(Request $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'stage' => 'sometimes|string',
            'value' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:3',
            'expected_close_date' => 'nullable|date',
            'owner_id' => 'sometimes|exists:users,id',
            'exclude_from_automations' => 'sometimes|boolean',
        ]);

        $oldStage = $deal->stage;
        $deal->update($validated);

        if (isset($validated['stage']) && $validated['stage'] !== $oldStage) {
            DealStageMoved::dispatch($deal, $oldStage, $validated['stage']);
        }

        return response()->json($deal->fresh()->load(['account', 'contact', 'owner', 'pipeline']));
    }

    public function moveStage(Request $request, Deal $deal): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|string',
        ]);

        $pipeline = $deal->pipeline;
        $stage = PipelineStage::where('pipeline_id', $pipeline->id)
            ->where('name', $validated['stage'])
            ->firstOrFail();

        $oldStage = $deal->stage;
        $deal->update([
            'stage' => $stage->name,
            'probability' => $stage->probability,
        ]);

        DealStageMoved::dispatch($deal, $oldStage, $stage->name);

        return response()->json(['message' => 'Deal moved successfully.', 'deal' => $deal->fresh()]);
    }

    public function addActivity(Request $request, Deal $deal): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'type' => 'required|in:call,email,meeting,task',
            'due_at' => 'nullable|datetime',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $activity = $deal->activities()->create($validated);

        activity()
            ->performedOn($deal)
            ->causedBy(auth()->user())
            ->withProperties(['activity_id' => $activity->id, 'subject' => $validated['subject']])
            ->event('activity_added')
            ->log('Activity added to deal');

        return response()->json($activity, 201);
    }

    public function addComment(Request $request, Deal $deal): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'mentions' => 'nullable|array',
            'mentions.*' => 'exists:users,id',
        ]);

        DB::transaction(function () use (&$comment, $deal, $validated) {
            $comment = $deal->comments()->create([
                'user_id' => auth()->id(),
                'body' => $validated['body'],
            ]);

            foreach ($validated['mentions'] ?? [] as $userId) {
                DealCommentMention::create([
                    'deal_comment_id' => $comment->id,
                    'user_id' => $userId,
                ]);

                NewDealComment::dispatch($comment, $userId);
            }

            activity()
                ->performedOn($deal)
                ->causedBy(auth()->user())
                ->withProperties(['comment_id' => $comment->id])
                ->event('comment_added')
                ->log('Comment added to deal');
        });

        return response()->json($comment->load(['user', 'mentions']), 201);
    }

    public function scheduleDemoTrial(Request $request, Deal $deal): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:demo,trial',
            'scheduled_date' => 'required|date',
            'start_date' => 'nullable|date|required_if:type,trial',
            'end_date' => 'nullable|date|required_if:type,trial|after:start_date',
            'scope_notes' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $demoTrial = $deal->demoTrials()->create($validated);

        activity()
            ->performedOn($deal)
            ->causedBy(auth()->user())
            ->withProperties(['demo_trial_id' => $demoTrial->id, 'type' => $validated['type']])
            ->event('demo_scheduled')
            ->log('Demo/Trial scheduled');

        return response()->json($demoTrial, 201);
    }

    public function closeDeal(Request $request, Deal $deal): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:won,lost',
            'win_loss_reason_id' => 'required|exists:win_loss_reasons,id',
            'note' => 'nullable|string',
        ]);

        $reason = WinLossReason::findOrFail($validated['win_loss_reason_id']);
        abort_unless($reason->type === $validated['status'], 422, 'Reason type must match close status');

        $deal->update([
            'stage' => 'closed_' . $validated['status'],
            'win_loss_reason_id' => $validated['win_loss_reason_id'],
            'win_loss_note' => $validated['note'],
            'probability' => $validated['status'] === 'won' ? 100 : 0,
        ]);

        DealStageMoved::dispatch($deal, $deal->getOriginal('stage'), 'closed_' . $validated['status']);

        return response()->json($deal->fresh());
    }
}