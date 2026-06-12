<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Segments/Index', [
            'segments' => Segment::all(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'criteria' => 'required|array',
        ]);
        
        Segment::create($data);
        
        return redirect()->route('segments.index');
    }
}
