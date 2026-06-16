<?php

use App\Http\Controllers\Admin\QuoteTemplateController;
use App\Http\Controllers\Admin\ScoringRuleController;
use App\Http\Controllers\Admin\WinLossReasonController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AnalyticsApiController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\CampaignAnalyticsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CampaignTemplateController;
use App\Http\Controllers\Api\V1\CannedResponseController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ComplianceAnalyticsController;
use App\Http\Controllers\Api\V1\ContactCentreController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CustomFieldController;
use App\Http\Controllers\Api\V1\DripSequenceController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\InteractionController;
use App\Http\Controllers\Api\V1\KioskController;
use App\Http\Controllers\Api\V1\KnowledgeBaseCategoryController;
use App\Http\Controllers\Api\V1\KnowledgeBaseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PipelineController;
use App\Http\Controllers\Api\V1\ReportBuilderController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\ServiceRegistryController;
use App\Http\Controllers\Api\V1\SlaController;
use App\Http\Controllers\Api\V1\SocialPostController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketCategoryController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TranslationController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\LegalMatterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Contact Routes
    Route::get('contacts', [ContactController::class, 'index']);
    Route::post('contacts', [ContactController::class, 'store']);
    Route::get('contacts/{contact}', [ContactController::class, 'show']);
    Route::put('contacts/{contact}', [ContactController::class, 'update']);
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);

    Route::post('contacts/check-duplicates', [ContactController::class, 'checkDuplicates']);
    Route::post('contacts/merge', [ContactController::class, 'merge']);
    Route::post('contacts/bulk-delete', [ContactController::class, 'bulkDelete']);
    Route::get('contacts/{id}/timeline', [ContactController::class, 'timeline']);
    Route::get('contacts/{id}/deals', [ContactController::class, 'deals']);
    Route::get('contacts/{id}/tickets', [ContactController::class, 'tickets']);
    Route::post('contacts/import', [ContactController::class, 'import']);
    Route::get('contacts/export', [ContactController::class, 'export']);
    Route::post('contacts/{id}/link-account', [ContactController::class, 'linkAccount']);
    Route::post('contacts/{id}/unlink-account', [ContactController::class, 'unlinkAccount']);

    // Account Routes
    Route::get('accounts', [AccountController::class, 'index']);
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);
    Route::delete('accounts/{account}', [AccountController::class, 'destroy']);

    Route::get('accounts/{account}/contacts', [AccountController::class, 'getAccountContacts']);
    Route::post('accounts/{account}/primary-contact', [AccountController::class, 'setPrimaryContact']);

    // Segment Routes
    Route::get('segments', [SegmentController::class, 'index']);
    Route::post('segments', [SegmentController::class, 'store']);
    Route::get('segments/{segment}', [SegmentController::class, 'show']);
    Route::put('segments/{segment}', [SegmentController::class, 'update']);
    Route::delete('segments/{segment}', [SegmentController::class, 'destroy']);

    Route::post('segments/preview', [SegmentController::class, 'preview']);
    Route::get('segments/{segment}/preview', [SegmentController::class, 'previewSegment']);

    // Scoring Rules (Admin)
    Route::get('scoring-rules', [ScoringRuleController::class, 'index']);
    Route::post('scoring-rules', [ScoringRuleController::class, 'store']);
    Route::put('scoring-rules/{scoringRule}', [ScoringRuleController::class, 'update']);
    Route::delete('scoring-rules/{scoringRule}', [ScoringRuleController::class, 'destroy']);
    Route::patch('scoring-rules/{scoringRule}/toggle', [ScoringRuleController::class, 'toggle']);

    // Deal & Pipeline Routes
    Route::get('deals', [DealController::class, 'index']);
    Route::post('deals', [DealController::class, 'store']);
    Route::get('deals/{deal}', [DealController::class, 'show']);
    Route::put('deals/{deal}', [DealController::class, 'update']);
    Route::patch('deals/{deal}/stage', [DealController::class, 'moveStage']);
    Route::post('deals/{deal}/activities', [DealController::class, 'addActivity']);
    Route::post('deals/{deal}/comments', [DealController::class, 'addComment']);
    Route::post('deals/{deal}/demo-trial', [DealController::class, 'scheduleDemoTrial']);
    Route::post('deals/{deal}/close', [DealController::class, 'closeDeal']);

    Route::get('pipelines', [PipelineController::class, 'index']);
    Route::get('pipelines/{pipeline}', [PipelineController::class, 'show']);
    Route::get('pipelines/{pipeline}/board', [PipelineController::class, 'board']);

    // Analytics
    Route::get('analytics/forecast', [AnalyticsController::class, 'forecast']);
    Route::get('analytics/win-loss', [AnalyticsController::class, 'winLossAnalysis']);
    Route::get('analytics/dashboard', [AnalyticsApiController::class, 'dashboard']);
    Route::get('analytics/growth', [AnalyticsApiController::class, 'growthMetrics']);
    Route::get('analytics/finance', [AnalyticsApiController::class, 'financeMetrics']);
    Route::get('analytics/deal-score/{deal}', [AnalyticsApiController::class, 'dealScore']);
    Route::get('analytics/campaign-performance', [CampaignAnalyticsController::class, 'performance']);
    Route::get('analytics/campaign-time-series/{campaign}', [CampaignAnalyticsController::class, 'timeSeries']);
    Route::get('analytics/campaign-per-contact/{campaign}', [CampaignAnalyticsController::class, 'perContact']);
    Route::get('analytics/campaign-per-link/{campaign}', [CampaignAnalyticsController::class, 'perLink']);

    // Reports
    Route::get('reports', [ReportBuilderController::class, 'index']);
    Route::post('reports', [ReportBuilderController::class, 'store']);
    Route::get('reports/{report}', [ReportBuilderController::class, 'show']);
    Route::put('reports/{report}', [ReportBuilderController::class, 'update']);
    Route::delete('reports/{report}', [ReportBuilderController::class, 'destroy']);
    Route::post('reports/{report}/schedule', [ReportBuilderController::class, 'schedule']);

    // Compliance
    Route::get('audit-trail', [ComplianceAnalyticsController::class, 'auditTrail']);
    Route::get('audit-stats', [ComplianceAnalyticsController::class, 'auditStats']);
    Route::get('audit-anomalies', [ComplianceAnalyticsController::class, 'anomalies']);

    // Contracts
    Route::get('contracts', [ContractController::class, 'indexApi']);
    Route::get('contracts/{contract}', [ContractController::class, 'showApi']);

    // Legal Matters
    Route::get('legal', [LegalMatterController::class, 'indexApi']);
    Route::get('legal/{legalMatter}', [LegalMatterController::class, 'show']);

    // Support Routes
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::put('tickets/{ticket}', [TicketController::class, 'update']);
    Route::delete('tickets/{ticket}', [TicketController::class, 'destroy']);
    Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
    Route::post('tickets/{ticket}/escalate', [TicketController::class, 'escalate']);
    Route::post('tickets/{ticket}/resolve', [TicketController::class, 'resolve']);
    Route::post('tickets/{ticket}/close', [TicketController::class, 'close']);
    Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen']);
    Route::post('tickets/{ticket}/merge', [TicketController::class, 'merge']);
    Route::post('tickets/{ticket}/split', [TicketController::class, 'split']);
    Route::post('tickets/{ticket}/notes', [TicketController::class, 'addNote']);
    Route::post('tickets/{ticket}/link-article', [TicketController::class, 'linkArticle']);
    Route::get('tickets/breached', [TicketController::class, 'breached']);

    Route::get('ticket-categories', [TicketCategoryController::class, 'index']);
    Route::post('ticket-categories', [TicketCategoryController::class, 'store']);
    Route::put('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update']);
    Route::delete('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy']);

    Route::get('knowledge-base/search', [KnowledgeBaseController::class, 'search']);
    Route::post('knowledge-base/{knowledge_base}/rate', [KnowledgeBaseController::class, 'rate']);
    Route::post('knowledge-base/{knowledge_base}/restore-version', [KnowledgeBaseController::class, 'restoreVersion']);
    Route::get('knowledge-base/categories', [KnowledgeBaseCategoryController::class, 'index']);
    Route::get('knowledge-base/contextual', [KnowledgeBaseController::class, 'contextual']);
    Route::post('knowledge-base/record-view', [KnowledgeBaseController::class, 'recordView']);
    Route::post('doc-requests', function (Request $request) {
        $validated = $request->validate([
            'screen_identifier' => 'required|string',
            'comment' => 'sometimes|string',
        ]);

        $request = \App\Models\DocRequest::firstOrCreate(
            ['screen_identifier' => $validated['screen_identifier'], 'user_id' => $request->user()->id],
            ['comment' => $validated['comment'] ?? null]
        );

        if (!$request->wasRecentlyCreated) {
            $request->incrementRequestCount();
        }

        return response()->json(['created' => true]);
    });
    Route::apiResource('knowledge-base', KnowledgeBaseController::class);

    Route::get('canned-responses', [CannedResponseController::class, 'index']);
    Route::post('canned-responses', [CannedResponseController::class, 'store']);
    Route::get('canned-responses/{cannedResponse}', [CannedResponseController::class, 'show']);
    Route::put('canned-responses/{cannedResponse}', [CannedResponseController::class, 'update']);
    Route::delete('canned-responses/{cannedResponse}', [CannedResponseController::class, 'destroy']);
    Route::patch('canned-responses/{cannedResponse}/toggle', [CannedResponseController::class, 'toggleActive']);
    Route::post('canned-responses/{cannedResponse}/favorite', [CannedResponseController::class, 'favorite']);

    Route::post('tickets/{ticket}/rating', [CsatController::class, 'store']);
    Route::get('tickets/{ticket}/rating', [CsatController::class, 'show']);
    Route::get('analytics/csat', [CsatController::class, 'analytics']);

    // Admin routes (manager+ access)
    Route::middleware('role:manager|admin')->group(function () {
        // Pipeline Management
        Route::post('pipelines', [App\Http\Controllers\Admin\PipelineController::class, 'store']);
        Route::put('pipelines/{pipeline}', [App\Http\Controllers\Admin\PipelineController::class, 'update']);
        Route::delete('pipelines/{pipeline}', [App\Http\Controllers\Admin\PipelineController::class, 'destroy']);
        Route::patch('pipelines/{pipeline}/archive', [App\Http\Controllers\Admin\PipelineController::class, 'archive']);

        // Deal Automations
        Route::get('deal-automations', [App\Http\Controllers\Api\V1\DealAutomationController::class, 'index']);
        Route::post('deal-automations', [App\Http\Controllers\Api\V1\DealAutomationController::class, 'store']);
        Route::get('deal-automations/{dealAutomation}', [App\Http\Controllers\Api\V1\DealAutomationController::class, 'show']);
        Route::put('deal-automations/{dealAutomation}', [App\Http\Controllers\Api\V1\DealAutomationController::class, 'update']);
        Route::delete('deal-automations/{dealAutomation}', [App\Http\Controllers\Api\V1\DealAutomationController::class, 'destroy']);

        // Win/Loss Reasons
        Route::get('win-loss-reasons', [WinLossReasonController::class, 'index']);
        Route::post('win-loss-reasons', [WinLossReasonController::class, 'store']);
        Route::put('win-loss-reasons/{winLossReason}', [WinLossReasonController::class, 'update']);
        Route::delete('win-loss-reasons/{winLossReason}', [WinLossReasonController::class, 'destroy']);

        // Quote Templates
        Route::get('quote-templates', [QuoteTemplateController::class, 'index']);
        Route::post('quote-templates', [QuoteTemplateController::class, 'store']);
        Route::put('quote-templates/{quoteTemplate}', [QuoteTemplateController::class, 'update']);
        Route::delete('quote-templates/{quoteTemplate}', [QuoteTemplateController::class, 'destroy']);

        // Campaigns
        Route::get('campaigns', [CampaignController::class, 'index']);
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
        Route::post('campaigns', [CampaignController::class, 'store']);
        Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
        Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy']);
        Route::post('campaigns/{campaign}/steps', [CampaignController::class, 'addStep']);
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume']);

        // Custom Fields
        Route::get('custom-fields', [CustomFieldController::class, 'index']);
        Route::post('custom-fields', [CustomFieldController::class, 'store']);
        Route::get('custom-fields/{customField}', [CustomFieldController::class, 'show']);
        Route::put('custom-fields/{customField}', [CustomFieldController::class, 'update']);
        Route::delete('custom-fields/{customField}', [CustomFieldController::class, 'destroy']);

        // Campaign Templates
        Route::get('campaign-templates', [CampaignTemplateController::class, 'index']);
        Route::get('campaign-templates/{template}', [CampaignTemplateController::class, 'show']);
        Route::post('campaign-templates', [CampaignTemplateController::class, 'store']);
        Route::put('campaign-templates/{template}', [CampaignTemplateController::class, 'update']);
        Route::delete('campaign-templates/{template}', [CampaignTemplateController::class, 'destroy']);
        Route::patch('campaign-templates/{template}/submit', [CampaignTemplateController::class, 'submitForReview']);
        Route::patch('campaign-templates/{template}/approve', [CampaignTemplateController::class, 'approve']);

        // Drip Sequences
        Route::get('drip-sequences', [DripSequenceController::class, 'index']);
        Route::get('drip-sequences/{sequence}', [DripSequenceController::class, 'show']);
        Route::post('drip-sequences', [DripSequenceController::class, 'store']);
        Route::put('drip-sequences/{sequence}', [DripSequenceController::class, 'update']);
        Route::delete('drip-sequences/{sequence}', [DripSequenceController::class, 'destroy']);
        Route::post('drip-sequences/{sequence}/steps', [DripSequenceController::class, 'addStep']);

        // Social Posts
        Route::get('social-posts', [SocialPostController::class, 'index']);
        Route::post('social-posts', [SocialPostController::class, 'store']);
        Route::put('social-posts/{socialPost}', [SocialPostController::class, 'update']);
        Route::delete('social-posts/{socialPost}', [SocialPostController::class, 'destroy']);

        // Interactions - Unified Inbox
        Route::get('interactions/inbox', [InteractionController::class, 'inbox']);
        Route::get('interactions/{id}', [InteractionController::class, 'show']);
        Route::patch('interactions/{id}/mark-reviewed', [InteractionController::class, 'markReviewed']);
        Route::patch('interactions/{id}/lock', [InteractionController::class, 'lock']);
        Route::patch('interactions/{id}/unlock', [InteractionController::class, 'unlock']);

        // Channels
        Route::get('interaction-channels', [InteractionController::class, 'channels']);

        // Email
        Route::post('email/send', [InteractionController::class, 'sendEmail']);
        Route::post('email/webhook/inbound', [IntegrationController::class, 'handleInboundEmail']);

        // SMS
        Route::post('sms/send', [InteractionController::class, 'sendSms']);

        // Calls
        Route::post('calls/log', [InteractionController::class, 'logCall']);
        Route::post('calls/webhook/twilio', [IntegrationController::class, 'handleTwilioWebhook']);

        // Chat
        Route::post('chat/start', [ChatController::class, 'start']);
        Route::patch('chat/{sessionId}/accept', [ChatController::class, 'accept']);
        Route::post('chat/{sessionId}/message', [ChatController::class, 'message']);
        Route::patch('chat/{sessionId}/close', [ChatController::class, 'close']);
        Route::get('chat/waiting', [ChatController::class, 'waiting']);

        // Kiosk
        Route::post('kiosk/{kiosk}/ingest', [KioskController::class, 'ingest']);

        Route::get('sla', [SlaController::class, 'index']);
        Route::post('sla', [SlaController::class, 'store']);
        Route::get('sla/{slaDefinition}', [SlaController::class, 'show']);
        Route::put('sla/{slaDefinition}', [SlaController::class, 'update']);
        Route::delete('sla/{slaDefinition}', [SlaController::class, 'destroy']);
        Route::get('tickets/{ticket}/sla', [SlaController::class, 'ticketSla']);
        Route::get('analytics/sla', [SlaController::class, 'analytics']);

        // Integrations
        Route::get('integrations', [IntegrationController::class, 'index']);
        Route::post('integrations', [IntegrationController::class, 'store']);
        Route::put('integrations/{integration}', [IntegrationController::class, 'update']);
        Route::delete('integrations/{integration}', [IntegrationController::class, 'destroy']);
        Route::post('integrations/{integration}/rotate-key', [IntegrationController::class, 'rotateKey']);

        // Unmatched items
        Route::get('unmatched-items', [InteractionController::class, 'unmatched']);
        Route::patch('unmatched-items/{item}/resolve', [InteractionController::class, 'resolveUnmatched']);

        // Contact Centre Dashboard
        Route::get('contact-centre/stats', [ContactCentreController::class, 'index']);
        Route::get('contact-centre/history', [ContactCentreController::class, 'history']);
        Route::patch('contact-centre/interactions/{interaction}/reassign', [ContactCentreController::class, 'reassign']);

        // Translations / Language
        Route::get('translations/available', [TranslationController::class, 'available']);
        Route::get('translations', [TranslationController::class, 'index']);
        Route::post('user/language', [TranslationController::class, 'setLanguage']);
    });

    // Webhooks (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('webhooks', [WebhookController::class, 'index']);
        Route::post('webhooks', [WebhookController::class, 'store']);
        Route::get('webhooks/{webhook}', [WebhookController::class, 'show']);
        Route::put('webhooks/{webhook}', [WebhookController::class, 'update']);
        Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy']);
        Route::post('webhooks/{webhook}/pause', [WebhookController::class, 'pause']);
        Route::post('webhooks/{webhook}/resume', [WebhookController::class, 'resume']);
        Route::post('webhooks/{webhook}/retry/{delivery}', [WebhookController::class, 'retryDelivery']);
    });

    // Comments (polymorphic, per entity type)
    Route::get('{type}/{id}/comments', [CommentController::class, 'index'])->where('id', '[0-9A-Za-z]{26}');
    Route::post('{type}/{id}/comments', [CommentController::class, 'store'])->where('id', '[0-9A-Za-z]{26}');
    Route::get('{type}/{id}/comments/{comment}', [CommentController::class, 'show'])->where(['id' => '[0-9A-Za-z]{26}', 'comment' => '[0-9A-Za-z]{26}']);
    Route::put('{type}/{id}/comments/{comment}', [CommentController::class, 'update'])->where(['id' => '[0-9A-Za-z]{26}', 'comment' => '[0-9A-Za-z]{26}']);
    Route::delete('{type}/{id}/comments/{comment}', [CommentController::class, 'destroy'])->where(['id' => '[0-9A-Za-z]{26}', 'comment' => '[0-9A-Za-z]{26}']);

    // Calendar
    Route::get('calendar', [CalendarController::class, 'index']);

    // Teams
    Route::middleware('role:manager|admin')->group(function () {
        Route::get('teams', [TeamController::class, 'index']);
        Route::post('teams', [TeamController::class, 'store']);
        Route::get('teams/{team}', [TeamController::class, 'show']);
        Route::put('teams/{team}', [TeamController::class, 'update']);
        Route::delete('teams/{team}', [TeamController::class, 'destroy']);
        Route::get('teams/{team}/members', [TeamController::class, 'members']);
        Route::post('teams/{team}/members', [TeamController::class, 'addMember']);
        Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Discussion Boards
    Route::prefix('{type}/{id}/discussion')->where(['id' => '[0-9A-Za-z]{26}'])->group(function () {
        Route::get('/', function ($type, $id) {
            $modelClass = match ($type) {
                'accounts' => \App\Models\Account::class,
                'deals' => \App\Models\Deal::class,
                default => abort(404),
            };
            $model = $modelClass::findOrFail($id);
            $board = $model->discussionBoard()->firstOrCreate(['title' => 'Discussion']);

            return response()->json(['data' => $board->load('threads.author')]);
        });

        Route::get('threads', function ($type, $id) {
            $modelClass = match ($type) {
                'accounts' => \App\Models\Account::class,
                'deals' => \App\Models\Deal::class,
                default => abort(404),
            };
            $model = $modelClass::findOrFail($id);
            $board = $model->discussionBoard()->first();

            return response()->json(['data' => $board?->threads()->with('author')->latest()->paginate(20)]);
        });

        Route::post('threads', 'App\Http\Controllers\Api\V1\DiscussionThreadController@store');
        Route::get('threads/{thread}', 'App\Http\Controllers\Api\V1\DiscussionThreadController@show');
        Route::put('threads/{thread}', 'App\Http\Controllers\Api\V1\DiscussionThreadController@update');
        Route::delete('threads/{thread}', 'App\Http\Controllers\Api\V1\DiscussionThreadController@destroy');
    });
});

// Service Registry
Route::middleware(['auth:sanctum', 'permission:integrations.manage'])->group(function () {
    Route::get('service-registry', [ServiceRegistryController::class, 'index']);
    Route::get('service-registry/activity', [ServiceRegistryController::class, 'activity']);
    Route::get('service-registry/export', [ServiceRegistryController::class, 'export']);
});

// Inbound webhooks (API key auth, no versioning per spec)
Route::middleware('api_key_auth')->group(function () {
    Route::post('webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.inbound.stripe');
    Route::post('webhooks/twilio', [WebhookController::class, 'twilio'])->name('webhooks.inbound.twilio');
    Route::post('webhooks/docusign', [WebhookController::class, 'docusign'])->name('webhooks.inbound.docusign');
    Route::post('webhooks/mailgun', [WebhookController::class, 'mailgun'])->name('webhooks.inbound.mailgun');
});
