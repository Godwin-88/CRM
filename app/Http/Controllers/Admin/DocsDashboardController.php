<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocRequest;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocsDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin']);
    }

    public function index()
    {
        $staleArticles = KnowledgeBaseArticle::where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('last_verified_at')
                    ->orWhere('last_verified_at', '<', now()->subMonths(6));
            })
            ->with('category')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        $lowHelpfulnessArticles = KnowledgeBaseArticle::where('status', 'published')
            ->where('helpful_votes', '>', 0)
            ->get()
            ->filter(function ($article) {
                return $article->getHelpfulRatio() < 40;
            })
            ->take(20)
            ->values();

        $coverageGaps = $this->getCoverageGaps();

        $pendingRequests = DocRequest::whereNull('resolved_at')
            ->with('user')
            ->orderBy('request_count', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Docs/Dashboard', [
            'stale_articles' => $staleArticles,
            'low_helpfulness_articles' => $lowHelpfulnessArticles,
            'coverage_gaps' => $coverageGaps,
            'pending_requests' => $pendingRequests,
        ]);
    }

    public function resolveRequest(DocRequest $request)
    {
        $request->resolve();

        return back()->with('success', 'Request resolved.');
    }

    protected function getCoverageGaps(): array
    {
        $specSections = config('docs.spec_sections', []);

        $coveredSections = KnowledgeBaseArticle::where('status', 'published')
            ->whereNotNull('feature_refs')
            ->pluck('feature_refs')
            ->flatten()
            ->unique()
            ->toArray();

        $gaps = [];
        foreach ($specSections as $section => $features) {
            foreach ($features as $featureKey => $featureTitle) {
                if (!in_array($featureKey, $coveredSections)) {
                    $gaps[] = [
                        'section' => $section,
                        'feature' => $featureKey,
                        'title' => $featureTitle,
                    ];
                }
            }
        }

        return $gaps;
    }
}