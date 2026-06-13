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
            'avg_payment_delay' => $account->invoices()->where('status', 'paid')->avg('paid_at') ? now()->diffInDays($account->invoices()->where('status', 'paid')->latest('paid_at')->first()?->paid_at) : 0,
        ];

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'financialSummary' => $financialSummary,
        ]);
    }
}
