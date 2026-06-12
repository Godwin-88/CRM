<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CampaignAnalyticsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CampaignTemplateController;
use App\Http\Controllers\Api\V1\CannedResponseController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ContactCentreController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CsatController;
use App\Http\Controllers\Api\V1\DripSequenceController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\InteractionController;
use App\Http\Controllers\Api\V1\KioskController;
use App\Http\Controllers\Api\V1\KnowledgeBaseController;
use App\Http\Controllers\Api\V1\PipelineController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\SocialPostController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TranslationController;
use App\Http\Controllers\Api\V1\DealController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Admin\ScoringRuleController;
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
    Route::get('analytics/campaign-performance', [\App\Http\Controllers\Api\V1\CampaignAnalyticsController::class, 'performance']);
    Route::get('analytics/campaign-time-series/{campaign}', [CampaignAnalyticsController::class, 'timeSeries']);
    Route::get('analytics/campaign-per-contact/{campaign}', [CampaignAnalyticsController::class, 'perContact']);
    Route::get('analytics/campaign-per-link/{campaign}', [CampaignAnalyticsController::class, 'perLink']);

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

    Route::get('ticket-categories', [\App\Http\Controllers\Api\V1\TicketCategoryController::class, 'index']);
    Route::post('ticket-categories', [\App\Http\Controllers\Api\V1\TicketCategoryController::class, 'store']);
    Route::put('ticket-categories/{ticketCategory}', [\App\Http\Controllers\Api\V1\TicketCategoryController::class, 'update']);
    Route::delete('ticket-categories/{ticketCategory}', [\App\Http\Controllers\Api\V1\TicketCategoryController::class, 'destroy']);

    Route::get('knowledge-base/search', [KnowledgeBaseController::class, 'search']);
    Route::post('knowledge-base/{knowledge_base}/rate', [KnowledgeBaseController::class, 'rate']);
    Route::post('knowledge-base/{knowledge_base}/restore-version', [KnowledgeBaseController::class, 'restoreVersion']);
    Route::get('knowledge-base/categories', [\App\Http\Controllers\Api\V1\KnowledgeBaseCategoryController::class, 'index']);
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
        Route::post('pipelines', [\App\Http\Controllers\Admin\PipelineController::class, 'store']);
        Route::put('pipelines/{pipeline}', [\App\Http\Controllers\Admin\PipelineController::class, 'update']);
        Route::delete('pipelines/{pipeline}', [\App\Http\Controllers\Admin\PipelineController::class, 'destroy']);
        Route::patch('pipelines/{pipeline}/archive', [\App\Http\Controllers\Admin\PipelineController::class, 'archive']);

        // Win/Loss Reasons
        Route::get('win-loss-reasons', [\App\Http\Controllers\Admin\WinLossReasonController::class, 'index']);
        Route::post('win-loss-reasons', [\App\Http\Controllers\Admin\WinLossReasonController::class, 'store']);
        Route::put('win-loss-reasons/{winLossReason}', [\App\Http\Controllers\Admin\WinLossReasonController::class, 'update']);
        Route::delete('win-loss-reasons/{winLossReason}', [\App\Http\Controllers\Admin\WinLossReasonController::class, 'destroy']);

        // Quote Templates
        Route::get('quote-templates', [\App\Http\Controllers\Admin\QuoteTemplateController::class, 'index']);
        Route::post('quote-templates', [\App\Http\Controllers\Admin\QuoteTemplateController::class, 'store']);
        Route::put('quote-templates/{quoteTemplate}', [\App\Http\Controllers\Admin\QuoteTemplateController::class, 'update']);
        Route::delete('quote-templates/{quoteTemplate}', [\App\Http\Controllers\Admin\QuoteTemplateController::class, 'destroy']);

        // Campaigns
        Route::get('campaigns', [CampaignController::class, 'index']);
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
        Route::post('campaigns', [CampaignController::class, 'store']);
        Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
        Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy']);
        Route::post('campaigns/{campaign}/steps', [CampaignController::class, 'addStep']);
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume']);

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

        Route::get('sla', [\App\Http\Controllers\Api\V1\SlaController::class, 'index']);
        Route::post('sla', [\App\Http\Controllers\Api\V1\SlaController::class, 'store']);
        Route::get('sla/{slaDefinition}', [\App\Http\Controllers\Api\V1\SlaController::class, 'show']);
        Route::put('sla/{slaDefinition}', [\App\Http\Controllers\Api\V1\SlaController::class, 'update']);
        Route::delete('sla/{slaDefinition}', [\App\Http\Controllers\Api\V1\SlaController::class, 'destroy']);
        Route::get('tickets/{ticket}/sla', [\App\Http\Controllers\Api\V1\SlaController::class, 'ticketSla']);
        Route::get('analytics/sla', [\App\Http\Controllers\Api\V1\SlaController::class, 'analytics']);
        
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
});
