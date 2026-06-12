<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use Inertia\Inertia;
use Inertia\Response;

class SocialPostWebController extends Controller
{
    public function index(): Response
    {
        $posts = SocialPost::orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Admin/SocialPosts', [
            'posts' => $posts,
        ]);
    }
}
