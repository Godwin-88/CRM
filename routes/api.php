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
use App\Http\Controllers\Api\V1\CsatController;
use App\Http\Controllers\Api\V1\CustomFieldController;
use App\Http\Controllers\Api\V1\DealController;
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
use App\Http\Controllers\Api\V1\AssistantTokenController;
use App\Http\Controllers\Api\V1\AgentToolController;
use App\Http\Controllers\Api\V1\AssistantChatController;
use App\Http\Controllers\Api\V1\CaseRecordController;
use App\Http\Controllers\Api\V1\ServiceRegistryController;
use App\Http\Controllers\Api\V1\ServiceCatalogItemController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Http\Controllers\Api\V1\SlaController;
use App\Http\Controllers\Api\V1\SocialPostController;
use App\Http\Controllers\Api\V1\SurveyController;
use App\Http\Controllers\Api\V1\TagController;
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
    Route::post('analytics/forecast/target', [AnalyticsApiController::class, 'updateRevenueTarget']);
    Route::get('analytics/win-loss', [AnalyticsController::class, 'winLossAnalysis']);
    Route::get('analytics/dashboard', [AnalyticsApiController::class, 'dashboard']);
    Route::get('analytics/dashboard-widgets', [AnalyticsApiController::class, 'dashboardWidgets']);
    Route::put('analytics/dashboard-widgets', [AnalyticsApiController::class, 'updateDashboardWidgets']);
    Route::get('analytics/growth', [AnalyticsApiController::class, 'growthMetrics']);
    Route::get('analytics/finance', [AnalyticsApiController::class, 'financeMetrics']);
    Route::get('analytics/deal-score/{deal}', [AnalyticsApiController::class, 'dealScore']);
    Route::get('analytics/deal-scores', [AnalyticsApiController::class, 'dealScores']);
    Route::post('analytics/deal-scores/recalculate', [AnalyticsApiController::class, 'recalculateDealScores']);
    Route::post('analytics/deal-score/{deal}', [AnalyticsApiController::class, 'updateDealScore']);
    Route::delete('analytics/deal-score/{deal}', [AnalyticsApiController::class, 'clearDealScore']);
    Route::get('analytics/scoring-weights', [AnalyticsApiController::class, 'scoringWeights']);
    Route::put('analytics/scoring-weights', [AnalyticsApiController::class, 'updateScoringWeights']);
    Route::get('analytics/customer', [AnalyticsApiController::class, 'customerMetrics']);
    Route::get('analytics/campaign-performance', [CampaignAnalyticsController::class, 'performance']);
    Route::get('analytics/campaign-time-series/{campaign}', [CampaignAnalyticsController::class, 'timeSeries']);
    Route::get('analytics/campaign-per-contact/{campaign}', [CampaignAnalyticsController::class, 'perContact']);
    Route::get('analytics/campaign-per-link/{campaign}', [CampaignAnalyticsController::class, 'perLink']);
    Route::get('analytics/campaign-revenue/{campaign}', [CampaignAnalyticsController::class, 'revenue']);
    Route::get('analytics/cross-campaign', [CampaignAnalyticsController::class, 'crossCampaign']);

    // Reports
    Route::get('reports', [ReportBuilderController::class, 'index']);
    Route::post('reports', [ReportBuilderController::class, 'store']);
    Route::get('reports/{report}', [ReportBuilderController::class, 'show']);
    Route::put('reports/{report}', [ReportBuilderController::class, 'update']);
    Route::delete('reports/{report}', [ReportBuilderController::class, 'destroy']);
    Route::post('reports/{report}/schedule', [ReportBuilderController::class, 'schedule']);
    Route::post('reports/{report}/run', [ReportBuilderController::class, 'run']);
    Route::get('reports/{report}/export/csv', [ReportBuilderController::class, 'exportCsv']);
    Route::get('reports/{report}/export/pdf', [ReportBuilderController::class, 'exportPdf']);
    Route::post('scheduled-reports/{scheduledReport}/deliver', [ReportBuilderController::class, 'deliver']);

    // Compliance
    Route::get('audit-trail', [ComplianceAnalyticsController::class, 'auditTrail']);
    Route::get('audit-stats', [ComplianceAnalyticsController::class, 'auditStats']);
    Route::get('audit-anomalies', [ComplianceAnalyticsController::class, 'anomalies']);
    Route::post('audit-anomalies/{anomalyId}/acknowledge', [ComplianceAnalyticsController::class, 'acknowledgeAnomaly']);
    Route::get('audit-retention', [ComplianceAnalyticsController::class, 'retentionSettings']);
    Route::put('audit-retention', [ComplianceAnalyticsController::class, 'updateRetentionSettings']);

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

    Route::get('service-catalog-items', [ServiceCatalogItemController::class, 'index']);
    Route::post('service-catalog-items', [ServiceCatalogItemController::class, 'store']);
    Route::get('service-catalog-items/{serviceCatalogItem}', [ServiceCatalogItemController::class, 'show']);
    Route::put('service-catalog-items/{serviceCatalogItem}', [ServiceCatalogItemController::class, 'update']);
    Route::delete('service-catalog-items/{serviceCatalogItem}', [ServiceCatalogItemController::class, 'destroy']);

    Route::get('service-requests', [ServiceRequestController::class, 'index']);
    Route::post('service-requests', [ServiceRequestController::class, 'store']);
    Route::get('service-requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::post('service-requests/{serviceRequest}/status', [ServiceRequestController::class, 'updateStatus']);
    Route::post('service-requests/{serviceRequest}/assign', [ServiceRequestController::class, 'assign']);
    Route::post('service-requests/{serviceRequest}/escalate', [ServiceRequestController::class, 'escalate']);
    Route::post('service-requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel']);
    Route::post('service-requests/{serviceRequest}/reopen', [ServiceRequestController::class, 'reopen']);
    Route::post('service-requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete']);
    Route::post('service-requests/{serviceRequest}/close', [ServiceRequestController::class, 'close']);
    Route::post('service-requests/{serviceRequest}/document-requests', [ServiceRequestController::class, 'addDocumentRequest']);
    Route::post('service-requests/{serviceRequest}/merge', [ServiceRequestController::class, 'merge']);

    Route::get('cases', [CaseRecordController::class, 'index']);
    Route::post('cases', [CaseRecordController::class, 'store']);
    Route::get('cases/{caseRecord}', [CaseRecordController::class, 'show']);
    Route::post('cases/{caseRecord}/status', [CaseRecordController::class, 'updateStatus']);
    Route::post('cases/{caseRecord}/links', [CaseRecordController::class, 'addLink']);
    Route::delete('cases/{caseRecord}/links/{caseLink}', [CaseRecordController::class, 'removeLink']);
    Route::post('cases/{caseRecord}/escalate', [CaseRecordController::class, 'escalate']);
    Route::post('cases/{caseRecord}/close', [CaseRecordController::class, 'close']);
    Route::post('cases/{caseRecord}/reopen', [CaseRecordController::class, 'reopen']);
    Route::post('cases/{caseRecord}/notes', [CaseRecordController::class, 'addNote']);
    Route::post('cases/{caseRecord}/signoff', [CaseRecordController::class, 'requestSignoff']);
    Route::post('cases/{caseRecord}/signoff/approve', [CaseRecordController::class, 'approveSignoff']);
    Route::post('cases/{caseRecord}/signoff/reject', [CaseRecordController::class, 'rejectSignoff']);

    Route::get('ticket-categories', [TicketCategoryController::class, 'index']);
    Route::post('ticket-categories', [TicketCategoryController::class, 'store']);
    Route::put('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update']);
    Route::delete('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy']);

    Route::get('knowledge-base/search', [KnowledgeBaseController::class, 'search']);
    Route::post('knowledge-base/retrieve-for-assistant', [KnowledgeBaseController::class, 'retrieveForAssistant']);
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
        Route::put('campaigns/{campaign}/steps/{step}', [CampaignController::class, 'updateStep']);
        Route::delete('campaigns/{campaign}/steps/{step}', [CampaignController::class, 'deleteStep']);
        Route::put('campaigns/{campaign}/steps/reorder', [CampaignController::class, 'reorderSteps']);
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume']);
        Route::post('campaigns/{campaign}/dispatch', [CampaignController::class, 'dispatch']);
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel']);
        Route::post('campaigns/{campaign}/validate', [CampaignController::class, 'validateCampaign']);
        Route::post('campaigns/{campaign}/ab-test', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'store']);
        Route::get('campaigns/{campaign}/ab-test', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'show']);
        Route::put('campaigns/{campaign}/ab-test', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'update']);
        Route::post('campaigns/{campaign}/ab-test/start', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'start']);
        Route::post('campaigns/{campaign}/ab-test/conclude', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'conclude']);
        Route::get('campaigns/{campaign}/ab-test/results', [App\Http\Controllers\Api\V1\CampaignABTestController::class, 'results']);
        Route::get('segments/{segment}/count', [CampaignController::class, 'getSegmentCount']);

        // Tags
        Route::get('tags', [App\Http\Controllers\Api\V1\TagController::class, 'index']);
        Route::post('tags', [App\Http\Controllers\Api\V1\TagController::class, 'store']);
        Route::put('tags/{tag}', [App\Http\Controllers\Api\V1\TagController::class, 'update']);
        Route::delete('tags/{tag}', [App\Http\Controllers\Api\V1\TagController::class, 'destroy']);
        Route::post('campaigns/bulk-tags', [App\Http\Controllers\Api\V1\TagController::class, 'bulkApply']);

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
        Route::get('campaign-templates/variables', [CampaignTemplateController::class, 'variables']);
        Route::post('campaign-templates/{template}/duplicate', [CampaignTemplateController::class, 'duplicate']);
        Route::post('campaign-templates/{template}/publish', [CampaignTemplateController::class, 'publish']);
        Route::post('campaign-templates/{template}/archive', [CampaignTemplateController::class, 'archive']);
        Route::post('campaign-templates/{template}/restore-version', [CampaignTemplateController::class, 'restoreVersion']);

        // Drip Sequences
        Route::get('drip-sequences', [DripSequenceController::class, 'index']);
        Route::get('drip-sequences/{sequence}', [DripSequenceController::class, 'show']);
        Route::post('drip-sequences', [DripSequenceController::class, 'store']);
        Route::put('drip-sequences/{sequence}', [DripSequenceController::class, 'update']);
        Route::delete('drip-sequences/{sequence}', [DripSequenceController::class, 'destroy']);
        Route::post('drip-sequences/{sequence}/steps', [DripSequenceController::class, 'addStep']);
        Route::put('drip-sequences/{sequence}/steps/{step}', [DripSequenceController::class, 'updateStep']);
        Route::delete('drip-sequences/{sequence}/steps/{step}', [DripSequenceController::class, 'deleteStep']);
        Route::post('drip-sequences/{sequence}/status', [DripSequenceController::class, 'updateStatus']);
        Route::post('drip-sequences/{sequence}/enrol', [DripSequenceController::class, 'enrolContact']);
        Route::delete('drip-sequences/{sequence}/enrolments/{contact}', [DripSequenceController::class, 'cancelEnrolment']);
        Route::get('drip-sequences/{sequence}/enrolments', [DripSequenceController::class, 'listEnrolments']);

        // Social Posts
        Route::get('social-posts', [SocialPostController::class, 'index']);
        Route::post('social-posts', [SocialPostController::class, 'store']);
        Route::put('social-posts/{socialPost}', [SocialPostController::class, 'update']);
        Route::delete('social-posts/{socialPost}', [SocialPostController::class, 'destroy']);
        Route::get('social-posts/channels', [SocialPostController::class, 'channels']);
        Route::post('social-posts/{socialPost}/publish', [SocialPostController::class, 'publish']);
        Route::post('social-posts/{socialPost}/refresh', [SocialPostController::class, 'refreshEngagement']);

        // Surveys
        Route::get('surveys', [SurveyController::class, 'index']);
        Route::post('surveys', [SurveyController::class, 'store']);
        Route::get('surveys/{survey}', [SurveyController::class, 'show']);
        Route::put('surveys/{survey}', [SurveyController::class, 'update']);
        Route::delete('surveys/{survey}', [SurveyController::class, 'destroy']);
        Route::post('surveys/{survey}/send', [SurveyController::class, 'send']);
        Route::post('surveys/{survey}/respond', [SurveyController::class, 'respond']);
        Route::get('surveys/{survey}/responses', [SurveyController::class, 'responses']);
        Route::get('surveys/{survey}/analytics', [SurveyController::class, 'analytics']);
        Route::post('surveys/{surveyId}/public-respond/{token}', [SurveyController::class, 'publicRespond']);

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
        Route::get('contact-centre/interactions', [ContactCentreController::class, 'interactions']);

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

