<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContractTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manageTemplates', Contract::class);

        $templates = ContractTemplate::with(['creator', 'clauses'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return Inertia::render('Admin/ContractTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageTemplates', Contract::class);

        $clauses = ContractClause::active()->get();
        $contractTypes = Contract::TYPES;

        return Inertia::render('Admin/ContractTemplates/Create', [
            'clauses' => $clauses,
            'contractTypes' => $contractTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageTemplates', Contract::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:msa,nda,sla,renewal,upsell,custom'],
            'is_active' => ['boolean'],
            'clauses' => ['required', 'array'],
            'clauses.*.id' => ['required', 'exists:contract_clauses,id'],
            'clauses.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'clauses.*.is_mandatory' => ['boolean'],
            'clauses.*.is_optional' => ['boolean'],
        ]);

        $template = DB::transaction(function () use ($validated) {
            $template = ContractTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            $this->syncClauses($template, $validated['clauses'] ?? []);

            ContractTemplateVersion::create([
                'contract_template_id' => $template->id,
                'version_number' => 1,
                'content' => $validated,
                'change_summary' => ['Initial creation'],
                'created_by' => auth()->id(),
            ]);

            return $template;
        });

        return redirect()->route('admin.contract-templates.index')->with('success', 'Template created.');
    }

    public function edit(ContractTemplate $contractTemplate): Response
    {
        $this->authorize('manageTemplates', Contract::class);

        $contractTemplate->load(['clauses', 'versions']);

        return Inertia::render('Admin/ContractTemplates/Edit', [
            'template' => $contractTemplate,
            'clauses' => ContractClause::active()->get(),
            'contractTypes' => Contract::TYPES,
        ]);
    }

    public function update(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        $this->authorize('manageTemplates', Contract::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:msa,nda,sla,renewal,upsell,custom'],
            'is_active' => ['boolean'],
            'clauses' => ['required', 'array'],
            'clauses.*.id' => ['required', 'exists:contract_clauses,id'],
            'clauses.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'clauses.*.is_mandatory' => ['boolean'],
            'clauses.*.is_optional' => ['boolean'],
        ]);

        DB::transaction(function () use ($contractTemplate, $validated) {
            $contractTemplate->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->syncClauses($contractTemplate, $validated['clauses'] ?? []);

            $nextVersion = $contractTemplate->versions()->max('version_number') + 1;
            ContractTemplateVersion::create([
                'contract_template_id' => $contractTemplate->id,
                'version_number' => $nextVersion,
                'content' => $validated,
                'change_summary' => $validated['change_summary'] ?? ['Updated'],
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.contract-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(ContractTemplate $contractTemplate): RedirectResponse
    {
        $this->authorize('manageTemplates', Contract::class);

        $contractTemplate->delete();

        return back()->with('success', 'Template deleted.');
    }

    public function restore(ContractTemplate $contractTemplate): RedirectResponse
    {
        $this->authorize('manageTemplates', Contract::class);

        $contractTemplate->restore();

        return back()->with('success', 'Template restored.');
    }

    private function syncClauses(ContractTemplate $template, array $clauses): void
    {
        $pivotData = [];
        foreach ($clauses as $clause) {
            $pivotData[$clause['id']] = [
                'sort_order' => $clause['sort_order'] ?? 0,
                'is_mandatory' => $clause['is_mandatory'] ?? false,
                'is_optional' => $clause['is_optional'] ?? false,
                'is_included_by_default' => ! ($clause['is_optional'] ?? false),
            ];
        }

        $template->clauses()->sync($pivotData);
    }
}
