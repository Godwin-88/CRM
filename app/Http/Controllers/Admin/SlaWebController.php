<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessHours;
use App\Models\SlaDefinition;
use App\Models\SlaInstance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SlaWebController extends Controller
{
    public function index(): Response
    {
        $slaDefinitions = SlaDefinition::with(['businessHours'])->orderBy('created_at', 'desc')->get();
        $businessHours = BusinessHours::orderBy('name')->get();

        return Inertia::render('Admin/Sla', [
            'slaDefinitions' => $slaDefinitions,
            'businessHours' => $businessHours,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'support_category' => 'nullable|string|max:100',
            'loyalty_tier_id' => 'nullable|exists:loyalty_tiers,id',
            'account_type' => 'nullable|string|max:100',
            'first_response_time_business_hours' => 'required|integer|min:1',
            'resolution_time_business_hours' => 'required|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'business_hours' => 'nullable|array',
        ]);

        $sla = SlaDefinition::create($request->all());

        if ($request->filled('business_hours')) {
            foreach ($request->business_hours as $bh) {
                $sla->businessHours()->create($bh);
            }
        }

        return redirect()->route('admin.sla.index')->with('success', 'SLA definition created successfully.');
    }

    public function update(Request $request, SlaDefinition $slaDefinition)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'support_category' => 'nullable|string|max:100',
            'loyalty_tier_id' => 'nullable|exists:loyalty_tiers,id',
            'account_type' => 'nullable|string|max:100',
            'first_response_time_business_hours' => 'sometimes|integer|min:1',
            'resolution_time_business_hours' => 'sometimes|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'business_hours' => 'nullable|array',
        ]);

        $slaDefinition->update($request->all());

        if ($request->filled('business_hours')) {
            $slaDefinition->businessHours()->delete();
            foreach ($request->business_hours as $bh) {
                $slaDefinition->businessHours()->create($bh);
            }
        }

        return redirect()->route('admin.sla.index')->with('success', 'SLA definition updated successfully.');
    }

    public function instances(): Response
    {
        $instances = SlaInstance::with(['ticket', 'slaDefinition'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/SlaInstances', [
            'instances' => $instances,
        ]);
    }
}
