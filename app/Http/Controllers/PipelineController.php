<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Pipeline;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(): Response
    {
        $pipelines = Pipeline::with('stages')->get();

        return Inertia::render('Pipelines/Index', [
            'pipelines' => $pipelines,
        ]);
    }

    public function showBoard(Pipeline $pipeline): Response
    {
        $stages = $pipeline->stages()->withCount('deals')->get();

        $deals = Deal::where('pipeline_id', $pipeline->id)
            ->with(['account', 'contact', 'owner'])
            ->get()
            ->groupBy('stage');

        $columns = $stages->map(function ($stage) use ($deals) {
            $stageDeals = $deals->get($stage->name, collect());

            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'probability' => $stage->probability,
                'deal_count' => $stageDeals->count(),
                'weighted_value' => $stageDeals->sum(fn ($d) => (float) $d->value * ($d->probability ?? $stage->probability) / 100),
                'total_value' => $stageDeals->sum('value'),
                'deals' => $stageDeals->map(function ($deal) {
                    return [
                        'id' => $deal->id,
                        'title' => $deal->title,
                        'account_name' => $deal->account->name,
                        'value' => $deal->value,
                        'expected_close_date' => $deal->expected_close_date,
                        'owner' => $deal->owner ? ['id' => $deal->owner->id, 'name' => $deal->owner->name] : null,
                    ];
                }),
            ];
        });

        $allPipelines = Pipeline::where('is_active', true)->get(['id', 'name', 'is_default']);

        return Inertia::render('Deals/Board', [
            'pipelines' => $allPipelines,
            'boardData' => [
                'pipeline' => $pipeline,
                'columns' => $columns,
            ],
        ]);
    }
}
