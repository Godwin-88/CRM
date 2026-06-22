<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Account::query()->with(['accountManager', 'parentAccount']);

        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
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

        return Inertia::render('Accounts/Index', [
            'accounts' => $query->paginate(10)->through(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'industry' => $account->industry,
                    'status' => $account->status,
                    'website' => $account->website,
                    'phone' => $account->phone,
                    'city' => $account->city,
                    'country' => $account->country,
                    'manager' => $account->accountManager?->only(['id', 'name']),
                ];
            }),
            'filters' => $request->only(['name', 'type', 'industry', 'status']),
        ]);
    }

    public function show(Account $account): Response
    {
        $account->load([
            'accountManager',
            'parentAccount',
            'contacts' => function ($q) {
                $q->withPivot('is_primary')->with(['owner']);
            },
            'deals' => function ($q) {
                $q->latest()->limit(10)->with(['owner']);
            },
            'tickets' => function ($q) {
                $q->latest()->limit(10);
            },
            'contracts' => function ($q) {
                $q->latest()->limit(5);
            },
            'invoices' => function ($q) {
                $q->latest()->limit(20)->with(['payments', 'contact']);
            },
            'customFieldValues',
        ]);

        $financialSummary = [
            'total_invoiced' => (float) $account->invoices()->sum('total'),
            'total_paid' => (float) Payment::whereHas('invoice', fn ($q) => $q->where('account_id', $account->id))->sum('amount'),
            'outstanding_balance' => (float) $account->invoices()->whereIn('status', ['sent', 'partially_paid', 'overdue'])->sum('total') - (float) Payment::whereHas('invoice', fn ($q) => $q->where('account_id', $account->id))->sum('amount'),
            'overdue_count' => $account->invoices()->where('status', 'overdue')->count(),
            'avg_payment_delay' => $account->invoices()->where('status', 'paid')->exists() ? now()->diffInDays($account->invoices()->where('status', 'paid')->latest('paid_at')->first()?->paid_at) : 0,
        ];

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'financialSummary' => $financialSummary,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'status' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'annual_revenue' => 'nullable|numeric|min:0',
            'employee_count' => 'nullable|integer|min:0',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'account_manager_id' => 'nullable|exists:users,id',
        ]);

        $account = Account::create($validated);

        return redirect()->route('accounts.show', $account)->with('success', 'Account created successfully.');
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'status' => 'sometimes|string|max:20',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'annual_revenue' => 'nullable|numeric|min:0',
            'employee_count' => 'nullable|integer|min:0',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'account_manager_id' => 'nullable|exists:users,id',
        ]);

        $account->update($validated);

        return redirect()->route('accounts.show', $account)->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }
}
