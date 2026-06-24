<?php

namespace App\Services;

use App\Http\Controllers\Api\V1\AgentToolController;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\UnauthorizedException;

class AssistantNavigationService
{
    private const LABELS = [
        '/contacts' => 'Contacts',
        '/accounts' => 'Accounts',
        '/deals' => 'Deals',
        '/deals/create' => 'Create Deal',
        '/deals/board' => 'Pipeline Board',
        '/admin/pipelines' => 'Pipeline Setup',
        '/admin/deal-automations' => 'Deal Automations',
        '/admin/win-loss-reasons' => 'Win/Loss Reasons',
        '/admin/quote-templates' => 'Quote Templates',
        '/support/tickets' => 'Support Tickets',
        '/support/tickets/create' => 'Create Support Ticket',
        '/admin/support/canned-responses' => 'Canned Responses',
        '/admin/sla' => 'SLA Policies',
        '/admin/sla/instances' => 'SLA Instances',
        '/docs' => 'Knowledge Base',
        '/contracts' => 'Contracts',
        '/contracts/create' => 'Create Contract',
        '/legal' => 'Legal Matters',
        '/invoices' => 'Invoices',
        '/vendors' => 'Vendors',
        '/purchase-orders' => 'Purchase Orders',
        '/assets' => 'Assets',
        '/employees' => 'Employees',
        '/finance' => 'Finance Overview',
        '/admin/campaigns' => 'Campaigns',
        '/admin/campaign-templates' => 'Campaign Templates',
        '/admin/analytics/campaigns-dashboard' => 'Campaign Analytics',
        '/admin/loyalty' => 'Loyalty Programs',
        '/admin/surveys' => 'Surveys',
        '/admin/onboarding' => 'Onboarding & Journeys',
        '/admin/queue-stats' => 'Queue Statistics',
        '/admin/interactions/inbox' => 'Interaction Inbox',
        '/admin/chat/inbox' => 'Chat Inbox',
        '/admin/analytics/dashboard' => 'Analytics Dashboard',
        '/admin/analytics/report-builder' => 'Report Builder',
        '/admin/custom-fields' => 'Custom Fields',
        '/admin/duplicates' => 'Duplicate Management',
        '/admin/scoring-rules' => 'Scoring Rules',
        '/admin/rbac' => 'RBAC Matrix',
        '/admin/security/events' => 'Security Events',
        '/admin/privileged' => 'Privileged Sessions',
        '/admin/sso' => 'SSO Configuration',
        '/mfa' => 'MFA Setup',
        '/admin/integrations/marketplace' => 'Integration Marketplace',
        '/admin/integrations/webhooks' => 'Webhooks',
        '/admin/integrations' => 'Integrations',
        '/calendar' => 'Calendar',
        '/notifications' => 'Notifications',
        '/discussions' => 'Discussion Boards',
        '/admin/email/compose' => 'Email Composer',
        '/admin/sms/compose' => 'SMS Composer',
        '/admin/call/log' => 'Call Log',
        '/service-catalog' => 'Service Catalog',
        '/service-requests' => 'Service Requests',
        '/cases' => 'Cases',
    ];

    public function analyze(array $featureRefs, array $context = [], ?User $user = null): array
    {
        $message = $this->messageFromContext($context);
        $recordReference = $this->recordReference($message);

        if ($recordReference) {
            return $this->navigationForRecord($recordReference, $message, $user);
        }

        $target = $this->targetFromMessage($message, $featureRefs, $context);

        if (! $target) {
            return [
                'allowed' => false,
                'reason' => 'no_destination',
                'message' => 'I could not identify a CRM screen to open from that request.',
            ];
        }

        if (! $this->canAccessRoute($target['route'], $user)) {
            return $this->permissionDeniedNavigation($target['route']);
        }

        $query = $this->queryForRoute($target['route'], $message, $user);
        $prefill = $this->prefillForRoute($target['route'], $message, $user);
        $summary = $this->summaryForRoute($target['route'], $query, $prefill);

        return [
            'allowed' => true,
            'route' => $target['route'],
            'label' => $target['label'] ?? $this->labelForRoute($target['route']),
            'query' => $query,
            'prefill' => $prefill,
            'summary' => $summary,
        ];
    }

    private function messageFromContext(array $context): string
    {
        return trim((string) (
            $context['message']
            ?? $context['query']
            ?? $context['text']
            ?? $context['user_message']
            ?? ''
        ));
    }

