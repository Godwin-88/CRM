<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DealAutomation;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealAutomationWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager|admin');
    }

    public function index(): Response
    {
        $stages = PipelineStage::with('pipeline')->orderByDesc('created_at')->get();

        return Inertia::render('Admin/DealAutomations', [
            'stages' => $stages->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'pipeline' => $s->pipeline?->only(['id', 'name']),
            ]),
        ]);
    }
}
