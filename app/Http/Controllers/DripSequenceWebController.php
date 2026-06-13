<?php

namespace App\Http\Controllers;

use App\Models\DripSequence;
use Inertia\Inertia;
use Inertia\Response;

class DripSequenceWebController extends Controller
{
    public function index(): Response
    {
        $sequences = DripSequence::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('DripSequences/Index', [
            'sequences' => $sequences,
        ]);
    }
}