// ================================================================
// AI CRM ASSISTANT — AGENT TOOL API (Section 4.14 Feature 1 & 5)
// ================================================================

// Internal token minting for the assistant service
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('assistant')->group(function () {
        Route::post('token', [AssistantTokenController::class, 'mint'])->name('assistant.token.mint');
        Route::delete('token', [AssistantTokenController::class, 'revoke'])->name('assistant.token.revoke');
        Route::post('chat', [AssistantChatController::class, 'chat'])->name('assistant.chat');
        Route::get('proactive', [AssistantChatController::class, 'proactive'])->name('assistant.proactive');
        Route::post('feedback', [AssistantChatController::class, 'feedback'])->name('assistant.feedback');

        // Agent tool API — allowlist-based, uses validate_assistant_token middleware
        Route::middleware('validate_assistant_token')->prefix('tool')->group(function () {
            Route::post('contacts/search', [AgentToolController::class, 'handle'])->name('assistant.tool.contacts.search');
            Route::post('contacts/get', [AgentToolController::class, 'handle'])->name('assistant.tool.contacts.get');
            Route::post('contacts/timeline', [AgentToolController::class, 'handle'])->name('assistant.tool.contacts.timeline');
            Route::post('deals/search', [AgentToolController::class, 'handle'])->name('assistant.tool.deals.search');
            Route::post('deals/get', [AgentToolController::class, 'handle'])->name('assistant.tool.deals.get');
            Route::post('deals/move_stage', [AgentToolController::class, 'handle'])->name('assistant.tool.deals.move_stage');
            Route::post('deals/create', [AgentToolController::class, 'handle'])->name('assistant.tool.deals.create');
            Route::post('accounts/search', [AgentToolController::class, 'handle'])->name('assistant.tool.accounts.search');
            Route::post('accounts/get', [AgentToolController::class, 'handle'])->name('assistant.tool.accounts.get');
            Route::post('tickets/search', [AgentToolController::class, 'handle'])->name('assistant.tool.tickets.search');
            Route::post('tickets/get', [AgentToolController::class, 'handle'])->name('assistant.tool.tickets.get');
            Route::post('tickets/create', [AgentToolController::class, 'handle'])->name('assistant.tool.tickets.create');
            Route::post('tickets/update_status', [AgentToolController::class, 'handle'])->name('assistant.tool.tickets.update_status');
            Route::post('services/search', [AgentToolController::class, 'handle'])->name('assistant.tool.services.search');
            Route::post('services/get', [AgentToolController::class, 'handle'])->name('assistant.tool.services.get');
            Route::post('service_requests/search', [AgentToolController::class, 'handle'])->name('assistant.tool.service_requests.search');
            Route::post('service_requests/create', [AgentToolController::class, 'handle'])->name('assistant.tool.service_requests.create');
            Route::post('service_requests/get_status', [AgentToolController::class, 'handle'])->name('assistant.tool.service_requests.get_status');
            Route::post('service_requests/update_status', [AgentToolController::class, 'handle'])->name('assistant.tool.service_requests.update_status');
            Route::post('service_requests/add_document_request', [AgentToolController::class, 'handle'])->name('assistant.tool.service_requests.add_document_request');
            Route::post('cases/search', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.search');
            Route::post('cases/create', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.create');
            Route::post('cases/get', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.get');
            Route::post('cases/update_status', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.update_status');
            Route::post('cases/add_note', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.add_note');
            Route::post('cases/request_signoff', [AgentToolController::class, 'handle'])->name('assistant.tool.cases.request_signoff');
            Route::post('activities/create', [AgentToolController::class, 'handle'])->name('assistant.tool.activities.create');
            Route::post('segments/preview', [AgentToolController::class, 'handle'])->name('assistant.tool.segments.preview');
            Route::post('segments/preview_count', [AgentToolController::class, 'handle'])->name('assistant.tool.segments.preview_count');
            Route::post('kb/search', [AgentToolController::class, 'handle'])->name('assistant.tool.kb.search');
            Route::post('dashboards/summary', [AgentToolController::class, 'handle'])->name('assistant.tool.dashboards.summary');
            Route::post('analytics/metric', [AgentToolController::class, 'handle'])->name('assistant.tool.analytics.metric');
            Route::post('reports/run', [AgentToolController::class, 'handle'])->name('assistant.tool.reports.run');
            Route::post('contracts/search', [AgentToolController::class, 'handle'])->name('assistant.tool.contracts.search');
            Route::post('contracts/get_status', [AgentToolController::class, 'handle'])->name('assistant.tool.contracts.get_status');
            Route::post('loyalty/get_balance', [AgentToolController::class, 'handle'])->name('assistant.tool.loyalty.get_balance');
            Route::post('clv/get_score', [AgentToolController::class, 'handle'])->name('assistant.tool.clv.get_score');
            Route::post('users/my_permissions', [AgentToolController::class, 'handle'])->name('assistant.tool.users.my_permissions');
            Route::post('integrations/status', [AgentToolController::class, 'handle'])->name('assistant.tool.integrations.status');
            Route::post('webhooks/get_delivery_log', [AgentToolController::class, 'handle'])->name('assistant.tool.webhooks.get_delivery_log');
            Route::post('notifications/get_unread', [AgentToolController::class, 'handle'])->name('assistant.tool.notifications.get_unread');
            Route::post('calendar/upcoming', [AgentToolController::class, 'handle'])->name('assistant.tool.calendar.upcoming');
            Route::post('comments/post', [AgentToolController::class, 'handle'])->name('assistant.tool.comments.post');
            Route::post('tasks/create', [AgentToolController::class, 'handle'])->name('assistant.tool.tasks.create');
            Route::post('invoices/search', [AgentToolController::class, 'handle'])->name('assistant.tool.invoices.search');
            Route::post('invoices/get_ledger', [AgentToolController::class, 'handle'])->name('assistant.tool.invoices.get_ledger');
            Route::get('tools/available', [AgentToolController::class, 'availableTools'])->name('assistant.tools.available');
        });

        Route::post('internal/low-confidence', [AssistantChatController::class, 'flagLowConfidence'])
            ->name('assistant.internal.low-confidence');
    });
});

// ================================================================
// LEGACY / EXISTING ROUTES END HERE
// ================================================================

