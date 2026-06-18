<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\LegalMatter;
use App\Models\User;
use Illuminate\Foundation\Http\FormResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LegalMatterController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LegalMatter::class);

        $query = LegalMatter::query()
            ->with(['creator', 'assignee', 'account', 'contact'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $matters = $query->paginate(25);

        $statuses = [
            LegalMatter::STATUS_OPEN,
            LegalMatter::STATUS_IN_PROGRESS,
            LegalMatter::STATUS_PENDING_EXTERNAL,
            LegalMatter::STATUS_RESOLVED,
            LegalMatter::STATUS_CLOSED,
        ];
        $types = [
            LegalMatter::TYPE_DISPUTE,
            LegalMatter::TYPE_CORRESPONDENCE,
            LegalMatter::TYPE_REGULATORY,
            LegalMatter::TYPE_ADVISORY,
            LegalMatter::TYPE_CUSTOM,
        ];

        return Inertia::render('Legal/Index', [
            'matters' => $matters,
            'statuses' => $statuses,
            'types' => $types,
            'filters' => $request->only(['search', 'status', 'type', 'assigned_to']),
            'users' => User::select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function show(LegalMatter $legalMatter): Response
    {
        $this->authorize('view', $legalMatter);

        $legalMatter->load([
            'creator',
            'assignee',
            'account',
            'contact',
            'notes.creator',
            'contracts',
        ]);

        return Inertia::render('Legal/Show', [
            'matter' => $legalMatter,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LegalMatter::class);

        $legalMatter = new LegalMatter;

        if ($request->filled('account_id')) {
            $legalMatter->account_id = $request->account_id;
        }
        if ($request->filled('contact_id')) {
            $legalMatter->contact_id = $request->contact_id;
        }

        $accounts = Account::select(['id', 'name'])->orderBy('name')->get();
        $contacts = Contact::select(['id', 'first_name', 'last_name'])->orderBy('first_name')->get();
        $statuses = [
            LegalMatter::STATUS_OPEN,
            LegalMatter::STATUS_IN_PROGRESS,
            LegalMatter::STATUS_PENDING_EXTERNAL,
            LegalMatter::STATUS_RESOLVED,
            LegalMatter::STATUS_CLOSED,
        ];
        $types = [
            LegalMatter::TYPE_DISPUTE,
            LegalMatter::TYPE_CORRESPONDENCE,
            LegalMatter::TYPE_REGULATORY,
            LegalMatter::TYPE_ADVISORY,
            LegalMatter::TYPE_CUSTOM,
        ];
        $users = User::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Legal/Create', [
            'matter' => $legalMatter,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'statuses' => $statuses,
            'types' => $types,
            'users' => $users,
            'preselectedAccountId' => $request->get('account_id'),
            'preselectedContactId' => $request->get('contact_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LegalMatter::class);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:open,in_progress,pending_external,resolved,closed'],
            'type' => ['nullable', 'string', 'in:dispute,correspondence,regulatory,advisory,custom'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $matter = LegalMatter::create(array_merge($validated, [
            'status' => $validated['status'] ?? LegalMatter::STATUS_OPEN,
            'type' => $validated['type'] ?? LegalMatter::TYPE_ADVISORY,
            'created_by' => auth()->id(),
        ]));

        \activity()
            ->performedOn($matter)
            ->withProperties(['matter_id' => $matter->id, 'subject' => $matter->subject])
            ->log('legal_matter_created');

        return redirect()->route('legal.show', $matter)->with('success', 'Matter created.');
    }

    public function edit(LegalMatter $legalMatter): Response
    {
        $this->authorize('update', $legalMatter);

        return Inertia::render('Legal/Edit', [
            'matter' => $legalMatter,
            'users' => User::select(['id', 'name'])->orderBy('name')->get(),
            'accounts' => Account::select(['id', 'name'])->orderBy('name')->get(),
            'contacts' => Contact::select(['id', 'first_name', 'last_name'])->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, LegalMatter $legalMatter): RedirectResponse
    {
        $this->authorize('update', $legalMatter);

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $matterData = $validated;

        if ($request->filled('status')) {
            $matterData['status'] = $request->status;
            if ($request->status === LegalMatter::STATUS_RESOLVED) {
                $matterData['resolved_at'] = now();
            }
            if ($request->status === LegalMatter::STATUS_CLOSED) {
                $matterData['closed_at'] = now();
            }
        }

        $legalMatter->update($matterData);

        \activity()
            ->performedOn($legalMatter)
            ->withProperties(['matter_id' => $legalMatter->id])
            ->log('legal_matter_updated');

        return redirect()->route('legal.show', $legalMatter)->with('success', 'Matter updated.');
    }

    public function destroy(LegalMatter $legalMatter): RedirectResponse
    {
        $this->authorize('delete', $legalMatter);

        $legalMatter->delete();

        return redirect()->route('legal.index')->with('success', 'Matter deleted.');
    }

    public function restore($id): RedirectResponse
    {
        $matter = LegalMatter::withTrashed()->findOrFail($id);

        $this->authorize('update', $matter);

        $matter->restore();

        return back()->with('success', 'Matter restored.');
    }

    public function addNote(Request $request, LegalMatter $legalMatter): RedirectResponse
    {
        $this->authorize('update', $legalMatter);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'type' => ['nullable', 'string', 'max:50'],
            'attachments' => ['nullable', 'array'],
        ]);

        $legalMatter->notes()->create([
            'legal_matter_id' => $legalMatter->id,
            'created_by' => auth()->id(),
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'note',
            'attachments' => $validated['attachments'] ?? [],
        ]);

        \activity()
            ->performedOn($legalMatter)
            ->withProperties(['matter_id' => $legalMatter->id])
            ->log('legal_matter_note_added');

        return back()->with('success', 'Note added.');
    }

    public function uploadAttachment(Request $request, LegalMatter $legalMatter): FormResponse
    {
        $this->authorize('update', $legalMatter);

        $request->validate([
            'attachment' => ['required', 'file', 'max:10240'],
        ]);

        $path = $request->file('attachment')->store('legal/'.$legalMatter->id.'/attachments', 'r2');

        return back()->with('success', 'Attachment uploaded.')->with('attachment_path', $path);
    }

    public function attachmentSignedUrl(Request $request, LegalMatter $legalMatter)
    {
        $this->authorize('update', $legalMatter);

        $path = $request->validate([
            'path' => ['required', 'string', 'starts_with:legal/'.$legalMatter->id.'/attachments/'],
        ])['path'];

        if (! Storage::disk('r2')->exists($path)) {
            abort(404, 'Attachment not found.');
        }

        return response()->json([
            'url' => Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15)),
            'path' => $path,
        ]);
    }

    public function indexApi(Request $request)
    {
        $this->authorize('viewAny', LegalMatter::class);

        $query = LegalMatter::query()
            ->with(['creator', 'assignee', 'account', 'contact'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->paginate(25));
    }
}
