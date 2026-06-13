<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReactivationWebController extends Controller
{
    public function index(): Response
    {
        $configs = ReactivationConfig::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Reactivation', [
            'configs' => $configs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|json',
            'actions' => 'required|json',
            'is_active' => 'boolean',
        ]);

        ReactivationConfig::create($request->all());

        return redirect()->route('admin.reactivation.index')->with('success', 'Reactivation configuration created successfully.');
    }

    public function update(Request $request, ReactivationConfig $reactivationConfig)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|json',
            'actions' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $reactivationConfig->update($request->all());

        return redirect()->route('admin.reactivation.index')->with('success', 'Reactivation configuration updated successfully.');
    }

    public function contacts(): Response
    {
        $contacts = ReactivationContact::with(['contact', 'config'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/ReactivationContacts', [
            'contacts' => $contacts,
        ]);
    }
}
