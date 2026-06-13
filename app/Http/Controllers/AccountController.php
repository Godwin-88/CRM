<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
            'customFieldValues',
        ]);

        return Inertia::render('Accounts/Show', [
            'account' => $account,
        ]);
    }
}
