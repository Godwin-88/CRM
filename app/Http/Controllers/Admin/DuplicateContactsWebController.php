<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\DuplicateDetectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DuplicateContactsWebController extends Controller
{
    public function __construct(
        protected DuplicateDetectionService $duplicateDetectionService,
    ) {}

    public function index(): Response
    {
        $contacts = Contact::with('owner')->orderByDesc('created_at')->paginate(25);

        return Inertia::render('Admin/Duplicates', [
            'contacts' => $contacts->through(fn ($c) => [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'owner' => $c->owner?->only(['id', 'name']),
            ]),
        ]);
    }
}
