<?php

namespace App\Http\Controllers;

use App\Events\DealStageMoved;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Deal::query()->with(['account', 'owner', 'pipeline'])->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->pipeline_id);
        }

        $pipelines = Pipeline::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Deals/Index', [
            'deals' => $query->paginate(20),
            'pipelines' => $pipelines,
            'filters' => $request->only(['search', 'stage', 'pipeline_id']),
        ]);
    }

    public function create(): Response
    {
        $pipelines = Pipeline::with('stages')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $accounts = Account::select(['id', 'name'])->get();
        $contacts = Contact::select(['id', 'first_name', 'last_name'])->get();

        return Inertia::render('Deals/Form', [
            'pipelines' => $pipelines,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'preselectedContactId' => null,
            'preselectedAccountId' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'pipeline_id' => ['required', 'exists:pipelines,id'],
            'stage' => ['required', 'string'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $deal = Deal::create([
            'title' => $validated['title'],
            'contact_id' => $validated['contact_id'],
            'account_id' => $validated['account_id'],
            'pipeline_id' => $validated['pipeline_id'],
            'stage' => $validated['stage'],
            'value' => $validated['value'] ?? 0,
            'currency' => $validated['currency'] ?? 'USD',
            'probability' => 0,
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('deals.show', $deal)->with('success', 'Deal created successfully.');
    }

    public function show(Deal $deal): Response
    {
        $deal->load([
            'account',
            'contact',
            'owner',
            'pipeline.stages',
            'quotes' => function ($q) {
                $q->latest();
            },
            'activities' => function ($q) {
                $q->latest()->limit(20);
            },
            'demoTrials' => function ($q) {
                $q->latest();
            },
            'comments' => function ($q) {
                $q->latest()->with('user', 'mentions');
            },
        ]);

        $pipelines = Pipeline::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Deals/Show', [
            'deal' => $deal,
            'pipelines' => $pipelines,
        ]);
    }

    public function board(): Response
    {
        $defaultPipeline = Pipeline::where('is_active', true)->first();

        if (! $defaultPipeline) {
            return Inertia::render('Deals/Board', ['pipelines' => [], 'boardData' => null]);
        }

        return $this->showBoard($defaultPipeline);
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

        $allPipelines = Pipeline::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Deals/Board', [
            'pipelines' => $allPipelines,
            'boardData' => [
                'pipeline' => $pipeline,
                'columns' => $columns,
            ],
        ]);
    }

    public function moveStage(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $validated = $request->validate([
            'stage' => 'required|string',
        ]);

        $pipeline = $deal->pipeline;
        $stage = PipelineStage::where('pipeline_id', $pipeline->id)
            ->where('name', $validated['stage'])
            ->firstOrFail();

        $oldStage = $deal->stage;
        $deal->update([
            'stage' => $stage->name,
            'probability' => $stage->probability,
        ]);

        DealStageMoved::dispatch($deal, $oldStage, $stage->name);

        return back()->with('success', 'Deal moved successfully.');
    }

    public function quotes(): Response
    {
        return Inertia::render('Quotes/Index');
    }

    public function forecast(): Response
    {
        return Inertia::render('Analytics/Forecast');
    }
}
