<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use App\Models\TicketForm;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketFormController extends Controller
{
    public function index()
    {
        $forms = TicketForm::with('category')
            ->paginate(50);

        $categories = TicketCategory::active()->get(['id', 'name']);

        return Inertia::render('Admin/Support/Forms', [
            'forms' => $forms,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'name' => 'required|string|max:255',
            'fields' => 'required|array',
        ]);

        $form = TicketForm::create($validated);

        return redirect()->route('admin.support.forms.index')
            ->with('success', 'Form created successfully.');
    }

    public function update(Request $request, TicketForm $ticketForm)
    {
        $validated = $request->validate([
            'ticket_category_id' => 'sometimes|exists:ticket_categories,id',
            'name' => 'sometimes|string|max:255',
            'fields' => 'sometimes|array',
        ]);

        $ticketForm->update($validated);

        return redirect()->route('admin.support.forms.index')
            ->with('success', 'Form updated successfully.');
    }
}