    private function targetFromMessage(string $message, array $featureRefs, array $context): ?array
    {
        $text = Str::lower($message);
        $currentRoute = (string) ($context['route'] ?? $context['current_route'] ?? $context['path'] ?? $context['url'] ?? '');
        $currentPath = ltrim((string) ($context['path'] ?? $context['url'] ?? $currentRoute), '/');

        if (str_contains($text, 'this ticket') && preg_match('#^support/tickets/[^/]+#', $currentPath)) {
            return ['route' => '/'.$currentPath, 'label' => 'This Ticket'];
        }

        if (str_contains($text, 'this deal') && preg_match('#^deals/[^/]+#', $currentPath)) {
            return ['route' => '/'.$currentPath, 'label' => 'This Deal'];
        }

        if (str_contains($text, 'this account') && preg_match('#^accounts/[^/]+#', $currentPath)) {
            return ['route' => '/'.$currentPath, 'label' => 'This Account'];
        }

        if (str_contains($text, 'this contact') && preg_match('#^contacts/[^/]+#', $currentPath)) {
            return ['route' => '/'.$currentPath, 'label' => 'This Contact'];
        }

        if (str_contains($text, 'this contract') && preg_match('#^contracts/[^/]+#', $currentPath)) {
            return ['route' => '/'.$currentPath, 'label' => 'This Contract'];
        }

        if ($this->mentions($text, ['new pipeline', 'create pipeline', 'set up a new pipeline', 'setup a new pipeline', 'pipeline with stages', 'pipeline named', 'pipeline for'])) {
            return ['route' => '/admin/pipelines', 'label' => 'Create Pipeline'];
        }

        if ($this->mentions($text, ['new sla', 'create sla', 'sla policy', 'sla policy for', 'service level agreement'])) {
            return ['route' => '/admin/sla', 'label' => 'Create SLA Policy'];
        }

        if ($this->mentions($text, ['contract variables', 'fill variables', 'fill in the variables', 'variable fill'])) {
            return ['route' => '/contracts/create', 'label' => 'Contract Variable Fill'];
        }

        if ($this->mentions($text, ['contract template', 'nda template', 'select template'])) {
            return ['route' => '/contracts/create', 'label' => 'Contract Template Selection'];
        }

        if ($this->mentions($text, ['create ticket', 'new ticket', 'open ticket'])) {
            return ['route' => '/support/tickets/create', 'label' => 'Create Support Ticket'];
        }

        if ($this->mentions($text, ['create deal', 'new deal'])) {
            return ['route' => '/deals/create', 'label' => 'Create Deal'];
        }

        if ($this->mentions($text, ['pipeline board', 'kanban', 'deal board'])) {
            return ['route' => '/deals/board', 'label' => 'Pipeline Board'];
        }

        if ($this->mentions($text, ['sla instances', 'sla breaches', 'breached sla'])) {
            return ['route' => '/admin/sla/instances', 'label' => 'SLA Instances'];
        }

        if ($this->mentions($text, ['sla', 'breach', 'breached'])) {
            return ['route' => '/admin/sla', 'label' => 'SLA Policies'];
        }

        if ($this->mentions($text, ['ticket', 'support ticket', 'support'])) {
            return ['route' => '/support/tickets', 'label' => 'Support Tickets'];
        }

        if ($this->mentions($text, ['pipeline', 'pipeline stage', 'sales pipeline'])) {
            return ['route' => '/admin/pipelines', 'label' => 'Pipeline Setup'];
        }

        if ($this->mentions($text, ['deal', 'opportunity'])) {
            return ['route' => '/deals', 'label' => 'Deals'];
        }

        if ($this->mentions($text, ['contract'])) {
            return ['route' => '/contracts', 'label' => 'Contracts'];
        }

        if ($this->mentions($text, ['account'])) {
            return ['route' => '/accounts', 'label' => 'Accounts'];
        }

        if ($this->mentions($text, ['contact'])) {
            return ['route' => '/contacts', 'label' => 'Contacts'];
        }

        // Module: Campaigns (4.4)
        if ($this->mentions($text, ['campaign', 'campaigns', 'marketing campaign', 'email campaign'])) {
            if ($this->mentions($text, ['analytics', 'performance', 'stats', 'metrics'])) {
                return ['route' => '/admin/analytics/campaigns-dashboard', 'label' => 'Campaign Analytics'];
            }
            if ($this->mentions($text, ['template', 'templates'])) {
                return ['route' => '/admin/campaign-templates', 'label' => 'Campaign Templates'];
            }
            return ['route' => '/admin/campaigns', 'label' => 'Campaigns'];
        }

        // Module: Loyalty Programs (4.5)
        if ($this->mentions($text, ['loyalty', 'loyalty program', 'points program', 'rewards', 'tier'])) {
            return ['route' => '/admin/loyalty', 'label' => 'Loyalty Programs'];
        }

        // Module: Surveys (4.5.4)
        if ($this->mentions($text, ['survey', 'surveys', 'feedback'])) {
            return ['route' => '/admin/surveys', 'label' => 'Surveys'];
        }

        // Module: Knowledge Base / Docs (4.6.2)
        if ($this->mentions($text, ['knowledge base', 'kb', 'documentation', 'docs', 'help article', 'article'])) {
            return ['route' => '/docs', 'label' => 'Knowledge Base'];
        }

        // Module: Analytics (4.7)
        if ($this->mentions($text, ['analytics dashboard', 'crm dashboard', 'analytics overview'])) {
            return ['route' => '/admin/analytics/dashboard', 'label' => 'Analytics Dashboard'];
        }
        if ($this->mentions($text, ['report', 'report builder', 'custom report'])) {
            return ['route' => '/admin/analytics/report-builder', 'label' => 'Report Builder'];
        }

        // Module: Inbox / Omni-Channel (4.3)
        if ($this->mentions($text, ['inbox', 'interaction inbox', 'unified inbox'])) {
            return ['route' => '/admin/interactions/inbox', 'label' => 'Interaction Inbox'];
        }
        if ($this->mentions($text, ['contact center', 'queue stats', 'queue statistics'])) {
            return ['route' => '/admin/queue-stats', 'label' => 'Queue Statistics'];
        }
        if ($this->mentions($text, ['chat inbox', 'chat widget', 'live chat'])) {
            return ['route' => '/admin/chat/inbox', 'label' => 'Chat Inbox'];
        }

        // Module: Contracts & Legal (4.8)
        if ($this->mentions($text, ['legal', 'legal matter', 'litigation', 'case'])) {
            return ['route' => '/legal', 'label' => 'Legal Matters'];
        }
        if ($this->mentions($text, ['invoice', 'invoices', 'billing'])) {
            return ['route' => '/invoices', 'label' => 'Invoices'];
        }

        // Module: Assets (4.9.5)
        if ($this->mentions($text, ['asset', 'assets', 'equipment'])) {
            return ['route' => '/assets', 'label' => 'Assets'];
        }

        // Module: Integrations (4.11)
        if ($this->mentions($text, ['integration marketplace', 'connector', 'marketplace'])) {
            return ['route' => '/admin/integrations/marketplace', 'label' => 'Integration Marketplace'];
        }
        if ($this->mentions($text, ['webhook', 'webhooks'])) {
            return ['route' => '/admin/integrations/webhooks', 'label' => 'Webhooks'];
        }
        if ($this->mentions($text, ['integration', 'integrations', 'api token'])) {
            return ['route' => '/admin/integrations', 'label' => 'Integrations'];
        }

        // Module: Calendar & Notifications (4.12)
        if ($this->mentions($text, ['calendar', 'schedule', 'my calendar'])) {
            return ['route' => '/calendar', 'label' => 'Calendar'];
        }
        if ($this->mentions($text, ['notification', 'notifications', 'alerts'])) {
            return ['route' => '/notifications', 'label' => 'Notifications'];
        }

        // Module: Security (4.10)
        if ($this->mentions($text, ['mfa', 'two factor', '2fa', 'multi factor'])) {
            return ['route' => '/mfa', 'label' => 'MFA Setup'];
        }
        if ($this->mentions($text, ['rbac', 'permission', 'permissions', 'role', 'roles', 'access control'])) {
            return ['route' => '/admin/rbac', 'label' => 'RBAC Matrix'];
        }
        if ($this->mentions($text, ['security event', 'audit log', 'audit trail'])) {
            return ['route' => '/admin/security/events', 'label' => 'Security Events'];
        }
        if ($this->mentions($text, ['privileged session', 'privileged access'])) {
            return ['route' => '/admin/privileged', 'label' => 'Privileged Sessions'];
        }
        if ($this->mentions($text, ['sso', 'single sign-on'])) {
            return ['route' => '/admin/sso', 'label' => 'SSO Configuration'];
        }

        // Module: Service Catalog & Requests (4.15)
        if ($this->mentions($text, ['service catalog', 'catalog item', 'service offering'])) {
            return ['route' => '/service-catalog', 'label' => 'Service Catalog'];
        }
        if ($this->mentions($text, ['service request', 'service requests'])) {
            return ['route' => '/service-requests', 'label' => 'Service Requests'];
        }

        // Module: Onboarding & Journeys (4.5.6)
        if ($this->mentions($text, ['onboarding', 'journey', 'reactivation', 'kiosk interaction'])) {
            return ['route' => '/admin/onboarding', 'label' => 'Onboarding & Journeys'];
        }

        // Module: Email/SMS/Call Composer (4.3)
        if ($this->mentions($text, ['compose email', 'email composer', 'send email'])) {
            return ['route' => '/admin/email/compose', 'label' => 'Email Composer'];
        }
        if ($this->mentions($text, ['compose sms', 'sms composer', 'send sms'])) {
            return ['route' => '/admin/sms/compose', 'label' => 'SMS Composer'];
        }

        // Module: Custom Fields (4.1.6)
        if ($this->mentions($text, ['custom field', 'custom fields'])) {
            return ['route' => '/admin/custom-fields', 'label' => 'Custom Fields'];
        }

        // Map feature refs from the intent classifier to actual navigation routes
        if (! empty($featureRefs)) {
            $navMap = $this->featureRefNavigationMap();

            foreach ($featureRefs as $featureRef) {
                if (isset($navMap[$featureRef])) {
                    $nav = $navMap[$featureRef];

                    return ['route' => $nav['route'], 'label' => $nav['label']];
                }
            }
        }

        return null;
    }

