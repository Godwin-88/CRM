<?php
  namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(): Response
    {
        $pipelines = Pipeline::with(['stages'])->orderBy('name')->get();

        return Inertia::render('Admin/Pipelines', [
            'pipelines' => $pipelines,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        $pipeline = Pipeline::create($request->all());

        return redirect()->route('admin.pipelines.index')->with('success', 'Pipeline created successfully.');
    }

    public function update(Request $request, Pipeline $pipeline)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        $pipeline->update($request->all());

        return redirect()->route('admin.pipelines.index')->with('success', 'Pipeline updated successfully.');
    }

    public function storeStage(Request $request, Pipeline $pipeline)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'probability' => 'required|integer|min:0|max:100',
            'position' => 'required|integer|min:0',
        ]);

        $pipeline->stages()->create($request->all());

        return back()->with('success', 'Stage created successfully.');
    }

    public function updateStage(Request $request, PipelineStage $stage)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'probability' => 'required|integer|min:0|max:100',
            'position' => 'required|integer|min:0',
        ]);

        $stage->update($request->all());

        return back()->with('success', 'Stage updated successfully.');
    }

    public function destroyStage(PipelineStage $stage)
    {
        $stage->delete();

        return back()->with('success', 'Stage deleted successfully.');
    }
}
