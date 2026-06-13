<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Jobs\GenerateContactExport;
use App\Jobs\ProcessContactImport;
use App\Models\Contact;
use App\Services\ContactService;
use App\Services\DuplicateDetectionService;
use App\Services\ScoringService;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected DuplicateDetectionService $duplicateDetectionService,
        protected TimelineService $timelineService,
        protected ScoringService $scoringService,
    ) {}

    /**
     * List contacts with filtering, pagination, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()->with(['owner', 'customFieldValues']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->first_name.'%');
        }
        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%'.$request->last_name.'%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('loyalty_tier')) {
            $query->where('loyalty_tier', $request->loyalty_tier);
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Create a new contact.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $request->input('custom_fields', []);

        try {
            $contact = $this->contactService->createContact($validated, $customFields);

            // Calculate initial score
            $this->scoringService->updateContactScore($contact);

            return response()->json($contact->load(['owner', 'customFieldValues']), 201);
        } catch (ValidationException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Get contact details.
     */
    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $contact->load([
            'owner',
            'accounts' => function ($q) {
                $q->withPivot('is_primary');
            },
            'customFieldValues',
            'interactions' => function ($q) {
                $q->latest()->limit(5);
            },
            'deals' => function ($q) {
                $q->latest()->limit(5);
            },
            'tickets' => function ($q) {
                $q->latest()->limit(5);
            },
            'contracts' => function ($q) {
                $q->latest()->limit(5);
            },
        ]);

        return response()->json($contact);
    }

    /**
     * Update a contact (only changed fields are saved).
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:contacts,email,'.$contact->id,
            'phone' => 'nullable|string|max:20',
            'type' => 'sometimes|in:lead,prospect,customer,partner',
            'status' => 'sometimes|in:active,inactive,churned,reactivated',
            'source' => 'nullable|string|max:50',
            'owner_id' => 'nullable|exists:users,id',
            'loyalty_tier' => 'nullable|in:bronze,silver,gold,platinum',
            'preferred_channel' => 'nullable|string|max:20',
            'custom_fields' => 'nullable|array',
        ]);

        $customFields = $request->input('custom_fields', []);
        $contact = $this->contactService->updateContact($contact, $validated, $customFields);

        // Recalculate score if data affecting scoring changed
        $this->scoringService->updateContactScore($contact);

        return response()->json($contact->load(['owner', 'customFieldValues']));
    }

    /**
     * Soft delete a contact.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);
        $this->contactService->deleteContact($contact);

        return response()->json(null, 204);
    }

    /**
     * Bulk delete contacts.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', Contact::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        $count = Contact::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => "{$count} contacts deleted."]);
    }

    /**
     * Get contact timeline (paginated, filterable).
     */
    public function timeline(string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $this->authorize('view', $contact);

        $filters = request()->only(['types']);
        if (request()->filled('types')) {
            $filters['types'] = explode(',', request->types);
        }

        $timeline = $this->timelineService->getTimeline($contact, $filters);

        return response()->json($timeline);
    }

    /**
     * Import contacts from file (async job).
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Contact::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $fieldMapping = $request->input('field_mapping', []);

        // Dispatch async import job
        ProcessContactImport::dispatch(
            $file->store('imports'),
            $fieldMapping,
            auth()->id()
        );

        return response()->json(['message' => 'Import started. You will be notified when it completes.'], 202);
    }

    /**
     * Export contacts as CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $filters = $request->only([
            'search', 'type', 'status', 'source', 'owner_id',
            'loyalty_tier', 'created_from', 'created_to',
        ]);
        $selectedFields = $request->input('fields', [
            'first_name', 'last_name', 'email', 'phone', 'type', 'status', 'source',
        ]);

        $recordCount = Contact::count();

        if ($recordCount > 500) {
            // Queue for large exports
            GenerateContactExport::dispatch(
                $filters,
                $selectedFields,
                auth()->id()
            );

            return response()->json([
                'message' => 'Export queued for large dataset. You will receive a notification when ready.',
            ], 202);
        }

        // Small export - generate and return immediately
        $exportService = app(ContactService::class);
        // Build a simple CSV response
        $contacts = Contact::where($filters)->get($selectedFields);
        $csv = $this->generateCsv($contacts, $selectedFields);

        return response()->json([
            'message' => 'Export ready',
            'data' => $csv,
            'filename' => 'contacts_export_'.now()->format('Ymd_His').'.csv',
        ]);
    }

    /**
     * Check for duplicate contacts before creating.
     */
    public function checkDuplicates(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'exclude_id' => 'nullable|string',
        ]);

        $data = $request->only(['email', 'first_name', 'last_name', 'phone']);
        if ($request->filled('exclude_id')) {
            $data['id'] = $request->exclude_id;
        }

        $duplicates = $this->duplicateDetectionService->findDuplicates($data);

        return response()->json([
            'has_duplicates' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates->map(fn ($c) => [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'type' => $c->type,
            ]),
        ]);
    }

    /**
     * Merge two contacts.
     */
    public function merge(Request $request): JsonResponse
    {
        $this->authorize('delete', Contact::class); // Merge requires delete permission (manager+)

        $validated = $request->validate([
            'surviving_id' => 'required|exists:contacts,id',
            'discarded_id' => 'required|exists:contacts,id|different:surviving_id',
            'field_selections' => 'required|array',
        ]);

        $contact = $this->contactService->mergeContacts(
            $validated['surviving_id'],
            $validated['discarded_id'],
            $validated['field_selections']
        );

        return response()->json([
            'message' => 'Contacts merged successfully.',
            'contact' => $contact->fresh()->load(['owner', 'customFieldValues']),
        ]);
    }

    /**
     * Get deals associated with a contact.
     */
    public function deals(string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $this->authorize('view', $contact);

        return response()->json($contact->deals()->with('owner')->paginate());
    }

    /**
     * Get tickets associated with a contact.
     */
    public function tickets(string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $this->authorize('view', $contact);

        return response()->json($contact->tickets()->with('assignee')->paginate());
    }

    /**
     * Link contact to an account.
     */
    public function linkAccount(Request $request, string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'is_primary' => 'boolean',
        ]);

        $contact->accounts()->syncWithoutDetaching([
            $validated['account_id'] => ['is_primary' => $validated['is_primary'] ?? false],
        ]);

        // If setting as primary, unset any other primary for this account
        if ($validated['is_primary'] ?? false) {
            \DB::table('contact_account')
                ->where('account_id', $validated['account_id'])
                ->where('contact_id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->withProperties(['account_id' => $validated['account_id']])
            ->event('linked')
            ->log('Contact linked to account');

        return response()->json(['message' => 'Contact linked to account successfully.']);
    }

    /**
     * Unlink contact from an account.
     */
    public function unlinkAccount(Request $request, string $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        $contact->accounts()->detach($validated['account_id']);

        activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->withProperties(['account_id' => $validated['account_id']])
            ->event('unlinked')
            ->log('Contact unlinked from account');

        return response()->json(['message' => 'Contact unlinked from account.']);
    }

    /**
     * Generate CSV string from collection.
     */
    private function generateCsv($contacts, array $fields): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $fields);
        foreach ($contacts as $contact) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $contact->{$field} ?? '';
            }
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