    private function recordReference(string $message): ?array
    {
        $text = Str::lower($message);

        if (preg_match('/\b(ticket|deal|account|contact|contract)\s*(?:#|number|no\.?|id)?\s*[:#-]?\s*([a-z0-9_-]{4,})/i', $message, $match)) {
            return [
                'type' => $this->recordType($match[1]),
                'id' => $match[2],
                'raw' => $match[0],
            ];
        }

        if (preg_match('/\b([a-z0-9][a-z0-9 &.,\'-]{2,80}?)\s+(ticket|deal|account|contact|contract)\b/i', $message, $match)) {
            $name = trim(preg_replace('/\b(the|this|my|our|a|an)\b/i', '', $match[1]));

            if (in_array(Str::lower($name), ['open', 'show', 'me', 'the', 'this', 'my', 'new', 'create', 'find', 'list'], true)) {
                return null;
            }

            return [
                'type' => $this->recordType($match[2]),
                'name' => $name,
                'raw' => $match[0],
            ];
        }

        if (preg_match('/\b(ticket|deal)\s+(?:for|at|belonging to)\s+([a-z0-9][a-z0-9 &.,\'-]{2,80}?)(?:\s+(?:with|where|that|and|open|overdue|high|urgent|$))/i', $message, $match)) {
            return [
                'type' => $this->recordType($match[1]),
                'account_name' => $this->cleanReferenceName($match[2]),
                'raw' => $match[0],
            ];
        }

        if (preg_match('/\b([a-z0-9][a-z0-9 &.,\'-]{2,80}?)\'s\s+(deal|ticket|account|contact|contract)\b/i', $message, $match)) {
            return [
                'type' => $this->recordType($match[2]),
                'name' => $this->cleanReferenceName($match[1]),
                'raw' => $match[0],
            ];
        }

        return null;
    }

