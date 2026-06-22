<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestCompleted;
use App\Events\ServiceRequestCreated;
use App\Events\ServiceRequestDocumentRequested;
use App\Events\ServiceRequestStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusHistory;
use App\Services\SlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ServiceRequestController extends Controller
{
    public function __construct(
        protected SlaService $slaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $query = ServiceRequest::query()
            ->with(['contact', 'account', 'assignee', 'team', 'catalogItem', 'catalogItemVersion', 'slaInstance.slaDefinition', 'formResponse.schemaVersion']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('contact', fn ($contact) => $contact->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$request->search}%"]))
                    ->orWhereHas('account', fn ($account) => $account->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('catalogItem', fn ($item) => $item->where('name', 'like', "%{$request->search}%"));
            });
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('catalog_item_id'), fn ($q) => $q->where('catalog_item_id', $request->catalog_item_id))
            ->when($request->filled('team_id'), fn ($q) => $q->where('team_id', $request->team_id))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->assigned_to))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->account_id))
            ->when($request->filled('contact_id'), fn ($q) => $q->where('contact_id', $request->contact_id))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('sla') && $request->sla === 'breached', fn ($q) => $q->whereHas('slaInstance', fn ($sla) => $sla->where(function ($conditions) {
                $conditions->where('first_response_breached', true)
                    ->orWhere('resolution_breached', true)
                    ->orWhere('acknowledgement_breached', true)
                    ->orWhere('review_breached', true)
                    ->orWhere('next_action_breached', true)
                    ->orWhere('completion_breached', true);
            })));

        return response()->json($query->latest()->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ServiceRequest::class);

        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:service_catalog_items,id',
            'requester_id' => 'required|exists:users,id',
            'contact_id' => 'required|exists:contacts,id',
            'account_id' => 'nullable|exists:accounts,id',
            'channel' => 'required|string|max:50',
            'source_identifier' => 'nullable|string|max:255|unique:service_requests,source_identifier',
            'status' => 'sometimes|in:submitted,under_review,in_progress,pending_customer,completed,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
            'form_response' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $catalogItem = ServiceCatalogItem::findOrFail($validated['catalog_item_id']);
        if (! $this->catalogItemAllowsChannel($catalogItem, $validated['channel'])) {
            abort(422, 'Catalog item is not visible for this channel.');
        }

        if (! $catalogItem->is_active) {
            abort(422, 'Catalog item is not active.');
        }

        $version = $catalogItem->intakeFormSchema?->latestVersion;
        if ($request->has('form_response')) {
            if (! $version) {
                abort(422, 'Catalog item has no published intake form schema.');
            }

            $this->validateFormResponse($request->input('form_response', []), $version->fields);
        }

        $serviceRequest = ServiceRequest::create([
            ...$validated,
            'catalog_item_version_id' => $version?->id,
            'priority' => $validated['priority'] ?? $catalogItem->default_priority,
            'team_id' => $validated['team_id'] ?? $catalogItem->default_team_id,
            'form_response_id' => null,
        ]);

        if ($request->has('form_response')) {
            $formResponse = FormResponse::create([
                'form_schema_version_id' => $version->id,
                'formable_type' => ServiceRequest::class,
                'formable_id' => $serviceRequest->id,
                'submitted_by_id' => $validated['requester_id'],
                'response_data' => $request->input('form_response'),
                'field_snapshots' => $version->fields,
            ]);

            $serviceRequest->form_response_id = $formResponse->id;
            $serviceRequest->save();
        }

        $this->slaService->assignSlaToServiceRequest($serviceRequest);

        ServiceRequestStatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'to_status' => $serviceRequest->status,
            'transitioned_by_id' => $request->user()?->id,
        ]);

        ServiceRequestCreated::dispatch($serviceRequest);

        return response()->json($serviceRequest->load(['contact', 'account', 'assignee', 'team', 'catalogItem', 'catalogItemVersion', 'formResponse.schemaVersion', 'slaInstance.slaDefinition']), 201);
    }

    public function show(ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);

        return response()->json($serviceRequest->load([
            'contact',
            'account',
            'requester',
            'assignee',
            'team',
            'catalogItem',
            'catalogItemVersion',
            'slaInstance.slaDefinition',
            'formResponse.schemaVersion',
            'caseRecord',
            'statusHistory.transitionedBy',
            'documentRequests.requestedBy',
            'relatedRequests',
        ]));
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'status' => 'required|in:submitted,under_review,in_progress,pending_customer,completed,closed',
            'reason' => 'nullable|string',
        ]);

        try {
            $previousStatus = $serviceRequest->status;
            $serviceRequest->changeStatus($validated['status'], $request->user(), $validated['reason'] ?? null);
            ServiceRequestStatusChanged::dispatch($serviceRequest, $previousStatus, $validated['status']);

            if ($validated['status'] === ServiceRequest::STATUS_COMPLETED) {
                ServiceRequestCompleted::dispatch($serviceRequest);
            }

            if ($validated['status'] === ServiceRequest::STATUS_CLOSED) {
                ServiceRequestClosed::dispatch($serviceRequest);
            }
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json($serviceRequest->fresh()->load(['contact', 'account', 'assignee', 'catalogItem', 'slaInstance.slaDefinition']));
    }

    public function assign(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $serviceRequest->update($validated);

        return response()->json($serviceRequest->fresh()->load('assignee', 'team'));
    }

    public function escalate(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];
        $current = $priorityOrder[$serviceRequest->priority] ?? 2;
        $nextPriority = array_search(min(4, $current + 1), $priorityOrder, true);

        $serviceRequest->update([
            'priority' => $nextPriority,
            'metadata' => array_merge($serviceRequest->metadata ?? [], [
                'escalated_at' => now()->toIso8601String(),
                'escalated_by_id' => $request->user()?->id,
                'escalation_reason' => $validated['reason'],
            ]),
        ]);

        return response()->json($serviceRequest->fresh());
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $previousStatus = $serviceRequest->status;
        $serviceRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => $validated['reason'],
        ]);

        ServiceRequestStatusChanged::dispatch($serviceRequest, $previousStatus, 'cancelled');

        return response()->json($serviceRequest->fresh());
    }

    public function reopen(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $previousStatus = $serviceRequest->status;
        $serviceRequest->reopened_at = now();
        $serviceRequest->status = ServiceRequest::STATUS_IN_PROGRESS;
        $serviceRequest->metadata = array_merge($serviceRequest->metadata ?? [], [
            'reopened_at' => now()->toIso8601String(),
            'reopened_by_id' => $request->user()?->id,
            'reopen_reason' => $validated['reason'] ?? null,
        ]);
        $serviceRequest->save();

        ServiceRequestStatusChanged::dispatch($serviceRequest, $previousStatus, $serviceRequest->status);

        return response()->json($serviceRequest->fresh());
    }

    public function complete(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'closure_reason' => 'required|string',
        ]);

        $previousStatus = $serviceRequest->status;
        $serviceRequest->changeStatus(ServiceRequest::STATUS_COMPLETED, $request->user(), $validated['closure_reason']);
        ServiceRequestStatusChanged::dispatch($serviceRequest, $previousStatus, ServiceRequest::STATUS_COMPLETED);
        ServiceRequestCompleted::dispatch($serviceRequest);

        return response()->json($serviceRequest->fresh());
    }

    public function close(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('close', $serviceRequest);

        $validated = $request->validate([
            'closure_reason' => 'required|string',
        ]);

        $previousStatus = $serviceRequest->status;
        $serviceRequest->closure_reason = $validated['closure_reason'];
        $serviceRequest->changeStatus(ServiceRequest::STATUS_CLOSED, $request->user(), $validated['closure_reason']);
        ServiceRequestStatusChanged::dispatch($serviceRequest, $previousStatus, ServiceRequest::STATUS_CLOSED);
        ServiceRequestClosed::dispatch($serviceRequest);

        return response()->json($serviceRequest->fresh());
    }

    public function addDocumentRequest(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'required_by' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $documentRequest = $serviceRequest->documentRequests()->create([
            ...$validated,
            'requested_by_id' => $request->user()?->id,
        ]);

        ServiceRequestDocumentRequested::dispatch($serviceRequest, $documentRequest);

        return response()->json($documentRequest, 201);
    }

    public function merge(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'linked_service_request_id' => 'required|exists:service_requests,id|different:'.$serviceRequest->id,
            'link_type' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
        ]);

        $serviceRequest->relatedRequests()->attach($validated['linked_service_request_id'], [
            'link_type' => $validated['link_type'] ?? 'duplicate_of',
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json(['message' => 'Service requests linked successfully.']);
    }

    private function catalogItemAllowsChannel(ServiceCatalogItem $catalogItem, string $channel): bool
    {
        if ($catalogItem->is_agent_only && ! in_array($channel, ['agent', 'api'])) {
            return false;
        }

        return match ($channel) {
            'portal', 'self_service_portal' => $catalogItem->portal_visible,
            'email' => $catalogItem->email_visible,
            'kiosk' => $catalogItem->kiosk_visible,
            'api' => $catalogItem->api_visible,
            'agent', 'phone', 'chat', 'ivr' => true,
            default => true,
        };
    }

    private function validateFormResponse(array $response, array $fields): void
    {
        foreach ($fields as $field) {
            $name = $field['name'] ?? $field['key'] ?? null;
            if (! $name || empty($field['required'])) {
                continue;
            }

            $value = $response[$name] ?? null;
            if ($value === null || $value === '') {
                abort(422, "Required field '{$name}' is missing.");
            }
        }
    }
}
