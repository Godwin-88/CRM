<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeEmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EmailTemplate::class, 'template');
    }

    public function index(): Response
    {
        $templates = EmailTemplate::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/WelcomeEmailTemplates', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        EmailTemplate::create($validated);

        return redirect()->route('admin.welcome-email-templates.index')->with('success', 'Template created successfully.');
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('admin.welcome-email-templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(EmailTemplate $template)
    {
        $template->delete();

        return redirect()->route('admin.welcome-email-templates.index')->with('success', 'Template deleted successfully.');
    }
}