    private function recordType(string $value): string
    {
        return match (Str::lower($value)) {
            'ticket', 'support ticket' => 'ticket',
            'deal', 'opportunity' => 'deal',
            'account' => 'account',
            'contact' => 'contact',
            'contract' => 'contract',
            default => Str::lower($value),
        };
    }

    private function navigationForRecord(array $reference, string $message, ?User $user): array
    {
        $matches = $this->resolveRecordReference($reference, $user);

        if (isset($reference['id']) && empty($matches)) {
            return $this->permissionDeniedNavigation($this->routeForRecordType($reference['type']));
        }

        if (count($matches) > 1) {
            return [
                'allowed' => false,
                'reason' => 'disambiguation_required',
                'message' => 'I found multiple matching records. Choose the one you want to open.',
                'disambiguation' => $matches,
            ];
        }

        if (count($matches) === 1) {
            $match = $matches[0];

            if (empty($match['allowed'])) {
                return $this->permissionDeniedNavigation($match['route']);
            }

            return [
                'allowed' => true,
                'route' => $match['route'],
                'label' => 'Open '.$match['label'],
                'query' => [],
                'prefill' => [],
                'summary' => 'Opening '.$match['label'].'.',
            ];
        }

        $target = $this->targetFromMessage($message, [], []);

        if (! $target || ! $this->canAccessRoute($target['route'], $user)) {
            return $this->permissionDeniedNavigation($target['route'] ?? '/');
        }

        $query = $this->queryForRoute($target['route'], $message, $user);

        return [
            'allowed' => true,
            'route' => $target['route'],
            'label' => $target['label'] ?? $this->labelForRoute($target['route']),
            'query' => $query,
            'prefill' => [],
            'summary' => 'I could not identify a unique record, so I opened the closest list with your filters.',
        ];
    }

    private function resolveRecordReference(array $reference, ?User $user): array
    {
        $type = $reference['type'] ?? null;
        $query = $reference['id'] ?? $reference['name'] ?? $reference['account_name'] ?? '';
        $filters = Arr::only($reference, ['account_id', 'account_name', 'contact_id', 'contact_name', 'status', 'type', 'stage']);

        if (! $type || $query === '') {
            return [];
        }

        return app(AgentToolController::class)->resolveNavigationReference($user, $type, $query, $filters);
    }

    private function routeForRecordType(string $type): string
    {
        return match ($type) {
            'ticket' => '/support/tickets',
            'deal' => '/deals',
            'account' => '/accounts',
            'contact' => '/contacts',
            'contract' => '/contracts',
            default => '/',
        };
    }

