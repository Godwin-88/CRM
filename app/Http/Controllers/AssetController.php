<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $assets = QueryBuilder::for(Asset::query())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
            ])
            ->with(['assignee', 'assignedAccount'])
            ->when($request->filled('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(25)
            ->appends($request->query());

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'status', 'type']),
            'statuses' => [Asset::STATUS_AVAILABLE, Asset::STATUS_ASSIGNED, Asset::STATUS_MAINTENANCE, Asset::STATUS_DISPOSED],
            'types' => AssetType::pluck('name'),
        ]);
    }

    public function show(Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load(['assignments.assignee', 'assignedAccount']);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
            'canAssign' => auth()->user()->can('assign', $asset),
            'users' => User::select(['id', 'name'])->orderBy('name')->get(),
            'accounts' => Account::select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Assets/Create', [
            'types' => AssetType::pluck('name'),
            'statuses' => [Asset::STATUS_AVAILABLE, Asset::STATUS_ASSIGNED, Asset::STATUS_MAINTENANCE, Asset::STATUS_DISPOSED],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Asset::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'identifier' => ['nullable', 'string'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'status' => ['required', 'in:available,assigned,under_maintenance,disposed'],
            'useful_life_years' => ['nullable', 'integer', 'min:1'],
            'total_quantity' => ['nullable', 'numeric', 'min:0'],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'minimum_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        $asset = Asset::create($validated);

        if (! empty($validated['total_quantity']) && empty($validated['available_quantity'])) {
            $asset->update(['available_quantity' => $validated['total_quantity']]);
        }

        \activity()
            ->performedOn($asset)
            ->withProperties(['asset_name' => $asset->name])
            ->log('asset_created');

        return redirect()->route('assets.show', $asset)->with('success', 'Asset created.');
    }

    public function assign(Request $request, Asset $asset)
    {
        $this->authorize('assign', $asset);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'assigned_to_account' => ['nullable', 'exists:accounts,id'],
            'assignment_start_date' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date'],
        ]);

        if ($asset->status === Asset::STATUS_ASSIGNED && ! $validated['assigned_to'] && ! $validated['assigned_to_account']) {
            return back()->withErrors(['assignment' => 'Asset must be returned before reassignment.']);
        }

        $asset->assignments()->create($validated);

        $asset->update([
            'assigned_to' => $validated['assigned_to'],
            'assigned_to_account' => $validated['assigned_to_account'],
            'assignment_start_date' => $validated['assignment_start_date'],
            'expected_return_date' => $validated['expected_return_date'],
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        \activity()
            ->performedOn($asset)
            ->log('asset_assigned');

        return back()->with('success', 'Asset assigned.');
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        $this->authorize('update', $asset);

        $validated = $request->validate([
            'returned_at' => ['required', 'date'],
            'condition_note' => ['nullable', 'string'],
        ]);

        $requiresMaintenance = str_contains(strtolower($validated['condition_note'] ?? ''), 'repair')
            || str_contains(strtolower($validated['condition_note'] ?? ''), 'damaged')
            || str_contains(strtolower($validated['condition_note'] ?? ''), 'broken');

        $asset->assignments()->whereNull('returned_at')->latest()->first()?->update([
            'returned_at' => $validated['returned_at'],
            'condition_note' => $validated['condition_note'],
            'requires_maintenance' => $requiresMaintenance,
        ]);

        $asset->update([
            'assigned_to' => null,
            'assigned_to_account' => null,
            'assignment_start_date' => null,
            'expected_return_date' => null,
            'status' => $requiresMaintenance ? Asset::STATUS_MAINTENANCE : Asset::STATUS_AVAILABLE,
        ]);

        \activity()
            ->performedOn($asset)
            ->log('asset_returned');

        return back()->with('success', 'Asset returned.');
    }

    public function export()
    {
        $this->authorize('viewAny', Asset::class);

        $assets = Asset::with(['assignee', 'assignedAccount', 'assignments'])->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=asset_register.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($assets) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Type', 'Identifier', 'Status', 'Assigned To', 'Assigned Account', 'Book Value', 'Last Assignment']);

            foreach ($assets as $asset) {
                fputcsv($file, [
                    $asset->name,
                    $asset->type,
                    $asset->identifier,
                    $asset->status,
                    $asset->assignee?->name,
                    $asset->assignedAccount?->name,
                    $asset->book_value,
                    $asset->last_assignment_date,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
