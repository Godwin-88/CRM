<?php

namespace App\Http\Controllers;

use App\Models\BankingRelationship;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankingRelationshipController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BankingRelationship::class);

        $relationships = QueryBuilder::for(BankingRelationship::query())
            ->allowedFilters(AllowedFilter::exact('relationship_type'))
            ->when($request->filled('search'), function ($query, $search) {
                $query->where('institution_name', 'like', "%{$search}%");
            })
            ->orderBy('institution_name')
            ->paginate(25)
            ->appends($request->query());

        return Inertia::render('Banking/Index', [
            'relationships' => $relationships,
            'filters' => $request->only(['search', 'relationship_type']),
            'types' => [
                BankingRelationship::TYPE_CURRENT_ACCOUNT,
                BankingRelationship::TYPE_CREDIT_FACILITY,
                BankingRelationship::TYPE_OVERDRAFT,
                BankingRelationship::TYPE_TRADE_FINANCE,
                BankingRelationship::TYPE_TREASURY,
            ],
        ]);
    }

    public function show(BankingRelationship $bankingRelationship): Response
    {
        $this->authorize('view', $bankingRelationship);

        return Inertia::render('Banking/Show', [
            'relationship' => $bankingRelationship,
            'canViewFinancials' => auth()->user()->can('viewFinancials', $bankingRelationship),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', BankingRelationship::class);

        return Inertia::render('Banking/Create', [
            'types' => [
                BankingRelationship::TYPE_CURRENT_ACCOUNT,
                BankingRelationship::TYPE_CREDIT_FACILITY,
                BankingRelationship::TYPE_OVERDRAFT,
                BankingRelationship::TYPE_TRADE_FINANCE,
                BankingRelationship::TYPE_TREASURY,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', BankingRelationship::class);

        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'relationship_type' => ['required', 'in:current_account,credit_facility,overdraft,trade_finance,treasury'],
            'relationship_manager_name' => ['required', 'string', 'max:255'],
            'relationship_manager_email' => ['required', 'email'],
            'relationship_manager_phone' => ['required', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'facility_expiry_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'string'],
        ]);

        $relationship = BankingRelationship::create($validated);

        \activity()
            ->performedOn($relationship)
            ->withProperties(['institution' => $relationship->institution_name])
            ->log('banking_relationship_created');

        return redirect()->route('banking.show', $relationship)->with('success', 'Banking relationship created.');
    }

    public function edit(BankingRelationship $bankingRelationship): Response
    {
        $this->authorize('update', $bankingRelationship);

        return Inertia::render('Banking/Edit', [
            'relationship' => $bankingRelationship,
            'types' => [
                BankingRelationship::TYPE_CURRENT_ACCOUNT,
                BankingRelationship::TYPE_CREDIT_FACILITY,
                BankingRelationship::TYPE_OVERDRAFT,
                BankingRelationship::TYPE_TRADE_FINANCE,
                BankingRelationship::TYPE_TREASURY,
            ],
        ]);
    }

    public function update(Request $request, BankingRelationship $bankingRelationship)
    {
        $this->authorize('update', $bankingRelationship);

        $validated = $request->validate([
            'institution_name' => ['sometimes', 'string', 'max:255'],
            'relationship_type' => ['sometimes', 'in:current_account,credit_facility,overdraft,trade_finance,treasury'],
            'relationship_manager_name' => ['sometimes', 'string', 'max:255'],
            'relationship_manager_email' => ['sometimes', 'email'],
            'relationship_manager_phone' => ['sometimes', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'facility_expiry_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'string'],
        ]);

        $bankingRelationship->update($validated);

        \activity()
            ->performedOn($bankingRelationship)
            ->log('banking_relationship_updated');

        return redirect()->route('banking.show', $bankingRelationship)->with('success', 'Banking relationship updated.');
    }

    public function destroy(BankingRelationship $bankingRelationship)
    {
        $this->authorize('delete', $bankingRelationship);

        $bankingRelationship->delete();

        \activity()
            ->performedOn($bankingRelationship)
            ->log('banking_relationship_deleted');

        return redirect()->route('banking.index')->with('success', 'Banking relationship deleted.');
    }
}