    private function queryForRoute(string $route, string $message, ?User $user): array
    {
        $text = Str::lower($message);
        $reference = $this->recordReference($message);
        $query = [];

        if ($route === '/support/tickets' || $route === '/support/tickets/create') {
            if ($this->mentions($text, ['overdue', 'sla breach', 'breached', 'breach'])) {
                $query['sla'] = 'breached';
            }

            if ($this->mentions($text, ['open ticket', 'open tickets'])) {
                $query['status'] = 'open';
            }

            if ($this->mentions($text, ['high priority', 'priority high'])) {
                $query['priority'] = 'high';
            }

            if ($this->mentions($text, ['urgent'])) {
                $query['priority'] = 'urgent';
            }

            if ($reference && $reference['type'] === 'ticket' && ($reference['id'] ?? null)) {
                $query['search'] = $reference['id'];
            }

            $account = $this->extractReferenceFilter($reference, 'account', $message, $user);
            $contact = $this->extractReferenceFilter($reference, 'contact', $message, $user);

            if ($account) {
                $query['account_id'] = $account['id'];
                $query['account_name'] = $account['label'];
            }

            if ($contact) {
                $query['contact_id'] = $contact['id'];
                $query['contact_name'] = $contact['label'];
            }

            $search = $this->searchTerm($message, ['ticket', 'support ticket', 'support', 'subject']);
            if ($search && empty($query['search'])) {
                $query['search'] = $search;
            }
        }

        if ($route === '/deals' || $route === '/deals/create') {
            $account = $this->extractReferenceFilter($reference, 'account', $message, $user);
            $contact = $this->extractReferenceFilter($reference, 'contact', $message, $user);

            if ($account) {
                $query['account_id'] = $account['id'];
                $query['account_name'] = $account['label'];
            }

            if ($contact) {
                $query['contact_id'] = $contact['id'];
                $query['contact_name'] = $contact['label'];
            }

            $stage = $this->stageFromMessage($message);
            if ($stage) {
                $query['stage'] = $stage;
            }

            $search = $this->searchTerm($message, ['deal', 'opportunity']);
            if ($search && empty($query['search'])) {
                $query['search'] = $search;
            }
        }

        if ($route === '/admin/pipelines') {
            $prefill = $this->prefillForRoute($route, $message, $user);

            return array_filter([
                'assistant_prefill_name' => $prefill['name'] ?? null,
                'assistant_prefill_stages' => isset($prefill['stages']) ? implode(',', $prefill['stages']) : null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        if ($route === '/admin/sla') {
            $prefill = $this->prefillForRoute($route, $message, $user);

            return array_filter([
                'assistant_prefill_name' => $prefill['name'] ?? null,
                'assistant_prefill_priority' => $prefill['priority'] ?? null,
                'assistant_prefill_first_response' => $prefill['first_response_time_business_hours'] ?? null,
                'assistant_prefill_resolution' => $prefill['resolution_time_business_hours'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        if ($route === '/contracts') {
            $account = $this->extractReferenceFilter($reference, 'account', $message, $user);
            $contact = $this->extractReferenceFilter($reference, 'contact', $message, $user);

            if ($account) {
                $query['account_id'] = $account['id'];
                $query['account_name'] = $account['label'];
            }

            if ($contact) {
                $query['contact_id'] = $contact['id'];
                $query['contact_name'] = $contact['label'];
            }

            if ($this->mentions($text, ['nda'])) {
                $query['type'] = 'nda';
            }
        }

        if ($route === '/contracts/create') {
            $prefill = $this->prefillForRoute($route, $message, $user);
            $account = $this->extractReferenceFilter($reference, 'account', $message, $user);
            $contact = $this->extractReferenceFilter($reference, 'contact', $message, $user);

            $query = array_merge($query, array_filter([
                'step' => $prefill['step'] ?? null,
                'type' => $prefill['type'] ?? null,
                'account_id' => $account['id'] ?? null,
                'contact_id' => $contact['id'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return array_filter($query, fn ($value) => $value !== null && $value !== '');
    }

    private function prefillForRoute(string $route, string $message, ?User $user): array
    {
        $reference = $this->recordReference($message);
        $account = $reference ? $this->extractReferenceFilter($reference, 'account', $message, $user) : null;
        $contact = $reference ? $this->extractReferenceFilter($reference, 'contact', $message, $user) : null;

        if ($route === '/admin/pipelines') {
            return [
                'name' => $this->pipelineName($message) ?: 'Renewals',
                'stages' => $this->stagesFromMessage($message) ?: ['Contacted', 'Negotiating', 'Renewed'],
            ];
        }

        if ($route === '/admin/sla') {
            return [
                'name' => $this->slaName($message) ?: 'High Priority Tickets',
                'priority' => $this->priorityFromMessage($message) ?: 'high',
                'first_response_time_business_hours' => $this->hoursAfter($message, ['first response', 'first response time']) ?: 4,
                'resolution_time_business_hours' => $this->hoursAfter($message, ['resolution', 'resolution time']) ?: 24,
            ];
        }

        return [];
    }

    private function summaryForRoute(string $route, array $query, array $prefill): ?string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            if (str_starts_with($key, 'assistant_prefill_')) {
                continue;
            }
            $parts[] = Str::replace('_', ' ', $key).'='.Str::headline((string) $value);
        }

        $prefillLabels = [
            'name' => 'SLA Name',
            'priority' => 'Priority',
            'first_response_time_business_hours' => 'First Response',
            'resolution_time_business_hours' => 'Resolution',
        ];

        foreach ($prefill as $key => $value) {
            if (is_array($value)) {
                $parts[] = ($prefillLabels[$key] ?? Str::replace('_', ' ', $key)).'='.implode(', ', array_map('strval', $value));
                continue;
            }

            if ($value !== null && $value !== '') {
                $parts[] = ($prefillLabels[$key] ?? Str::replace('_', ' ', $key)).'='.Str::headline((string) $value);
            }
        }

        return $parts ? 'Prefill: '.implode('; ', $parts).'.' : null;
    }

    private function permissionDeniedNavigation(string $route): array
    {
        return [
            'allowed' => false,
            'reason' => 'permission_denied',
            'route' => $route,
            'label' => $this->labelForRoute($route),
            'message' => 'You do not have permission to open this screen. Your manager or an admin can help with this.',
            'who_can_access' => ['admin', 'manager'],
        ];
    }

    private function canAccessRoute(string $route, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        try {
            return match ($route) {
                '/contacts' => Gate::allows('viewAny', \App\Models\Contact::class),
                '/accounts' => Gate::allows('viewAny', \App\Models\Account::class),
                '/deals', '/deals/board' => Gate::allows('viewAny', Deal::class),
                '/deals/create' => Gate::allows('create', Deal::class),
                '/support/tickets' => Gate::allows('viewAny', Ticket::class),
                '/support/tickets/create' => Gate::allows('create', Ticket::class),
                '/contracts' => Gate::allows('viewAny', Contract::class),
                '/contracts/create' => Gate::allows('create', Contract::class),
                '/admin/pipelines' => Gate::allows('managePipeline', Deal::class),
                '/admin/sla', '/admin/sla/instances' => $user->hasAnyRole(['admin', 'manager']),
                default => true,
            };
        } catch (\Throwable) {
            return false;
        }
    }

    private function labelForRoute(string $route): string
    {
        return self::LABELS[$route] ?? 'CRM Screen';
    }

    private function mentions(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($text, Str::lower($phrase))) {
                return true;
            }
        }

        return false;
    }

    private function cleanReferenceName(string $value): string
    {
        return trim(preg_replace('/\b(ticket|deal|account|contact|contract|for|at|with|where|open|overdue|high|urgent)\b/i', '', $value));
    }

    private function extractReferenceFilter(?array $reference, string $type, string $message, ?User $user): ?array
    {
        if (! $reference) {
            return null;
        }

        $key = $type.'_id';

        if (isset($reference[$key])) {
            return [
                'id' => $reference[$key],
                'label' => $reference[$type.'_name'] ?? $reference[$key],
            ];
        }

        $lookupKey = $type.'_name';

        if (isset($reference[$lookupKey])) {
            return $this->firstAllowedReferenceMatch($type, $reference[$lookupKey], $user);
        }

        if (($reference['type'] ?? null) === $type && isset($reference['name'])) {
            return $this->firstAllowedReferenceMatch($type, $reference['name'], $user);
        }

        if (($reference['type'] ?? null) === 'ticket' && $type === 'account' && isset($reference['account_name'])) {
            return $this->firstAllowedReferenceMatch('account', $reference['account_name'], $user);
        }

        if (($reference['type'] ?? null) === 'deal' && $type === 'account' && isset($reference['account_name'])) {
            return $this->firstAllowedReferenceMatch('account', $reference['account_name'], $user);
        }

        if ($this->mentions(Str::lower($message), [$type.' ']) && preg_match('/\b'.$type.'\s+(?:for\s+)?([a-z0-9][a-z0-9 &.,\'-]{2,80}?)(?:\s+(?:with|where|that|and|open|overdue|high|urgent|$))/i', $message, $match)) {
            return $this->firstAllowedReferenceMatch($type, $this->cleanReferenceName($match[1]), $user);
        }

        return null;
    }

    private function firstAllowedReferenceMatch(string $type, string $name, ?User $user): ?array
    {
        $matches = app(AgentToolController::class)->resolveNavigationReference($user, $type, $name, []);

        return $matches[0] ?? null;
    }

    private function searchTerm(string $message, array $stopWords): string
    {
        $text = Str::lower($message);
        $words = preg_split('/\s+/', trim($message));
        $filtered = array_values(array_filter($words, fn ($word) => ! in_array(Str::lower($word), $stopWords, true)
            && ! in_array(Str::lower($word), ['show', 'me', 'open', 'the', 'for', 'with', 'where', 'that', 'and', 'to'], true)));

        if (! $filtered) {
            return '';
        }

        if (str_contains($text, ' for ')) {
            [$before] = explode(' for ', $text, 2);
            $beforeWords = preg_split('/\s+/', trim($before));
            $beforeWords = array_values(array_filter($beforeWords, fn ($word) => ! in_array(Str::lower($word), $stopWords, true)
                && ! in_array(Str::lower($word), ['show', 'me', 'open', 'the'], true)));

            return implode(' ', $beforeWords) ?: implode(' ', $filtered);
        }

        return implode(' ', array_slice($filtered, 0, 4));
    }

    private function pipelineName(string $message): string
    {
        if (preg_match('/(?:new|create|setup|set up)\s+(?:a\s+)?pipeline\s+(?:for|named|called)?\s*[:#-]?\s*([a-z0-9][a-z0-9 &.,\'-]{2,80}?)(?:\s+(?:with|using|stages|stage|$))/i', $message, $match)) {
            return Str::title(trim($match[1]));
        }

        return '';
    }

    private function stagesFromMessage(string $message): array
    {
        if (! preg_match('/stages?\s*[:#-]?\s*([a-z0-9][a-z0-9 &.,\'-]{2,160}?)(?:\.|$)/i', $message, $match)) {
            return [];
        }

        $stages = preg_split('/\s*,\s*|\s+and\s+|\s*;\s*/i', trim($match[1]));

        return array_values(array_filter(array_map(fn ($stage) => Str::title(trim($stage)), $stages), fn ($stage) => $stage !== ''));
    }

    private function stageFromMessage(string $message): string
    {
        if (preg_match('/stage\s+(?:is|named|called|to)?\s*[:#-]?\s*([a-z0-9_ -]{2,80}?)(?:\.|,|\s+with|\s+for|$)/i', $message, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private function slaName(string $message): string
    {
        if (preg_match('/sla\s+(?:for|named|called)?\s*[:#-]?\s*([a-z0-9][a-z0-9 &.,\'-]{2,80}?)(?:\s+(?:with|where|that|and|priority|$))/i', $message, $match)) {
            return Str::title(trim($match[1]));
        }

        return '';
    }

    private function priorityFromMessage(string $message): string
    {
        $text = Str::lower($message);

        if (str_contains($text, 'urgent')) {
            return 'urgent';
        }

        if (str_contains($text, 'critical')) {
            return 'critical';
        }

        if (str_contains($text, 'high priority') || str_contains($text, 'priority high')) {
            return 'high';
        }

        if (str_contains($text, 'medium priority') || str_contains($text, 'priority medium')) {
            return 'medium';
        }

        if (str_contains($text, 'low priority') || str_contains($text, 'priority low')) {
            return 'low';
        }

        return '';
    }

    private function hoursAfter(string $message, array $phrases): ?int
    {
        $text = Str::lower($message);

        foreach ($phrases as $phrase) {
            $position = strpos($text, $phrase);

            if ($position === false) {
                continue;
            }

            $tail = substr($text, $position + strlen($phrase));

            if (preg_match('/\b(\d+(?:\.\d+)?)\s*(hour|hrs|hr|hours)\b/', $tail, $match)) {
                return max(1, (int) round((float) $match[1]));
            }
        }

        return null;
    }

    private function ticketSubject(string $message): string
    {
        if (preg_match('/ticket\s+(?:for|about|regarding|named)?\s*[:#-]?\s*([a-z0-9][a-z0-9 &.,\'-]{4,120}?)(?:\s+(?:for|with|where|that|and|open|overdue|high|urgent|$))/i', $message, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private function dealTitle(string $message): string
    {
        if (preg_match('/deal\s+(?:for|with|named|called)?\s*[:#-]?\s*([a-z0-9][a-z0-9 &.,\'-]{4,120}?)(?:\s+(?:for|with|where|that|and|open|high|urgent|$))/i', $message, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    /**
     * Map feature references (from the intent classifier) to navigation routes and labels.
     * This allows routing by intent when the keyword-based matching doesn't apply.
     */
    private function featureRefNavigationMap(): array
    {
        return [
            // 4.1 Contacts & Accounts
            '4.1.1' => ['route' => '/contacts', 'label' => 'Contacts'],
            '4.1.2' => ['route' => '/accounts', 'label' => 'Accounts'],
            '4.1.3' => ['route' => '/admin/duplicates', 'label' => 'Duplicate Management'],
            '4.1.4' => ['route' => '/admin/duplicates', 'label' => 'Duplicate Detection'],
            '4.1.5' => ['route' => '/contacts', 'label' => 'Timeline View'],
            '4.1.6' => ['route' => '/admin/custom-fields', 'label' => 'Custom Fields'],
            '4.1.7' => ['route' => '/contacts', 'label' => 'Bulk Import/Export'],
            '4.1.8' => ['route' => '/admin/scoring-rules', 'label' => 'Scoring Rules'],
            // 4.2 Deals & Pipelines
            '4.2.1' => ['route' => '/deals', 'label' => 'Deals'],
            '4.2.2' => ['route' => '/deals/board', 'label' => 'Pipeline Board'],
            '4.2.3' => ['route' => '/admin/deal-automations', 'label' => 'Deal Automations'],
            '4.2.4' => ['route' => '/admin/win-loss-reasons', 'label' => 'Win/Loss Reasons'],
            '4.2.5' => ['route' => '/admin/quote-templates', 'label' => 'Quote Templates'],
            '4.2.6' => ['route' => '/deals', 'label' => 'Forecast'],
            '4.2.7' => ['route' => '/deals', 'label' => 'Deal Comments'],
            // 4.3 Omni-Channel
            '4.3.1' => ['route' => '/admin/omni/dashboard', 'label' => 'Omni-Channel Dashboard'],
            '4.3.2' => ['route' => '/admin/interactions/inbox', 'label' => 'Interaction Inbox'],
            '4.3.3' => ['route' => '/admin/interactions/channels', 'label' => 'Channels Configuration'],
            '4.3.4' => ['route' => '/admin/interactions/unmatched', 'label' => 'Unmatched Items'],
            '4.3.5' => ['route' => '/admin/queue-stats', 'label' => 'Contact Center'],
            '4.3.6' => ['route' => '/admin/field-channel', 'label' => 'Kiosk'],
            '4.3.7' => ['route' => '/admin/email/compose', 'label' => 'Email Composer'],
            '4.3.8' => ['route' => '/admin/call/log', 'label' => 'Call Log'],
            '4.3.9' => ['route' => '/admin/chat/inbox', 'label' => 'Chat Inbox'],
            '4.3.10' => ['route' => '/admin/sms/compose', 'label' => 'SMS Composer'],
            '4.3.11' => ['route' => '/admin/queue-stats', 'label' => 'Queue Stats'],
            // 4.4 Campaigns
            '4.4.1' => ['route' => '/admin/campaigns', 'label' => 'Campaigns'],
            '4.4.2' => ['route' => '/admin/campaign-templates', 'label' => 'Campaign Templates'],
            '4.4.3' => ['route' => '/admin/campaign-templates', 'label' => 'Email Template Editor'],
            '4.4.4' => ['route' => '/admin/social-posts', 'label' => 'Multi-Channel Builder'],
            '4.4.5' => ['route' => '/admin/campaigns', 'label' => 'A/B Testing'],
            '4.4.6' => ['route' => '/admin/drip-sequences', 'label' => 'Schedule Controls'],
            '4.4.7' => ['route' => '/admin/tags', 'label' => 'Tag Management'],
            '4.4.8' => ['route' => '/admin/analytics/campaigns-dashboard', 'label' => 'Campaign Analytics'],
            // 4.5 Loyalty & CX
            '4.5.1' => ['route' => '/admin/loyalty', 'label' => 'Loyalty Programs'],
            '4.5.2' => ['route' => '/admin/loyalty/ledger', 'label' => 'Points Ledger'],
            '4.5.3' => ['route' => '/admin/loyalty', 'label' => 'Tier Display'],
            '4.5.4' => ['route' => '/admin/surveys', 'label' => 'Surveys'],
            '4.5.5' => ['route' => '/admin/surveys/responses', 'label' => 'Survey Responses'],
            '4.5.6' => ['route' => '/admin/onboarding', 'label' => 'Kiosk Interactions'],
            // 4.6 Support
            '4.6.1' => ['route' => '/support/tickets', 'label' => 'Support Tickets'],
            '4.6.2' => ['route' => '/docs', 'label' => 'Knowledge Base'],
            '4.6.3' => ['route' => '/support/tickets', 'label' => 'Ticket Merge/Split'],
            '4.6.4' => ['route' => '/support/tickets', 'label' => 'Internal Notes'],
            '4.6.5' => ['route' => '/admin/support/canned-responses', 'label' => 'Canned Responses'],
            '4.6.6' => ['route' => '/admin/support', 'label' => 'CSAT Ratings'],
            '4.6.7' => ['route' => '/admin/sla', 'label' => 'SLA Configuration'],
            // 4.7 Analytics
            '4.7.1' => ['route' => '/admin/analytics/dashboard', 'label' => 'Analytics Dashboard'],
            '4.7.2' => ['route' => '/admin/analytics/customer', 'label' => 'Customer Analytics'],
            '4.7.3' => ['route' => '/admin/analytics/growth', 'label' => 'Growth Analytics'],
            '4.7.4' => ['route' => '/admin/analytics/finance', 'label' => 'Finance Analytics'],
            '4.7.5' => ['route' => '/admin/analytics/compliance', 'label' => 'Compliance Analytics'],
            '4.7.6' => ['route' => '/admin/analytics/predictive-scoring', 'label' => 'Predictive Scoring'],
            '4.7.7' => ['route' => '/admin/analytics/report-builder', 'label' => 'Report Builder'],
            '4.7.8' => ['route' => '/admin/analytics/report-builder', 'label' => 'Exploratory Analysis'],
            '4.7.9' => ['route' => '/admin/analytics/dashboard', 'label' => 'Revenue Forecast'],
            '4.7.10' => ['route' => '/admin/analytics/predictive-scoring', 'label' => 'Churn Risk'],
            '4.7.11' => ['route' => '/admin/analytics/dashboard', 'label' => 'Time-Bucketed Forecast'],
            // 4.8 Contracts & Legal
            '4.8.1' => ['route' => '/contracts', 'label' => 'Contracts'],
            '4.8.2' => ['route' => '/contracts/create', 'label' => 'Contract Creation'],
            '4.8.3' => ['route' => '/legal', 'label' => 'Legal Matters'],
            '4.8.4' => ['route' => '/legal', 'label' => 'Milestone Tracking'],
            '4.8.5' => ['route' => '/contracts', 'label' => 'Renewal Reminders'],
            '4.8.6' => ['route' => '/contracts', 'label' => 'E-Signature Workflow'],
            '4.8.7' => ['route' => '/contracts', 'label' => 'Contract Repository'],
            // 4.9 Finance & Procurement
            '4.9.1' => ['route' => '/invoices', 'label' => 'Invoices'],
            '4.9.2' => ['route' => '/vendors', 'label' => 'Payment Recording'],
            '4.9.3' => ['route' => '/vendors', 'label' => 'Bank Details'],
            '4.9.4' => ['route' => '/employees', 'label' => 'Headcount Planning'],
            '4.9.5' => ['route' => '/assets', 'label' => 'Asset Management'],
            '4.9.6' => ['route' => '/purchase-orders', 'label' => 'Procurement Approval'],
            '4.9.7' => ['route' => '/finance', 'label' => 'Ledger Summary'],
            // 4.10 Security
            '4.10.1' => ['route' => '/mfa', 'label' => 'MFA'],
            '4.10.2' => ['route' => '/admin/security/events', 'label' => 'Security Events'],
            '4.10.3' => ['route' => '/admin/privileged', 'label' => 'Privileged Sessions'],
            '4.10.4' => ['route' => '/admin/rbac', 'label' => 'RBAC Matrix'],
            '4.10.5' => ['route' => '/admin/sso', 'label' => 'SSO Configuration'],
            '4.10.6' => ['route' => '/admin', 'label' => 'Data Classification'],
            '4.10.7' => ['route' => '/admin/dsr', 'label' => 'DSR Module'],
            // 4.11 Integrations
            '4.11.1' => ['route' => '/admin/integrations/marketplace', 'label' => 'Integration Marketplace'],
            '4.11.2' => ['route' => '/admin/api-tokens', 'label' => 'API Tokens'],
            '4.11.3' => ['route' => '/admin/integrations/webhooks', 'label' => 'Webhooks'],
            '4.11.4' => ['route' => '/admin/oauth-clients', 'label' => 'OAuth2 Authorization'],
            '4.11.5' => ['route' => '/admin/integrations', 'label' => 'Service Registry'],
            '4.11.6' => ['route' => '/admin/integrations', 'label' => 'Rate Limit Configuration'],
            '4.11.7' => ['route' => '/admin/integrations', 'label' => 'OpenAPI Documentation'],
            // 4.12 Calendar & Notifications
            '4.12.1' => ['route' => '/calendar', 'label' => 'Calendar'],
            '4.12.2' => ['route' => '/notifications', 'label' => 'Notifications'],
            '4.12.3' => ['route' => '/notifications', 'label' => 'File Attachments'],
            '4.12.4' => ['route' => '/discussions', 'label' => 'Discussion Boards'],
            '4.12.5' => ['route' => '/calendar', 'label' => 'Team Calendar'],
            '4.12.6' => ['route' => '/notifications', 'label' => 'Mentions'],
            // 4.15 Service & Support
            '4.15.1' => ['route' => '/service-catalog', 'label' => 'Service Catalog'],
            '4.15.2' => ['route' => '/service-requests', 'label' => 'Service Requests'],
            '4.15.3' => ['route' => '/cases', 'label' => 'Cases'],
        ];
    }
}
