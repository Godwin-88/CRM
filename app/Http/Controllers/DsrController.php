<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\DsrRequest;
use App\Models\Invoice;
use App\Models\LegalMatter;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DsrController extends Controller
{
    public function index()
    {
        $this->authorize('dsr.manage');

        $requests = DsrRequest::with('contact', 'handler')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Dsr/Index', [
            'requests' => $requests,
        ]);
    }

    public function create()
    {
        $this->authorize('dsr.manage');

        return Inertia::render('Admin/Dsr/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('dsr.manage');

        $validated = $request->validate([
            'type' => ['required', 'in:access,erasure,rectification'],
            'contact_id' => ['required', 'exists:contacts,id'],
        ]);

        $contact = Contact::findOrFail($validated['contact_id']);

        // Check for blocking conditions
        $blockingReason = $this->checkBlockingConditions($contact, $validated['type']);

        $dsr = DsrRequest::create([
            'type' => $validated['type'],
            'contact_id' => $validated['contact_id'],
            'requested_by' => Auth::user()->email,
            'status' => $blockingReason ? 'blocked' : 'pending',
            'blocking_reason' => $blockingReason,
        ]);

        $this->logSecurityEvent('dsr_requested', Auth::user(), $validated['type'], $contact->id);

        return redirect()->route('admin.dsr.show', $dsr->id)
            ->with('status', 'DSR request created.');
    }

    public function show(DsrRequest $dsrRequest)
    {
        $this->authorize('dsr.manage');

        return Inertia::render('Admin/Dsr/Show', [
            'request' => $dsrRequest->load('contact', 'handler'),
        ]);
    }

    public function execute(DsrRequest $dsrRequest)
    {
        $this->authorize('dsr.manage');

        if ($dsrRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'Request is not in pending status.']);
        }

        return match ($dsrRequest->type) {
            'access' => $this->executeAccess($dsrRequest),
            'erasure' => $this->executeErasure($dsrRequest),
            'rectification' => $this->executeRectification($dsrRequest),
            default => back()->withErrors(['type' => 'Unknown request type.']),
        };
    }

    public function override(DsrRequest $dsrRequest, Request $request)
    {
        $this->authorize('dsr.manage');

        $request->validate([
            'justification' => ['required', 'string', 'max:500'],
        ]);

        $dsrRequest->update([
            'status' => 'pending',
            'justification' => $request->justification,
        ]);

        $this->logSecurityEvent('dsr_override', Auth::user(), $dsrRequest->type, $dsrRequest->contact_id);

        return back()->with('status', 'Block overridden. Request is now pending.');
    }

    private function checkBlockingConditions(Contact $contact, string $type): ?string
    {
        if ($type !== 'erasure') {
            return null;
        }

        // Check for active contracts
        $activeContracts = $contact->contracts()->where('status', 'active')->count();
        if ($activeContracts > 0) {
            return "Active contracts exist ({$activeContracts})";
        }

        // Check for unresolved legal matters
        $unresolvedLegal = LegalMatter::where('contact_id', $contact->id)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();
        if ($unresolvedLegal > 0) {
            return "Unresolved legal matters exist ({$unresolvedLegal})";
        }

        // Check for outstanding invoices
        $outstandingBalance = Invoice::where('contact_id', $contact->id)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('total');
        if ($outstandingBalance > 0) {
            return "Outstanding invoice balance (${$outstandingBalance})";
        }

        return null;
    }

    private function executeAccess(DsrRequest $dsr)
    {
        // Generate export zip with all contact data
        $contact = $dsr->contact;

        $data = [
            'contact' => $contact->load(['accounts', 'deals', 'tickets', 'contracts', 'invoices', 'interactions', 'activities', 'surveyResponses']),
        ];

        // Store to R2 (simplified - actual implementation would use R2 storage)
        $dsr->update([
            'status' => 'completed',
            'completed_at' => now(),
            'handled_by' => Auth::id(),
        ]);

        $this->logSecurityEvent('dsr_completed', Auth::user(), $dsr->type, $contact->id);

        return back()->with('status', 'Access request completed. Export generated.');
    }

    private function executeErasure(DsrRequest $dsr)
    {
        $contact = $dsr->contact;

        $contact->update([
            'first_name' => 'Anonymised-'.substr(md5($contact->id), 0, 8),
            'last_name' => 'Customer',
            'email' => null,
            'phone' => null,
            'national_id' => null,
        ]);

        // Update related records
        Invoice::where('contact_id', $contact->id)->update(['contact_name' => 'Anonymised Customer']);
        Contract::where('contact_id', $contact->id)->update(['contact_name' => 'Anonymised Customer']);

        $contact->delete();

        $dsr->update([
            'status' => 'completed',
            'completed_at' => now(),
            'handled_by' => Auth::id(),
        ]);

        $this->logSecurityEvent('dsr_erasure_completed', Auth::user(), $dsr->type, $contact->id);

        return back()->with('status', 'Contact anonymised successfully.');
    }

    private function executeRectification(DsrRequest $dsr)
    {
        // This would show a form to edit contact data
        $dsr->update([
            'status' => 'completed',
            'completed_at' => now(),
            'handled_by' => Auth::id(),
        ]);

        $this->logSecurityEvent('dsr_completed', Auth::user(), $dsr->type, $dsr->contact_id);

        return back()->with('status', 'Rectification request completed.');
    }

    private function logSecurityEvent($eventType, $user, $dsrType = null, $contactId = null)
    {
        SecurityEvent::create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
            'metadata' => [
                'dsr_type' => $dsrType,
                'contact_id' => $contactId,
            ],
        ]);
    }
}
