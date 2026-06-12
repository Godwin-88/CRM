<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(protected AccountService $accountService) {}

    /**
     * Display a listing of accounts with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $query = Account::query()->with(['accountManager', 'parentAccount']);

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('industry')) {
            $query->where('industry', $request->industry);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('account_manager_id')) {
            $query->where('account_manager_id', $request->account_manager_id);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $request->input('custom_fields', []);

        $account = $this->accountService->createAccount($validated, $customFields);

        return response()->json($account->load(['accountManager', 'parentAccount', 'customFieldValues']), 201);
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $account->load([
            'subAccounts',
            'accountManager',
            'parentAccount',
            'contacts' => function ($q) { $q->withPivot('is_primary'); },
            'customFieldValues',
            'deals' => function ($q) { $q->latest()->limit(10); },
            'tickets' => function ($q) { $q->latest()->limit(10); },
            'contracts' => function ($q) { $q->latest()->limit(5); },
        ]);

        return response()->json($account);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'annual_revenue' => 'nullable|numeric',
            'employee_count' => 'nullable|integer',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'account_manager_id' => 'nullable|exists:users,id',
            'custom_fields' => 'nullable|array',
        ]);

        $customFields = $request->input('custom_fields', []);
        $account = $this->accountService->updateAccount($account, $validated, $customFields);

        return response()->json($account->load(['accountManager', 'parentAccount', 'customFieldValues']));
    }

    /**
     * Remove the specified account (soft delete).
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->authorize('delete', $account);
        $this->accountService->deleteAccount($account);
        return response()->json(null, 204);
    }

    /**
     * Get contacts linked to an account.
     */
    public function getAccountContacts(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $contacts = $account->contacts()
            ->withPivot('is_primary')
            ->with(['owner'])
            ->paginate();

        return response()->json($contacts);
    }

    /**
     * Set primary contact for an account.
     */
    public function setPrimaryContact(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
        ]);

        // Unset current primary
        \DB::table('contact_account')
            ->where('account_id', $account->id)
            ->update(['is_primary' => false]);

        // Set new primary
        \DB::table('contact_account')
            ->where('account_id', $account->id)
            ->where('contact_id', $validated['contact_id'])
            ->update(['is_primary' => true]);

        activity()
            ->performedOn($account)
            ->causedBy(auth()->user())
            ->withProperties(['primary_contact_id' => $validated['contact_id']])
            ->event('primary_set')
            ->log('Account primary contact changed');

        return response()->json(['message' => 'Primary contact updated.']);
    }
}