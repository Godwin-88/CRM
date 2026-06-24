<?php

namespace App\Services;

use App\Models\AssistantLowConfidenceRoute;
use App\Models\DocRequest;
use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssistantIntentService
{
    private AssistantNavigationService $navigationService;

    public function __construct()
    {
        $this->navigationService = app(AssistantNavigationService::class);
    }

    public const HELP_NAVIGATE = 'navigate';
    public const HELP_EXPLAIN = 'explain';
    public const HELP_EXECUTE = 'execute';
    public const HELP_CLARIFY = 'clarify';

    private const FEATURE_KEYWORDS = [
        '4.1.1' => ['contact', 'contacts', 'account', 'accounts', 'customer', 'customers', 'lead', 'leads'],
        '4.1.3' => ['duplicate', 'duplicates', 'merge contacts'],
        '4.1.4' => ['duplicate detection', 'dedupe', 'deduplication'],
        '4.1.5' => ['timeline', 'history', 'activity timeline'],
        '4.1.6' => ['custom field', 'custom fields'],
        '4.1.7' => ['import', 'export', 'bulk import', 'bulk export'],
        '4.1.8' => ['scoring', 'score', 'lead score'],
        '4.2.1' => ['deal', 'deals', 'opportunity', 'opportunities', 'pipeline deal'],
        '4.2.2' => ['pipeline', 'kanban', 'board', 'stage'],
        '4.2.3' => ['deal automation', 'stage automation', 'automation'],
        '4.2.4' => ['win loss', 'win/loss', 'lost reason'],
        '4.2.5' => ['quote', 'quotes', 'proposal'],
        '4.2.6' => ['forecast', 'probability', 'revenue forecast'],
        '4.2.7' => ['deal comment', 'deal comments'],
        '4.3.1' => ['omni', 'omni-channel', 'dashboard'],
        '4.3.2' => ['inbox', 'interaction', 'interactions', 'unified inbox'],
        '4.3.3' => ['channel', 'channels'],
        '4.3.4' => ['unmatched', 'unmatched items'],
        '4.3.5' => ['contact center', 'queue stats', 'queue statistics'],
        '4.3.6' => ['kiosk'],
        '4.3.7' => ['email composer', 'compose email'],
        '4.3.8' => ['call log', 'call logging'],
        '4.3.9' => ['chat widget', 'chat inbox'],
        '4.3.10' => ['sms composer', 'compose sms'],
        '4.3.11' => ['queue stats', 'queue statistics'],
        '4.4.1' => ['campaign', 'campaigns'],
        '4.4.2' => ['campaign template', 'template'],
        '4.4.3' => ['email template', 'template editor'],
        '4.4.4' => ['multi-channel', 'builder'],
        '4.4.5' => ['a/b', 'ab test', 'test'],
        '4.4.6' => ['schedule', 'scheduled'],
        '4.4.7' => ['tag', 'tags'],
        '4.4.8' => ['campaign analytics'],
        '4.5.1' => ['loyalty', 'tier', 'tiers', 'points'],
        '4.5.2' => ['points ledger', 'ledger'],
        '4.5.3' => ['tier display'],
        '4.5.4' => ['survey', 'surveys'],
        '4.5.5' => ['survey response', 'responses'],
        '4.5.6' => ['kiosk interaction', 'journey', 'onboarding', 'reactivation'],
        '4.6.1' => ['ticket', 'tickets', 'support ticket', 'support'],
        '4.6.2' => ['knowledge base', 'kb', 'article', 'articles', 'documentation', 'docs'],
        '4.6.3' => ['merge ticket', 'split ticket'],
        '4.6.4' => ['internal note', 'internal notes'],
        '4.6.5' => ['canned response', 'canned responses'],
        '4.6.6' => ['csat', 'rating'],
        '4.6.7' => ['sla', 'breach', 'breached', 'sla policy'],
        '4.7.1' => ['analytics dashboard', 'dashboard'],
        '4.7.2' => ['customer analytics', 'clv analytics'],
        '4.7.3' => ['growth analytics', 'growth'],
        '4.7.4' => ['finance analytics', 'finance'],
        '4.7.5' => ['compliance analytics', 'compliance'],
        '4.7.6' => ['predictive scoring', 'churn risk'],
        '4.7.7' => ['report', 'reports', 'report builder'],
        '4.7.8' => ['exploratory analysis'],
        '4.7.9' => ['revenue forecast'],
        '4.7.10' => ['churn risk'],
        '4.7.11' => ['time bucket', 'time-bucket'],
        '4.8.1' => ['contract', 'contracts'],
        '4.8.2' => ['contract creation', 'create contract', 'generate contract'],
        '4.8.3' => ['legal', 'legal matter'],
        '4.8.4' => ['milestone', 'milestones'],
        '4.8.5' => ['renewal', 'renewals'],
        '4.8.6' => ['signature', 'e-sign', 'esign', 'signing'],
        '4.8.7' => ['contract repository'],
        '4.9.1' => ['invoice', 'invoices'],
        '4.9.2' => ['payment', 'payments', 'bank detail', 'vendor'],
        '4.9.3' => ['bank detail', 'bank details'],
        '4.9.4' => ['headcount', 'planning'],
        '4.9.5' => ['asset', 'assets'],
        '4.9.6' => ['procurement', 'approval', 'purchase order'],
        '4.9.7' => ['ledger summary', 'ledger'],
        '4.10.1' => ['mfa', 'two factor', '2fa'],
        '4.10.2' => ['security event', 'security events', 'audit event'],
        '4.10.3' => ['privileged', 'privileged session'],
        '4.10.4' => ['rbac', 'permission', 'permissions', 'role', 'roles', 'access control'],
        '4.10.5' => ['sso', 'single sign-on'],
        '4.10.6' => ['data classification', 'classified'],
        '4.10.7' => ['dsr', 'data subject', 'privacy request'],
        '4.11.1' => ['integration marketplace', 'connector', 'connectors'],
        '4.11.2' => ['api token', 'api tokens'],
        '4.11.3' => ['webhook', 'webhooks'],
        '4.11.4' => ['oauth', 'oauth2'],
        '4.11.5' => ['service registry'],
        '4.11.6' => ['rate limit', 'rate limiting'],
        '4.11.7' => ['openapi', 'api documentation'],
        '4.12.1' => ['calendar'],
        '4.12.2' => ['notification', 'notifications'],
        '4.12.3' => ['file', 'files', 'attachment', 'attachments'],
        '4.12.4' => ['discussion', 'discussions', 'discussion board'],
        '4.12.5' => ['team calendar'],
        '4.12.6' => ['mention', 'mentions', '@mention'],
        '4.15.1' => ['service catalog', 'catalog item', 'catalog items', 'service offering'],
        '4.15.2' => ['service request', 'service requests', 'support request', 'support requests', 'document request'],
        '4.15.3' => ['case record', 'case records', 'service case', 'service cases', 'complaint case', 'investigation case'],
    ];

    private const NAVIGATION_MAP = [
        '4.1.1' => '/contacts',
        '4.1.2' => '/accounts',
        '4.1.3' => '/admin/duplicates',
        '4.1.6' => '/admin/custom-fields',
        '4.1.8' => '/admin/scoring-rules',
        '4.2.1' => '/deals',
        '4.2.2' => '/deals/board',
        '4.2.3' => '/admin/deal-automations',
        '4.2.4' => '/admin/win-loss-reasons',
        '4.2.5' => '/admin/quote-templates',
        '4.3.2' => '/admin/interactions/inbox',
        '4.3.5' => '/admin/queue-stats',
        '4.3.9' => '/admin/chat/inbox',
        '4.4.1' => '/admin/campaigns',
        '4.4.6' => '/admin/campaigns',
        '4.4.8' => '/admin/analytics/campaigns-dashboard',
        '4.5.1' => '/admin/loyalty',
        '4.5.4' => '/admin/surveys',
        '4.6.1' => '/support/tickets',
        '4.6.2' => '/docs',
        '4.6.5' => '/admin/support/canned-responses',
        '4.6.7' => '/admin/sla',
        '4.7.1' => '/admin/analytics/dashboard',
        '4.7.7' => '/admin/analytics/report-builder',
        '4.8.1' => '/contracts',
        '4.8.3' => '/legal',
        '4.8.5' => '/contracts',
        '4.9.1' => '/invoices',
        '4.9.5' => '/assets',
        '4.10.1' => '/mfa',
        '4.10.2' => '/admin/security/events',
        '4.10.3' => '/admin/privileged',
        '4.10.4' => '/admin/rbac',
        '4.10.5' => '/admin/sso',
        '4.11.1' => '/admin/integrations/marketplace',
        '4.11.3' => '/admin/integrations/webhooks',
        '4.12.1' => '/calendar',
        '4.12.2' => '/notifications',
        '4.12.4' => '/discussions',
        '4.15.1' => '/service-catalog',
        '4.15.2' => '/service-requests',
        '4.15.3' => '/cases',
    ];

    private const TOOL_HINTS = [
        '4.1' => ['contacts.search', 'accounts.search'],
        '4.2' => ['deals.search', 'deals.move_stage', 'activities.create'],
        '4.3' => [],
        '4.4' => ['segments.preview_count'],
        '4.5' => ['loyalty.get_balance', 'clv.get_score'],
        '4.6' => ['tickets.search', 'tickets.create', 'tickets.update_status', 'kb.search'],
        '4.7' => ['dashboards.summary', 'analytics.metric', 'reports.run'],
        '4.8' => ['contracts.search', 'contracts.get_status'],
        '4.9' => ['invoices.search', 'invoices.get_ledger'],
        '4.10' => ['users.my_permissions'],
        '4.11' => ['integrations.get_status', 'webhooks.get_delivery_log'],
        '4.12' => ['notifications.get_unread', 'calendar.upcoming', 'comments.post'],
        '4.15' => ['services.search', 'services.get', 'service_requests.search', 'service_requests.create', 'service_requests.get_status', 'service_requests.update_status', 'service_requests.add_document_request', 'cases.search', 'cases.create', 'cases.get', 'cases.update_status', 'cases.add_note', 'cases.request_signoff'],
    ];

    public function systemPrompt(): string
    {
        $featureIndex = $this->featureIndex();

        return "You are the AI CRM Assistant embedded in the CRM. "
            ."Classify each user request into exactly one help_type: navigate, explain, or execute. "
            ."Use the structured feature index below as the authoritative map of CRM capabilities. "
            ."For ambiguous requests, ask one clarifying question with 2-3 concrete options instead of guessing. "
            ."For navigate responses, return a navigation object with a same-origin route or href and a label. "
            ."For explain responses, ground the answer in retrieved_documents and cite documentation links. "
            ."If retrieved_documents is empty or low relevance, say confidence is low, still provide the closest route, and mark low_confidence. "
            ."For execute responses, return tools_to_call only when the user has confirmed the action and the tool is available in available_tools. "
            ."Never bypass RBAC. If permission is required, explain who can help and provide a navigation link. "
            ."Cross-module requests must be decomposed into multiple intents and tools_to_call entries.\n\n"
            .'Feature index: '.json_encode($featureIndex, JSON_UNESCAPED_SLASHES);
    }

    public function featureIndex(): array
    {
        return config('docs.spec_sections', []);
    }

    public function retrieveDocumentsForAssistant(string $query, array $featureRefs, int $limit, ?User $user): array
    {
        return $this->retrieveDocuments($query, $featureRefs, $limit, $user);
    }

    public function analyze(string $message, array $context = [], ?User $user = null): array
    {
        $message = trim($message);
        $routeRefs = $this->featureRefsFromContext($context);
        $keywordRefs = $this->featureRefsFromMessage($message);
        $featureRefs = $this->uniqueValues(array_merge($routeRefs, $keywordRefs));
        $groups = $this->groupedBySection($featureRefs);
        $ambiguous = $this->isAmbiguous($message, $groups, $featureRefs);
        $clarifyingOptions = $ambiguous ? $this->clarifyingOptions($message, $groups, $featureRefs) : [];
        $intent = $this->resolveIntent($message, $featureRefs, $groups);
        $rawNavigation = $this->navigationService->analyze($featureRefs, $context, $user);
        $navigationRequiresClarification = isset($rawNavigation['disambiguation']);
        $helpType = ($ambiguous || $navigationRequiresClarification) ? self::HELP_CLARIFY : $intent;
        $documents = $this->retrieveDocuments($message, $featureRefs, 4, $user);

        if (empty($documents) && ! empty($featureRefs)) {
            $documents = $this->retrieveDocuments($message, [], 4, $user);
        }

        $confidence = $this->confidence($message, $featureRefs, $groups, $documents, $helpType, $ambiguous);
        $navigation = in_array($helpType, [self::HELP_NAVIGATE, self::HELP_EXECUTE], true) || $navigationRequiresClarification
            ? $rawNavigation
            : null;
        $quickReplies = $this->quickRepliesFor($helpType, $intent, $featureRefs, $navigation, $clarifyingOptions);
        $lowConfidence = ! $ambiguous
            && $helpType === self::HELP_EXPLAIN
            && (empty($documents) || $confidence < 0.6);
        $decomposedIntents = $this->decomposedIntents($groups, $featureRefs);

        return [
            'help_type' => $helpType,
            'intent' => $intent,
            'resolved_intent' => $this->resolvedIntentLabel($intent, $featureRefs),
            'confidence' => $confidence,
            'feature_refs' => $featureRefs,
            'feature_groups' => $groups,
            'decomposed_intents' => $decomposedIntents,
            'navigation' => $navigation,
            'quick_replies' => $quickReplies,
            'clarifying_options' => $clarifyingOptions,
            'articles' => $documents,
            'low_confidence' => $lowConfidence,
            'response' => $this->responseFor($helpType, $intent, $featureRefs, $documents, $navigation, $clarifyingOptions, $lowConfidence),
        ];
    }

    public function recordDocumentationGap(string $sessionId, string $query, string $resolvedIntent, float $confidence, ?User $user): void
    {
        AssistantLowConfidenceRoute::create([
            'session_id' => $sessionId,
            'user_id' => $user?->id,
            'user_query' => Str::limit($query, 1000),
            'resolved_intent' => Str::limit($resolvedIntent, 100),
            'confidence_score' => $confidence,
            'flagged' => true,
        ]);

        if ($user) {
            $request = DocRequest::firstOrCreate(
                ['screen_identifier' => 'assistant:'.$resolvedIntent, 'user_id' => $user->id],
                ['comment' => Str::limit($query, 1000)]
            );
            $request->incrementRequestCount();
        }
    }

    private function featureRefsFromContext(array $context): array
    {
        $route = (string) ($context['route'] ?? $context['current_route'] ?? $context['path'] ?? $context['current_screen'] ?? '');
        $path = (string) ($context['path'] ?? $context['url'] ?? '');
        $refs = [];

        foreach (config('docs.route_feature_map', []) as $pattern => $patternRefs) {
            if ($this->routeMatches($route, $pattern) || $this->routeMatches($path, $pattern)) {
                $refs = array_merge($refs, (array) $patternRefs);
            }
        }

        return $this->uniqueValues($refs);
    }

    private function featureRefsFromMessage(string $message): array
    {
        $text = ' '.Str::lower($message).' ';
        $refs = [];

        foreach (self::FEATURE_KEYWORDS as $featureRef => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, ' '.$keyword.' ') || str_contains($text, $keyword)) {
                    $refs[] = $featureRef;
                    break;
                }
            }
        }

        return $this->uniqueValues($refs);
    }

    private function routeMatches(string $route, string $pattern): bool
    {
        $route = ltrim(Str::lower($route), '/');
        $pattern = ltrim(Str::lower($pattern), '/');

        if ($route === $pattern) {
            return true;
        }

        $regex = '#^'.preg_replace(
            ['#/\*#', '#\{[^/]+\}#'],
            ['.*', '[^/]+'],
            preg_quote($pattern, '#')
        ).'(/.*)?$#';

        return (bool) preg_match($regex, $route);
    }

    private function resolveIntent(string $message, array $featureRefs, array $groups): string
    {
        $text = Str::lower($message);

        if (preg_match('/\b(create|update|move|post|send|generate|record|log|comment|change|set|execute|confirm)\b/', $text)) {
            return self::HELP_EXECUTE;
        }

        if (preg_match('/\b(open|show|take me|go to|navigate|find|where is|which screen|list|overview|recent)\b/', $text)) {
            return self::HELP_NAVIGATE;
        }

        if (preg_match('/\b(how|what|why|when|explain|define|what is|how do i|where do i)\b/', $text)) {
            return self::HELP_EXPLAIN;
        }

        if (! empty($groups)) {
            return self::HELP_NAVIGATE;
        }

        return self::HELP_EXPLAIN;
    }

    private function isAmbiguous(string $message, array $groups, array $featureRefs): bool
    {
        $text = Str::lower($message);

        if (str_contains($text, 'follow up') && count(array_intersect(array_keys($groups), ['4.2', '4.3', '4.4', '4.6', '4.12'])) >= 1) {
            return true;
        }

        if (str_contains($text, 'settings') && count(array_intersect(array_keys($groups), ['4.2', '4.6', '4.10', '4.11'])) >= 2) {
            return true;
        }

        if (str_contains($text, 'report') && count(array_intersect(array_keys($groups), ['4.2', '4.4', '4.7'])) >= 2) {
            return true;
        }

        if (count($groups) >= 3 && str_contains($text, ' and ')) {
            return true;
        }

        return false;
    }

    private function clarifyingOptions(string $message, array $groups, array $featureRefs): array
    {
        $text = Str::lower($message);

        if (str_contains($text, 'follow up')) {
            return [
                'Create a follow-up activity or task',
                'Send a campaign or drip follow-up',
                'Follow up on a support ticket',
            ];
        }

        if (str_contains($text, 'report')) {
            return [
                'Show deal or pipeline report',
                'Show campaign analytics report',
                'Open the report builder',
            ];
        }

        if (str_contains($text, 'settings')) {
            return [
                'Open RBAC settings',
                'Open integration settings',
                'Open SLA or ticket settings',
            ];
        }

        return [
            'Navigate to the screen',
            'Explain how it works',
            'Perform the action',
        ];
    }

    private function confidence(string $message, array $featureRefs, array $groups, array $documents, string $helpType, bool $ambiguous): float
    {
        if ($ambiguous) {
            return 0.45;
        }

        $score = 0.35;

        if (! empty($featureRefs)) {
            $score += min(0.25, 0.08 * count($featureRefs));
        }

        if (! empty($groups)) {
            $score += 0.1;
        }

        if (! empty($documents)) {
            $score += 0.2;
        }

        if ($this->hasExplicitIntentVerb($message, $helpType)) {
            $score += 0.1;
        }

        return round(min($score, 0.95), 2);
    }

    private function hasExplicitIntentVerb(string $message, string $helpType): bool
    {
        $text = Str::lower($message);

        return match ($helpType) {
            self::HELP_NAVIGATE => (bool) preg_match('/\b(open|show|take me|go to|navigate|find|where is|which screen|list|overview|recent)\b/', $text),
            self::HELP_EXPLAIN => (bool) preg_match('/\b(how|what|why|when|explain|define|what is|how do i|where do i)\b/', $text),
            self::HELP_EXECUTE => (bool) preg_match('/\b(create|update|move|post|send|generate|record|log|comment|change|set|execute|confirm)\b/', $text),
            default => false,
        };
    }

    private function navigationFor(array $featureRefs, array $context): ?array
    {
        foreach ($featureRefs as $featureRef) {
            if (! isset(self::NAVIGATION_MAP[$featureRef])) {
                continue;
            }

            return [
                'label' => 'Open '.$this->featureTitle($featureRef),
                'route' => self::NAVIGATION_MAP[$featureRef],
                'query' => $this->queryFromMessage($context['message'] ?? ''),
            ];
        }

        return null;
    }

    private function queryFromMessage(string $message): array
    {
        $text = Str::lower($message);
        $query = [];

        if (str_contains($text, 'overdue')) {
            $query['status'] = 'overdue';
        }

        if (str_contains($text, 'breach') || str_contains($text, 'breached')) {
            $query['sla'] = 'breached';
        }

        if (str_contains($text, 'high priority') || str_contains($text, 'priority high')) {
            $query['priority'] = 'high';
        }

        if (str_contains($text, 'open') && str_contains($text, 'ticket')) {
            $query['status'] = 'open';
        }

        return $query;
    }

    private function quickRepliesFor(string $helpType, string $intent, array $featureRefs, ?array $navigation, array $clarifyingOptions): array
    {
        if ($clarifyingOptions) {
            return $clarifyingOptions;
        }

        if ($helpType === self::HELP_NAVIGATE && $navigation) {
            return ['Open it', 'Explain this screen', 'Show related docs'];
        }

        if ($helpType === self::HELP_EXECUTE) {
            return ['Confirm action', 'Cancel', 'Show me the screen'];
        }

        if ($helpType === self::HELP_EXPLAIN) {
            return ['Open the screen', 'Show related docs', 'Ask a follow-up'];
        }

        if ($intent === self::HELP_NAVIGATE) {
            return ['Open it', 'Tell me more'];
        }

        if (in_array('4.6.1', $featureRefs, true)) {
            return ['Show breached tickets', 'Create a ticket', 'Open SLA settings'];
        }

        if (in_array('4.2.1', $featureRefs, true)) {
            return ['Show my deals', 'Move a deal stage', 'Open pipeline board'];
        }

        return [];
    }

    private function responseFor(string $helpType, string $intent, array $featureRefs, array $documents, ?array $navigation, array $clarifyingOptions, bool $lowConfidence): string
    {
        if ($helpType === self::HELP_CLARIFY) {
            if (! empty($navigation['disambiguation'])) {
                $labels = array_column($navigation['disambiguation'], 'label');
                return 'I found '.count($labels).' records that match. Which one would you like to open: '.implode(', ', $labels).'?';
            }

            return 'Just to make sure I get this right — are you trying to: '.implode(', ', $clarifyingOptions).'?';
        }

        $featureLabel = $featureRefs ? $this->featureTitle($featureRefs[0]) : 'the closest CRM area';
        $navLabel = $navigation['label'] ?? $featureLabel;
        $route = $navigation['route'] ?? null;

        if ($helpType === self::HELP_NAVIGATE) {
            if ($route && $navLabel) {
                return 'I can take you to your '.$navLabel.'. Just click the button below to open it.';
            }

            return 'I can take you to your '.$featureLabel.'.';
        }

        if ($helpType === self::HELP_EXECUTE) {
            return 'I can perform this for you. If you\'d like to proceed, confirm and I\'ll run it — just make sure you have the right permissions.';
        }

        if ($lowConfidence) {
            return 'This looks like it\'s about '.$featureLabel.', but I don\'t have detailed information on it yet. I\'ll still show you the closest screen and any relevant docs I can find.';
        }

        return 'I found some information that can help with this.';
    }

    private function retrieveDocuments(string $query, array $featureRefs, int $limit, ?User $user): array
    {
        $limit = max(1, min($limit, 10));
        $scoutResults = $this->retrieveWithScout($query, $featureRefs, $limit, $user);

        if ($scoutResults->isNotEmpty()) {
            return $scoutResults->all();
        }

        return $this->retrieveWithDatabase($query, $featureRefs, $limit, $user);
    }

    private function retrieveWithScout(string $query, array $featureRefs, int $limit, ?User $user): Collection
    {
        if (! config('scout.driver')) {
            return collect();
        }

        try {
            $results = KnowledgeBaseArticle::search($query)->take($limit * 3)->get();
        } catch (\Throwable) {
            return collect();
        }

        return $this->filterArticles($results, $featureRefs, $limit, $user);
    }

    private function retrieveWithDatabase(string $query, array $featureRefs, int $limit, ?User $user): array
    {
        $audience = $this->audienceForUser($user);
        $queryText = trim($query);

        $articles = KnowledgeBaseArticle::published()
            ->forAudience($audience)
            ->when($featureRefs, function ($q) use ($featureRefs) {
                $q->where(function ($query) use ($featureRefs) {
                    foreach ($featureRefs as $featureRef) {
                        $query->orWhereJsonContains('feature_refs', $featureRef);
                    }
                });
            })
            ->where(function ($q) use ($queryText, $featureRefs) {
                if ($queryText !== '') {
                    $q->where('title', 'like', '%'.$queryText.'%')
                        ->orWhere('body', 'like', '%'.$queryText.'%');
                }

                foreach ($featureRefs as $featureRef) {
                    $q->orWhereJsonContains('feature_refs', $featureRef);
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'category_id', 'published_at', 'feature_refs']);

        return $articles->load(['category', 'author'])->map(fn ($article) => $this->formatArticle($article))->all();
    }

    private function filterArticles(Collection $articles, array $featureRefs, int $limit, ?User $user): Collection
    {
        $audience = $this->audienceForUser($user);

        return $articles
            ->filter(fn ($article) => $article->status === 'published')
            ->filter(fn ($article) => in_array($article->audience, ['all', $audience], true))
            ->filter(function ($article) use ($featureRefs) {
                if (empty($featureRefs)) {
                    return true;
                }

                return ! empty(array_intersect((array) $article->feature_refs, $featureRefs));
            })
            ->take($limit)
            ->map(fn ($article) => $this->formatArticle($article->load(['category', 'author'])));
    }

    private function formatArticle(KnowledgeBaseArticle $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'snippet' => Str::limit(strip_tags($article->body), 240),
            'category' => $article->category?->name,
            'url' => '/docs/'.$article->slug,
            'published_at' => $article->published_at?->toIso8601String(),
            'feature_refs' => $article->feature_refs ?? [],
        ];
    }

    private function audienceForUser(?User $user): string
    {
        if (! $user) {
            return 'all';
        }

        foreach (['contact', 'agent', 'manager', 'admin'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return 'all';
    }

    private const SECTION_NAMES = [
        '4.1' => 'Contacts & Accounts',
        '4.2' => 'Deals & Pipelines',
        '4.3' => 'Omni-Channel',
        '4.4' => 'Campaigns',
        '4.5' => 'Loyalty & CX',
        '4.6' => 'Support',
        '4.7' => 'Analytics',
        '4.8' => 'Contracts & Legal',
        '4.9' => 'Finance & Procurement',
        '4.10' => 'Security',
        '4.11' => 'Integrations',
        '4.12' => 'Calendar & Notifications',
    ];

    private function decomposedIntents(array $groups, array $featureRefs): array
    {
        $intents = [];

        foreach (array_keys($groups) as $section) {
            $sectionRefs = array_values(array_filter($featureRefs, fn ($ref) => str_starts_with($ref, $section.'.')));
            $intents[] = [
                'section' => self::SECTION_NAMES[$section] ?? $section,
                'feature_refs' => $sectionRefs,
                'help_type' => $this->sectionDefaultHelpType($section),
                'possible_tools' => self::TOOL_HINTS[$section] ?? [],
            ];
        }

        return $intents;
    }

    private function sectionDefaultHelpType(string $section): string
    {
        return match ($section) {
            '4.6', '4.7', '4.9' => self::HELP_EXPLAIN,
            '4.2', '4.3', '4.4', '4.5', '4.8', '4.10', '4.11', '4.12' => self::HELP_NAVIGATE,
            default => self::HELP_EXPLAIN,
        };
    }

    private function groupedBySection(array $featureRefs): array
    {
        $groups = [];

        foreach ($featureRefs as $featureRef) {
            [$section] = explode('.', $featureRef, 2);
            $groups[$section][] = $featureRef;
        }

        foreach ($groups as $section => $refs) {
            $groups[$section] = array_values(array_unique($refs));
        }

        ksort($groups);

        return $groups;
    }

    private function featureTitle(string $featureRef): string
    {
        // feature_ref format is X.Y.Z (e.g. 4.4.1)
        // Extract section key (X.Y) from the feature ref by removing the last segment
        $parts = explode('.', $featureRef);
        $feature = $featureRef;

        if (count($parts) >= 3) {
            // Section is everything except the last segment (e.g. 4.4 from 4.4.1)
            $section = $parts[0].'.'.$parts[1];
        } elseif (count($parts) === 2) {
            $section = $featureRef;
            $feature = $parts[1];
        } else {
            $section = $featureRef;
            $feature = null;
        }

        $index = config('docs.spec_sections', []);

        // Look up in the feature index using section.feature format
        if (isset($index[$section]['features'][$featureRef])) {
            return $index[$section]['features'][$featureRef];
        }

        // Fallback to section title
        return $this->sectionTitle($section);
    }

    private function sectionTitle(string $sectionRef): string
    {
        $index = config('docs.spec_sections', []);

        return $index[$sectionRef]['title'] ?? $sectionRef;
    }

    private function featureTitleOrSection(string $featureRef): string
    {
        [$section] = array_pad(explode('.', $featureRef), 2, $featureRef);

        return $this->featureTitle($featureRef) !== $featureRef
            ? $this->featureTitle($featureRef)
            : $this->sectionTitle($section);
    }

    private function resolvedIntentLabel(string $intent, array $featureRefs): string
    {
        if (! empty($featureRefs)) {
            return $this->featureTitle($featureRefs[0]);
        }

        return ucfirst($intent);
    }

    private function uniqueValues(array $values): array
    {
        return array_values(array_unique(array_filter($values, 'is_string')));
    }
}
