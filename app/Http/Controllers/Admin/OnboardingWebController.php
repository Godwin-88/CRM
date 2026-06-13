<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnboardingActivity;
use App\Models\OnboardingRecord;
use App\Models\OnboardingTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingWebController extends Controller
{
    public function index(): Response
    {
        $templates = OnboardingTemplate::orderBy('created_at', 'desc')->get();
        $records = OnboardingRecord::with(['contact', 'template'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/Onboarding', [
            'templates' => $templates,
            'records' => $records,
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'steps' => 'required|json',
            'is_active' => 'boolean',
        ]);

        OnboardingTemplate::create($request->all());

        return redirect()->route('admin.onboarding.index')->with('success', 'Onboarding template created successfully.');
    }

    public function updateTemplate(Request $request, OnboardingTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'steps' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $template->update($request->all());

        return redirect()->route('admin.onboarding.index')->with('success', 'Onboarding template updated successfully.');
    }

    public function records(): Response
    {
        $records = OnboardingRecord::with(['contact', 'template', 'activities'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/OnboardingRecords', [
            'records' => $records,
        ]);
    }

    public function activities(): Response
    {
        $activities = OnboardingActivity::with(['record.contact', 'record.template'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/OnboardingActivities', [
            'activities' => $activities,
        ]);
    }
}
