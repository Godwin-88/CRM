<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomFieldWebController extends Controller
{
    public function index(): Response
    {
        $fields = CustomField::orderBy('entity_type')->orderBy('name')->get();

        return Inertia::render('Admin/CustomFields', [
            'fields' => $fields,
        ]);
    }
}
