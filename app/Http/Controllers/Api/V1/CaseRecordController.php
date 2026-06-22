<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\CaseClosed;
use App\Events\CaseCreated;
use App\Events\CaseEscalated;
use App\Events\CaseSignoffRequired;
use App\Events\CaseStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\CaseLink;
use App\Models\CaseRecord;
use App\Models\CaseSignoff;
use App\Models\CaseStatusHistory;
use App\Models\Comment;
use App\Services\SlaService;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CaseRecordController extends Controller
{
    public function __construct(
        protected SlaService $slaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CaseRecord::class);

        $query = CaseRecord::query()
            ->with(['owner', 'primaryContact', 'primaryAccount', 'slaInstance.slaDefinition', 'closureReport']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('case_number', 'like', "%{$request->search}%")
                    ->orWhere('title', 'like', "%{$request->search}%")
                    ->orWhereHas('primaryContact', fn ($contact) => $contact->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$request->search}%"]))
                    ->orWhereHas('primaryAccount', fn ($account) => $account->where('name', 'like', "%{$request->search}%"));
            });
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->owner_id))
            ->when($request->filled('account_id'), fn ($q) => $q->where('primary_account_id', $request->account_id))
            ->when($request->filled('contact_id'), fn ($q) => $q->where('primary_contact_id', $request->contact_id))
            ->when($request->boolean('pending_signoff', false), fn ($q) => $q->pendingSignoff());

        return response()->json($query->latest()->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CaseRecord::class);

        $validated = $request->validate([
            'case_number' => 'nullable|string|max:100|unique:case_records,case_number',
            'title' => 'required|string|max:255',
            'type' => 'nullable|in:service_delivery,complaint,compliance,dispute,investigation,escalation,custom',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:new,triaged,in_progress,pending_customer,pending_internal,resolution_proposed,closed,reopened',
            'owner_id' => 'nullable|exists:users,id',
            'primary_contact_id' => 'nullable|exists:contacts,id',
            'primary_account_id' => 'nullable|exists:accounts,id',
            'sla_instance_id' => 'nullable|exists:sla_instances,id',
            'root_cause' => 'nullable|string',
            'resolution_details' => 'nullable|string',
            'closure_summary' => 'nullable|string',
            'signoff_required' => 'sometimes|boolean',
            'signoff_due_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $caseRecord = CaseRecord::create([
            ...$validated,
            'case_number' => $validated['case_number'] ?? $this->generateCaseNumber(),
        ]);

        $this->slaService->assignSlaToCase($caseRecord);

        CaseStatusHistory::create([
            'case_record_id' => $caseRecord->id,
            'to_status' => $caseRecord->status,
            'transitioned_by_id' => $request->user()?->id,
        ]);

        CaseCreated::dispatch($caseRecord);

        if ($caseRecord->signoff_required) {
            CaseSignoffRequired::dispatch($caseRecord);
        }

        return response()->json($caseRecord->load(['owner', 'primaryContact', 'primaryAccount', 'slaInstance.slaDefinition']), 201);
    }

    public function show(CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('view', $caseRecord);

        return response()->json($caseRecord->load([
            'owner',
            'primaryContact',
            'primaryAccount',
            'slaInstance.slaDefinition',
            'closureReport.preparedBy',
            'signoffApprover',
            'links.linkable',
            'statusHistory.transitionedBy',
            'signoffs.requestedBy',
            'signoffs.approvedBy',
            'signoffs.rejectedBy',
        ]));
    }

    public function updateStatus(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('update', $caseRecord);

        $validated = $request->validate([
            'status' => 'required|in:new,triaged,in_progress,pending_customer,pending_internal,resolution_proposed,closed,reopened',
            'reason' => 'nullable|string',
        ]);

        try {
            $previousStatus = $caseRecord->status;
            $caseRecord->changeStatus($validated['status'], $request->user(), $validated['reason'] ?? null);
            CaseStatusChanged::dispatch($caseRecord, $previousStatus, $validated['status']);

            if ($validated['status'] === CaseRecord::STATUS_CLOSED) {
                CaseClosed::dispatch($caseRecord);
            }
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json($caseRecord->fresh()->load(['owner', 'primaryContact', 'primaryAccount', 'slaInstance.slaDefinition']));
    }

    public function addNote(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('update', $caseRecord);

        $validated = $request->validate([
            'body' => 'required|string',
            'mentions' => 'sometimes|array',
            'mentions.*' => 'exists:users,id',
        ]);

        $comment = Comment::create([
            'commentable_type' => CaseRecord::class,
            'commentable_id' => $caseRecord->id,
            'user_id' => $request->user()?->id,
            'body' => $validated['body'],
        ]);

        foreach ($validated['mentions'] ?? [] as $userId) {
            $comment->mentions()->create(['user_id' => $userId]);
        }

        return response()->json($comment->load('mentions.user'), 201);
    }

    public function addLink(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('update', $caseRecord);

        $validated = $request->validate([
            'linkable_type' => 'required|string|max:255',
            'linkable_id' => 'required|ulid',
            'link_type' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        $link = $caseRecord->links()->create($validated);

        return response()->json($link->load('linkable'), 201);
    }

    public function removeLink(CaseRecord $caseRecord, CaseLink $caseLink): JsonResponse
    {
        $this->authorize('update', $caseRecord);

        if ($caseLink->case_record_id !== $caseRecord->id) {
            abort(404);
        }

        $caseLink->delete();

        return response()->json(['message' => 'Case link removed.']);
    }

    public function escalate(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('update', $caseRecord);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];
        $current = $priorityOrder[$caseRecord->priority] ?? 2;
        $nextPriority = array_search(min(4, $current + 1), $priorityOrder, true);

        $caseRecord->update([
            'priority' => $nextPriority,
            'metadata' => array_merge($caseRecord->metadata ?? [], [
                'escalated_at' => now()->toIso8601String(),
                'escalated_by_id' => $request->user()?->id,
                'escalation_reason' => $validated['reason'],
            ]),
        ]);

        CaseEscalated::dispatch($caseRecord, $request->user(), $validated['reason']);

        return response()->json($caseRecord->fresh());
    }

    public function close(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('close', $caseRecord);

        $validated = $request->validate([
            'closure_summary' => 'nullable|string',
            'resolution_details' => 'nullable|string',
            'root_cause' => 'nullable|string',
        ]);

        if ($validated) {
            $caseRecord->update($validated);
        }

        $previousStatus = $caseRecord->status;

        try {
            $caseRecord->changeStatus(CaseRecord::STATUS_CLOSED, $request->user(), $validated['closure_summary'] ?? null);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        CaseStatusChanged::dispatch($caseRecord, $previousStatus, CaseRecord::STATUS_CLOSED);
        CaseClosed::dispatch($caseRecord);

        return response()->json($caseRecord->fresh());
    }

    public function reopen(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('reopen', $caseRecord);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $previousStatus = $caseRecord->status;
        $caseRecord->reopened_at = now();
        $caseRecord->status = CaseRecord::STATUS_REOPENED;
        $caseRecord->metadata = array_merge($caseRecord->metadata ?? [], [
            'reopened_at' => now()->toIso8601String(),
            'reopened_by_id' => $request->user()?->id,
            'reopen_reason' => $validated['reason'] ?? null,
        ]);
        $caseRecord->save();

        CaseStatusChanged::dispatch($caseRecord, $previousStatus, CaseRecord::STATUS_REOPENED);

        return response()->json($caseRecord->fresh());
    }

    public function requestSignoff(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('signoff', $caseRecord);

        $validated = $request->validate([
            'reason' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);

        $signoff = CaseSignoff::create([
            'case_record_id' => $caseRecord->id,
            'requested_by_id' => $request->user()?->id,
            'status' => 'pending',
            'reason' => $validated['reason'] ?? null,
            'requested_at' => now(),
        ]);

        $caseRecord->update([
            'signoff_required' => true,
            'signoff_status' => 'pending',
            'signoff_due_at' => $validated['due_at'] ?? null,
        ]);

        CaseSignoffRequired::dispatch($caseRecord);

        return response()->json($signoff, 201);
    }

    public function approveSignoff(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('signoff', $caseRecord);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $signoff = $caseRecord->signoffs()->where('status', 'pending')->latest()->firstOrFail();
        $signoff->update([
            'status' => 'approved',
            'approved_by_id' => $request->user()?->id,
            'reason' => $validated['reason'] ?? null,
            'decided_at' => now(),
        ]);

        $caseRecord->update([
            'signoff_status' => 'approved',
            'signoff_approved_by_id' => $request->user()?->id,
            'signoff_approved_at' => now(),
        ]);

        return response()->json($signoff->fresh());
    }

    public function rejectSignoff(Request $request, CaseRecord $caseRecord): JsonResponse
    {
        $this->authorize('signoff', $caseRecord);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $signoff = $caseRecord->signoffs()->where('status', 'pending')->latest()->firstOrFail();
        $signoff->update([
            'status' => 'rejected',
            'rejected_by_id' => $request->user()?->id,
            'reason' => $validated['reason'],
            'decided_at' => now(),
        ]);

        $caseRecord->update([
            'signoff_status' => 'rejected',
        ]);

        return response()->json($signoff->fresh());
    }

    private function generateCaseNumber(): string
    {
        return 'CASE-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }
}
