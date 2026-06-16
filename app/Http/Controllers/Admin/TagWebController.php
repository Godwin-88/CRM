<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class TagWebController extends Controller
{
    public function index(): Response
    {
        $tags = Tag::orderBy('name')->limit(200)->get(['id', 'name', 'type', 'usage_count']);

        return Inertia::render('Admin/Tags', [
            'tags' => $tags,
        ]);
    }
}
