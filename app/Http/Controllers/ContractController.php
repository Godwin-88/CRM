<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contract::class);

        $perPage = $request->get('per_page', 25);

        $contracts = QueryBuilder::for(Contract::query())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('account_id'),
                AllowedFilter::exact('account_manager_id'),
            ])
            ->allowedIncludes(['account', 'contact', 'template'])
            ->when($request->filled('search'), function ($q, $search) {
                $q->where(function ($qb) use ($search) {
                    $qb->where('title', 'like', "%{$search}%")
                        ->orWhereHas('account', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('contact', fn ($q2) => $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('end_date', 'asc')
            ->paginate($perPage)
            ->appends($request->query());

        $contractTypes = Contract::TYPES;
        $statuses = [
            Contract::STATUS_DRAFT,
            Contract::STATUS_SENT,
            Contract::STATUS_SIGNED,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_EXPIRING,
            Contract::STATUS_EXPIRED,
            Contract::STATUS_DECLINED,
            Contract::STATUS_TERMINATED,
        ];

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'contractTypes' => $contractTypes,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'status', 'type', 'account_id', 'account_manager_id']),
        ]);
    }

    public function show(Contract $contract): Response
    {
        $this->authorize('view', $contract);

        $contract->load([
            'account',
            'contact',
            'template.clauses',
            'createdBy',
            'accountManager',
            'versions' => fn ($q) => $q->orderByDesc('version_number'),
            'signatories' => fn ($q) => $q->orderBy('signing_order'),
            'milestones' => fn ($q) => $q->orderBy('due_date'),
            'kpiValues.kpiField',
            'tags',
        ]);

        $activeVersion = $contract->versions()->where('status', 'active')->latest('version_number')->first();

        return Inertia::render('Contracts/Show', [
            'contract' => $contract,
            'activeVersion' => $activeVersion,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Contract::class);

        $templates = ContractTemplate::active()->get();
        $accounts = Account::select(['id', 'name'])->orderBy('name')->get();
        $contacts = Contact::select(['id', 'first_name', 'last_name', 'email'])->orderBy('first_name')->get();
        $contractTypes = Contract::TYPES;

        return Inertia::render('Contracts/Create', [
            'templates' => $templates,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'contractTypes' => $contractTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:msa,nda,sla,renewal,upsell,custom'],
            'template_id' => ['nullable', 'exists:contract_templates,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $contract = Contract::create(array_merge($validated, [
            'status' => Contract::STATUS_DRAFT,
            'created_by' => auth()->id(),
            'account_manager_id' => auth()->id(),
        ]));

        \activity()
            ->performedOn($contract)
            ->withProperties([
                'contract_type' => $contract->type,
                'status' => $contract->status,
            ])
            ->log('contract_created');

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract created.');
    }

    public function edit(Contract $contract): Response
    {
        $this->authorize('update', $contract);

        $contract->load(['template.clauses', 'signatories', 'versions']);
        $templates = ContractTemplate::active()->get();
        $contractTypes = Contract::TYPES;

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract,
            'templates' => $templates,
            'contractTypes' => $contractTypes,
        ]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:msa,nda,sla,renewal,upsell,custom'],
            'template_id' => ['nullable', 'exists:contract_templates,id'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'custom_variables' => ['nullable', 'array'],
        ]);

        $contract->update($validated);

        \activity()
            ->performedOn($contract)
            ->withProperties([
                'contract_id' => $contract->id,
            ])
            ->log('contract_updated');

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract updated.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        $contract->delete();

        activity()
            ->performedOn($contract)
            ->withProperties([
                'contract_id' => $contract->id,
            ])
            ->log('contract_deleted');

        return redirect()->route('contracts.index')->with('success', 'Contract deleted.');
    }

    public function duplicate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $new = $contract->replicate();
        $new->title = $contract->title.' (Copy)';
        $new->status = Contract::STATUS_DRAFT;
        $new->current_version = 1;
        $new->document_path = null;
        $new->e_signature_status = null;
        $new->signed_at = null;
        $new->activated_at = null;
        $new->terminated_at = null;
        $new->termination_reason = null;
        $new->created_by = auth()->id();
        $new->save();

        $new->load('account', 'contact', 'template');

        return redirect()->route('contracts.edit', $new)->with('success', 'Contract duplicated.');
    }

    public function regenerate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $contract->current_version = ($contract->current_version ?? 1) + 1;
        $contract->save();

        activity()
            ->performedOn($contract)
            ->withProperties([
                'contract_id' => $contract->id,
                'new_version' => $contract->current_version,
            ])
            ->log('contract_regenerated');

        return back()->with('success', 'Contract regeneration queued. New version: '.$contract->current_version);
    }

    public function downloadSignedUrl(Contract $contract)
    {
        $this->authorize('view', $contract);

        $path = $contract->document_path
            ?? 'contracts/'.$contract->account_id.'/'.$contract->id.'/v'.$contract->current_version.'.pdf';

        if (! Storage::disk('r2')->exists($path)) {
            abort(404, 'Document not found.');
        }

        if (request()->wantsJson()) {
            return response()->json([
                'url' => Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15)),
                'path' => $path,
            ]);
        }

        return redirect(Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15)));
    }

    public function bulkExport(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $validated = $request->validate([
            'contract_ids' => ['required', 'array'],
            'contract_ids.*' => ['exists:contracts,id'],
        ]);

        $contracts = Contract::whereIn('id', $validated['contract_ids'])->get();

        $zipFileName = 'contracts_export_'.now()->format('Y-m-d_Hi').'.zip';
        $zipPath = storage_path('app/tmp/'.$zipFileName);

        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($contracts as $contract) {
                $path = $contract->document_path
                    ?? 'contracts/'.$contract->account_id.'/'.$contract->id.'/v'.$contract->current_version.'.pdf';

                if (Storage::disk('r2')->exists($path)) {
                    $pdfContent = Storage::disk('r2')->get($path);
                    $fileName = ($contract->type ?? 'contract').'_'.($contract->account?->name ?? 'unknown').'_'.($contract->end_date ? $contract->end_date->format('Y-m-d') : 'nodate').'.pdf';
                    $zip->addFromString($fileName, $pdfContent);
                }
            }

            $zip->close();
        }

        activity()
            ->performedOn($contracts->first())
            ->withProperties([
                'contract_ids' => $validated['contract_ids'],
                'export_filename' => $zipFileName,
            ])
            ->log('contracts_bulk_exported');

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function indexApi(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $perPage = $request->get('per_page', 25);

        $contracts = QueryBuilder::for(Contract::query())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('account_id'),
                AllowedFilter::exact('account_manager_id'),
            ])
            ->orderBy('end_date', 'asc')
            ->paginate($perPage);

        return response()->json($contracts);
    }

    public function showApi(Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load([
            'account',
            'contact',
            'template',
            'versions' => fn ($q) => $q->orderByDesc('version_number'),
            'signatories' => fn ($q) => $q->orderBy('signing_order'),
            'milestones',
            'kpiValues.kpiField',
            'tags',
        ]);

        return response()->json($contract);
    }
}
