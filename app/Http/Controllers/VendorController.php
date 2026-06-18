<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VendorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vendor::class);

        $vendors = QueryBuilder::for(Vendor::query())
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category')
            )
            ->when($request->filled('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withAvg(['ratings as overall_rating'], 'quality + delivery_timeliness + communication + value_for_money')
            ->orderBy('name')
            ->paginate(25)
            ->appends($request->query());

        $categories = [Vendor::CATEGORY_GOODS, Vendor::CATEGORY_SERVICES, Vendor::CATEGORY_BOTH];

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'category']),
            'statuses' => [Vendor::STATUS_ACTIVE, Vendor::STATUS_INACTIVE, Vendor::STATUS_BLACKLISTED],
        ]);
    }

    public function show(Vendor $vendor): Response
    {
        $this->authorize('view', $vendor);

        $vendor->load(['ratings.rater', 'ratings.purchaseOrder']);

        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
            'canViewFinancials' => auth()->user()->can('viewFinancials', $vendor),
            'purchaseOrders' => $vendor->purchaseOrders()->with('lineItems')->latest()->take(10)->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Vendor::class);

        return Inertia::render('Vendors/Create', [
            'categories' => [Vendor::CATEGORY_GOODS, Vendor::CATEGORY_SERVICES, Vendor::CATEGORY_BOTH],
            'statuses' => [Vendor::STATUS_ACTIVE, Vendor::STATUS_INACTIVE],
            'canViewFinancials' => auth()->user()->can('viewFinancials', Vendor::class),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Vendor::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:goods,services,both'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'primary_contact_email' => ['required', 'email'],
            'primary_contact_phone' => ['required', 'string'],
            'registration_number' => ['nullable', 'string'],
            'tax_identification_number' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'branch_code' => ['nullable', 'string'],
            'swift_code' => ['nullable', 'string'],
            'physical_address' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'status' => ['required', 'in:active,inactive,blacklisted'],
        ]);

        $vendor = Vendor::create($validated);

        \activity()
            ->performedOn($vendor)
            ->withProperties(['vendor_name' => $vendor->name])
            ->log('vendor_created');

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor created.');
    }

    public function edit(Vendor $vendor): Response
    {
        $this->authorize('update', $vendor);

        return Inertia::render('Vendors/Edit', [
            'vendor' => $vendor,
            'categories' => [Vendor::CATEGORY_GOODS, Vendor::CATEGORY_SERVICES, Vendor::CATEGORY_BOTH],
            'statuses' => [Vendor::STATUS_ACTIVE, Vendor::STATUS_INACTIVE, Vendor::STATUS_BLACKLISTED],
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'in:goods,services,both'],
            'primary_contact_name' => ['sometimes', 'string', 'max:255'],
            'primary_contact_email' => ['sometimes', 'email'],
            'primary_contact_phone' => ['sometimes', 'string'],
            'registration_number' => ['nullable', 'string'],
            'tax_identification_number' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'branch_code' => ['nullable', 'string'],
            'swift_code' => ['nullable', 'string'],
            'physical_address' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'status' => ['sometimes', 'in:active,inactive,blacklisted'],
        ]);

        $vendor->update($validated);

        \activity()
            ->performedOn($vendor)
            ->withProperties(['vendor_id' => $vendor->id])
            ->log('vendor_updated');

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        $this->authorize('delete', $vendor);

        $vendor->delete();

        \activity()
            ->performedOn($vendor)
            ->withProperties(['vendor_id' => $vendor->id])
            ->log('vendor_deleted');

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted.');
    }

    public function addRating(Request $request, Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        $validated = $request->validate([
            'quality' => ['required', 'integer', 'min:1', 'max:5'],
            'delivery_timeliness' => ['required', 'integer', 'min:1', 'max:5'],
            'communication' => ['required', 'integer', 'min:1', 'max:5'],
            'value_for_money' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
        ]);

        $vendor->ratings()->create(array_merge($validated, [
            'rated_by' => auth()->id(),
            'rated_at' => now(),
        ]));

        \activity()
            ->performedOn($vendor)
            ->log('vendor_rated');

        return back()->with('success', 'Rating added.');
    }
}
