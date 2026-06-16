<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\ContactService;
use App\Services\TimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected TimelineService $timelineService,
    ) {}

    public function downloadTemplate()
    {
        $headers = ['first_name', 'last_name', 'email', 'type', 'status'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contact_import_template.csv"',
        ]);
    }

    public function index(Request $request): Response
    {
        $query = Contact::query()->with(['owner']);

        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->first_name.'%');
        }
        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%'.$request->last_name.'%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Contacts/Index', [
            'contacts' => $query->paginate(10)->through(function ($contact) {
                return [
                    'id' => $contact->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'type' => $contact->type,
                    'status' => $contact->status,
                    'source' => $contact->source,
                    'score' => $contact->score,
                    'owner' => $contact->owner?->only(['id', 'name']),
                ];
            }),
            'filters' => $request->only(['first_name', 'last_name', 'email', 'type', 'status']),
        ]);
    }

    public function show(Contact $contact): Response
    {
        $contact->load([
            'owner',
            'accounts' => function ($q) {
                $q->withPivot('is_primary');
            },
            'customFieldValues',
            'interactions' => function ($q) {
                $q->latest()->limit(5);
            },
        ]);

        $timeline = $this->timelineService->getTimeline($contact);

        $allAccounts = Account::select(['id', 'name'])->get();
        $contactDeals = $contact->deals()->with('account')->get(['id', 'title', 'stage', 'value']);

        $enrollment = $contact->loyaltyEnrollments()
            ->where('is_active', true)
            ->with(['program', 'tier'])
            ->first();

        $latestLedger = null;
        $recentLedger = [];
        if ($enrollment) {
            $latestLedger = \App\Models\PointsLedger::where('enrollment_id', $enrollment->id)
                ->orderByDesc('transaction_date')
                ->first();
            $recentLedger = \App\Models\PointsLedger::where('enrollment_id', $enrollment->id)
                ->with('program')
                ->orderByDesc('transaction_date')
                ->limit(10)
                ->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'type' => $l->type,
                    'points_amount' => $l->points_amount,
                    'running_balance' => $l->running_balance,
                    'description' => $l->description,
                    'transaction_date' => $l->transaction_date,
                    'program_name' => $l->program?->name ?? '—',
                ]);
        }

        $loyaltyData = [
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'program_id' => $enrollment->program_id,
                'program_name' => $enrollment->program?->name ?? '—',
                'program_type' => $enrollment->program?->program_type ?? '—',
                'currency_label' => $enrollment->program?->currency_label ?? '',
                'currency_symbol' => $enrollment->program?->currency_symbol ?? '',
                'earn_rate' => $enrollment->program?->earn_rate ?? 0,
                'enrolled_at' => $enrollment->enrolled_at,
                'tier_id' => $enrollment->tier_id,
                'tier_name' => $enrollment->tier?->name ?? null,
            ] : null,
            'balance' => $latestLedger?->running_balance ?? 0,
            'next_tier' => null,
            'recent_ledger' => $recentLedger,
        ];

        if ($enrollment && $latestLedger) {
            $nextTier = \App\Models\LoyaltyTier::where('program_id', $enrollment->program_id)
                ->where('min_points_threshold', '>', $latestLedger->running_balance)
                ->orderBy('min_points_threshold')
                ->first();

            if ($nextTier) {
                $loyaltyData['next_tier'] = [
                    'id' => $nextTier->id,
                    'name' => $nextTier->name,
                    'min_points_threshold' => $nextTier->min_points_threshold,
                ];
            }
        }

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'accounts' => $allAccounts,
            'timelineEvents' => $timeline->items(),
            'loyalty' => $loyaltyData,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->contactService->createContact($request->validated());

        return redirect()->route('contacts.index')->with('success', 'Contact created.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:contacts,id'],
        ]);

        Contact::whereIn('id', $request->ids)->delete();

        return back()->with('success', count($request->ids) . ' contacts deleted.');
    }

    public function edit(Contact $contact): Response
    {
        $users = User::select(['id', 'name'])->get();
        $accounts = Account::select(['id', 'name'])->get();

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact->load(['customFieldValues']),
            'users' => $users,
            'accounts' => $accounts,
        ]);
    }

    public function update(StoreContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contactService->updateContact(
            $contact,
            $request->validated(),
            $request->input('custom_fields', [])
        );

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact updated.');
    }

    public function linkAccount(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $contact->accounts()->syncWithoutDetaching([
            $validated['account_id'] => ['is_primary' => $validated['is_primary'] ?? false],
        ]);

        return back()->with('success', 'Account linked successfully.');
    }

    public function unlinkAccount(Contact $contact, string $accountId): RedirectResponse
    {
        $contact->accounts()->detach($accountId);

        return back()->with('success', 'Account unlinked successfully.');
    }

    public function createDeal(Contact $contact): Response
    {
        $pipelines = Pipeline::with('stages')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $accounts = Account::select(['id', 'name'])->get();

        return Inertia::render('Deals/Form', [
            'pipelines' => $pipelines,
            'accounts' => $accounts,
            'preselectedContactId' => $contact->id,
            'preselectedAccountId' => $contact->accounts->first()?->id,
        ]);
    }

    public function storeDeal(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'expected_close_date' => ['nullable', 'date'],
            'pipeline_id' => ['required', 'exists:pipelines,id'],
        ]);

        $deal = Deal::create([
            'title' => $validated['title'],
            'contact_id' => $contact->id,
            'account_id' => $validated['account_id'],
            'stage' => $validated['stage'],
            'value' => $validated['value'] ?? 0,
            'probability' => 0,
            'currency' => 'USD',
            'pipeline_id' => $validated['pipeline_id'],
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('deals.show', $deal)->with('success', 'Deal created successfully.');
    }
}
