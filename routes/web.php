<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AnalyticsWebController;
use App\Http\Controllers\Admin\CampaignTemplateWebController;
use App\Http\Controllers\Admin\CampaignWebController;
use App\Http\Controllers\Admin\CannedResponseController;
use App\Http\Controllers\Admin\ClvAnalyticsWebController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Admin\CustomFieldWebController;
use App\Http\Controllers\Admin\DuplicateContactsWebController;
use App\Http\Controllers\Admin\GuidedJourneyWebController;
use App\Http\Controllers\Admin\InteractionWebController;
use App\Http\Controllers\Admin\LoyaltyProgramWebController;
use App\Http\Controllers\Admin\OmniChannelWebController;
use App\Http\Controllers\Admin\OnboardingWebController;
use App\Http\Controllers\Admin\PipelineWebController;
use App\Http\Controllers\Admin\QuoteTemplateWebController;
use App\Http\Controllers\Admin\QuoteWebController;
use App\Http\Controllers\Admin\ReactivationWebController;
use App\Http\Controllers\Admin\ScoringRuleWebController;
use App\Http\Controllers\Admin\SlaBreachController;
use App\Http\Controllers\Admin\SlaWebController;
use App\Http\Controllers\Admin\SocialPostWebController;
use App\Http\Controllers\Admin\SupportCategoryController;
use App\Http\Controllers\Admin\SurveyWebController;
use App\Http\Controllers\Admin\TagWebController;
use App\Http\Controllers\Admin\TicketFormController;
use App\Http\Controllers\Admin\WelcomeEmailTemplateController;
use App\Http\Controllers\Admin\WinLossReasonWebController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankingRelationshipController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DsrController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DripSequenceWebController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LegalMatterController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PrivilegedSessionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\Support\KnowledgeBaseController;
use App\Http\Controllers\Support\PerformanceController;
use App\Http\Controllers\Support\TicketController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\CalendarWebController;
use App\Http\Controllers\DocsWebController;
use Inertia\Inertia;

// ─── Authentication ───────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// MFA Routes
Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
Route::post('/mfa/setup/generate', [MfaController::class, 'generateSecret'])->name('mfa.setup.generate');
Route::post('/mfa/setup', [MfaController::class, 'enable'])->name('mfa.enable');
Route::get('/mfa/verify', [MfaController::class, 'showVerify'])->name('mfa.verify');
Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify.submit');
Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');
Route::post('/admin/users/{user}/mfa/reset', [MfaController::class, 'adminReset'])->name('admin.mfa.reset');

// Email tracking redirect
Route::get('/t/{token}', [TrackingController::class, 'redirect'])->name('tracking.redirect');
Route::get('/open/{token}', [TrackingController::class, 'openPixel'])->name('tracking.open');

// Home page - public access
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// ─── Auth required routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'mfa_verified'])->group(function () {

    // ─── Security Events ───────────────────────────────────────────────────────────
    Route::middleware(['permission:security.events'])->group(function () {
        Route::get('/admin/security/events', [\App\Http\Controllers\SecurityEventController::class, 'index'])
            ->name('admin.security.events');
    });

    // ─── Privileged Session ───────────────────────────────────────────────────────
    Route::get('/admin/privileged/challenge', [PrivilegedSessionController::class, 'showChallenge'])
        ->name('admin.privileged.challenge');
    Route::post('/admin/privileged/enter', [PrivilegedSessionController::class, 'enter'])
        ->name('admin.privileged.enter');
    Route::post('/admin/privileged/exit', [PrivilegedSessionController::class, 'exit'])
        ->name('admin.privileged.exit');

    // ─── Integrations (Admin) ───────────────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/integrations', [\App\Http\Controllers\Admin\IntegrationWebController::class, 'index'])
            ->name('admin.integrations.index');
        Route::get('/admin/integrations/marketplace', [\App\Http\Controllers\Admin\IntegrationWebController::class, 'marketplace'])
            ->name('admin.integrations.marketplace');
        Route::post('/admin/integrations/{integration}/connect', [\App\Http\Controllers\Admin\IntegrationWebController::class, 'connect'])
            ->name('admin.integrations.connect');
        Route::post('/admin/integrations/{integration}/disconnect', [\App\Http\Controllers\Admin\IntegrationWebController::class, 'disconnect'])
            ->name('admin.integrations.disconnect');
        Route::get('/admin/integrations/webhooks', [\App\Http\Controllers\Admin\WebhookWebController::class, 'index'])
            ->name('admin.integrations.webhooks');
        Route::post('/admin/integrations/webhooks', [\App\Http\Controllers\Admin\WebhookWebController::class, 'store'])
            ->name('admin.integrations.webhooks.store');
        Route::get('/admin/integrations/webhooks/{webhook}', [\App\Http\Controllers\Admin\WebhookWebController::class, 'show'])
            ->name('admin.integrations.webhooks.show');
        Route::put('/admin/integrations/webhooks/{webhook}', [\App\Http\Controllers\Admin\WebhookWebController::class, 'update'])
            ->name('admin.integrations.webhooks.update');
        Route::delete('/admin/integrations/webhooks/{webhook}', [\App\Http\Controllers\Admin\WebhookWebController::class, 'destroy'])
            ->name('admin.integrations.webhooks.destroy');
        Route::post('/admin/integrations/webhooks/{webhook}/retry', [\App\Http\Controllers\Admin\WebhookWebController::class, 'retry'])
            ->name('admin.integrations.webhooks.retry');
    });

    Route::middleware(['role:manager|admin'])->group(function () {
        Route::get('/admin/api-tokens', [\App\Http\Controllers\Api\PersonalTokenController::class, 'index'])
            ->name('admin.api-tokens.index');
        Route::post('/admin/api-tokens', [\App\Http\Controllers\Api\PersonalTokenController::class, 'store'])
            ->name('admin.api-tokens.store');
        Route::delete('/admin/api-tokens/{token}', [\App\Http\Controllers\Api\PersonalTokenController::class, 'destroy'])
            ->name('admin.api-tokens.destroy');
    });

    Route::middleware(['permission:integrations.manage'])->group(function () {
        Route::get('/admin/oauth-clients', [\App\Http\Controllers\Api\OAuthClientController::class, 'index'])
            ->name('admin.oauth-clients.index');
        Route::post('/admin/oauth-clients', [\App\Http\Controllers\Api\OAuthClientController::class, 'store'])
            ->name('admin.oauth-clients.store');
        Route::get('/admin/oauth-clients/{client}', [\App\Http\Controllers\Api\OAuthClientController::class, 'show'])
            ->name('admin.oauth-clients.show');
        Route::put('/admin/oauth-clients/{client}', [\App\Http\Controllers\Api\OAuthClientController::class, 'update'])
            ->name('admin.oauth-clients.update');
        Route::delete('/admin/oauth-clients/{client}', [\App\Http\Controllers\Api\OAuthClientController::class, 'destroy'])
            ->name('admin.oauth-clients.destroy');
        Route::post('/admin/oauth-clients/{client}/suspend', [\App\Http\Controllers\Api\OAuthClientController::class, 'suspend'])
            ->name('admin.oauth-clients.suspend');
    });

    // DSR Module
    Route::middleware(['permission:dsr.manage'])->group(function () {
        Route::get('/admin/dsr', [DsrController::class, 'index'])->name('admin.dsr.index');
        Route::get('/admin/dsr/create', [DsrController::class, 'create'])->name('admin.dsr.create');
        Route::post('/admin/dsr', [DsrController::class, 'store'])->name('admin.dsr.store');
        Route::get('/admin/dsr/{dsrRequest}', [DsrController::class, 'show'])->name('admin.dsr.show');
        Route::post('/admin/dsr/{dsrRequest}/execute', [DsrController::class, 'execute'])->name('admin.dsr.execute');
        Route::post('/admin/dsr/{dsrRequest}/override', [DsrController::class, 'override'])->name('admin.dsr.override');
    });

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::get('/contacts/template', [ContactController::class, 'downloadTemplate'])->name('contacts.template');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::post('/contacts/{contact}/accounts/link', [ContactController::class, 'linkAccount'])->name('contacts.accounts.link');
    Route::delete('/contacts/{contact}/accounts/{account}/unlink', [ContactController::class, 'unlinkAccount'])->name('contacts.accounts.unlink');
    Route::get('/contacts/{contact}/deals/create', [ContactController::class, 'createDeal'])->name('contacts.deals.create');
    Route::post('/contacts/{contact}/deals', [ContactController::class, 'storeDeal'])->name('contacts.deals.store');
    Route::post('/contacts/{contact}/deals/link', [ContactController::class, 'linkDeal'])->name('contacts.deals.link');

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Segments
    Route::get('/segments', [SegmentController::class, 'index'])->name('segments.index');
    Route::post('/segments', [SegmentController::class, 'store'])->name('segments.store');

    // Deals
    Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
    Route::get('/deals/create', [DealController::class, 'create'])->name('deals.create');
    Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
    Route::get('/deals/board', [DealController::class, 'board'])->name('deals.board');
    Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
    Route::post('/deals/{deal}/move-stage', [DealController::class, 'moveStage'])->name('deals.move-stage');

    // Quotes
    Route::get('/quotes', [DealController::class, 'quotes'])->name('quotes.index');

    // Analytics
    Route::get('/analytics/forecast', [DealController::class, 'forecast'])->name('analytics.forecast');

    // Admin Analytics (manager+ access)
    Route::middleware(['role:manager|admin'])->group(function () {
        Route::get('/admin/analytics/dashboard', [AnalyticsWebController::class, 'dashboard'])->name('admin.analytics.dashboard');
        Route::get('/admin/analytics/customer', [AnalyticsWebController::class, 'customerAnalytics'])->name('admin.analytics.customer');
        Route::get('/admin/analytics/growth', [AnalyticsWebController::class, 'growthAnalytics'])->name('admin.analytics.growth');
        Route::get('/admin/analytics/finance', [AnalyticsWebController::class, 'financeAnalytics'])->name('admin.analytics.finance');
        Route::get('/admin/analytics/compliance', [AnalyticsWebController::class, 'complianceAnalytics'])->name('admin.analytics.compliance');
        Route::get('/admin/analytics/predictive-scoring', [AnalyticsWebController::class, 'predictiveScoring'])->name('admin.analytics.predictive-scoring');
        Route::get('/admin/analytics/report-builder', [AnalyticsWebController::class, 'reportBuilder'])->name('admin.analytics.report-builder');
    });

    // Pipelines
    Route::get('/pipelines', [PipelineController::class, 'index'])->name('pipelines.index');
    Route::get('/pipelines/{pipeline}/board', [PipelineController::class, 'showBoard'])->name('pipelines.board');

    // Support Routes
    Route::get('/support/tickets', [TicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support/tickets/create', [TicketController::class, 'create'])->name('support.tickets.create');
    Route::post('/support/tickets', [TicketController::class, 'store'])->name('support.tickets.store');
    Route::get('/support/tickets/{ticket}', [TicketController::class, 'show'])->name('support.tickets.show');
    Route::put('/support/tickets/{ticket}', [TicketController::class, 'update'])->name('support.tickets.update');
    Route::delete('/support/tickets/{ticket}', [TicketController::class, 'destroy'])->name('support.tickets.destroy');
    Route::post('/support/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('support.tickets.assign');
    Route::post('/support/tickets/{ticket}/escalate', [TicketController::class, 'escalate'])->name('support.tickets.escalate');
    Route::post('/support/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('support.tickets.resolve');
    Route::post('/support/tickets/{ticket}/close', [TicketController::class, 'close'])->name('support.tickets.close');

    Route::get('/support/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('support.knowledge-base.index');
    Route::get('/support/knowledge-base/{article}', [KnowledgeBaseController::class, 'show'])->name('support.knowledge-base.show');
    Route::post('/support/knowledge-base/{article}/rate', [KnowledgeBaseController::class, 'rate'])->name('support.knowledge-base.rate');
    Route::post('/support/knowledge-base/{article}/link', [KnowledgeBaseController::class, 'linkToTicket'])->name('support.knowledge-base.link');

    Route::get('/support/performance', [PerformanceController::class, 'index'])->name('support.performance.index');

    Route::middleware('role:manager|admin')->group(function () {
        Route::get('/admin/support/categories', [SupportCategoryController::class, 'index'])->name('admin.support.categories.index');
        Route::post('/admin/support/categories', [SupportCategoryController::class, 'store'])->name('admin.support.categories.store');
        Route::put('/admin/support/categories/{ticketCategory}', [SupportCategoryController::class, 'update'])->name('admin.support.categories.update');

        Route::get('/admin/support/forms', [TicketFormController::class, 'index'])->name('admin.support.forms.index');
        Route::post('/admin/support/forms', [TicketFormController::class, 'store'])->name('admin.support.forms.store');
        Route::put('/admin/support/forms/{ticketForm}', [TicketFormController::class, 'update'])->name('admin.support.forms.update');

        Route::get('/admin/support/canned-responses', [CannedResponseController::class, 'index'])->name('admin.support.canned-responses.index');
        Route::post('/admin/support/canned-responses', [CannedResponseController::class, 'store'])->name('admin.support.canned-responses.store');

        Route::get('/admin/support/sla-breaches', [SlaBreachController::class, 'index'])->name('admin.support.sla-breaches.index');
    });

    // Contracts
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
    Route::post('/contracts/{contract}/duplicate', [ContractController::class, 'duplicate'])->name('contracts.duplicate');
    Route::post('/contracts/{contract}/regenerate', [ContractController::class, 'regenerate'])->name('contracts.regenerate');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'downloadSignedUrl'])->name('contracts.download');
    Route::post('/contracts/bulk-export', [ContractController::class, 'bulkExport'])->name('contracts.bulk-export');

    // Legal Matters
    Route::get('/legal', [LegalMatterController::class, 'index'])->name('legal.index');
    Route::get('/legal/create', [LegalMatterController::class, 'create'])->name('legal.create');
    Route::get('/legal/{legalMatter}', [LegalMatterController::class, 'show'])->name('legal.show');
    Route::post('/legal', [LegalMatterController::class, 'store'])->name('legal.store');
    Route::get('/legal/{legalMatter}/edit', [LegalMatterController::class, 'edit'])->name('legal.edit');
    Route::put('/legal/{legalMatter}', [LegalMatterController::class, 'update'])->name('legal.update');
    Route::delete('/legal/{legalMatter}', [LegalMatterController::class, 'destroy'])->name('legal.destroy');
    Route::post('/legal/{legalMatter}/restore', [LegalMatterController::class, 'restore'])->name('legal.restore');
    Route::post('/legal/{legalMatter}/notes', [LegalMatterController::class, 'addNote'])->name('legal.notes.add');
    Route::post('/legal/{legalMatter}/attachments', [LegalMatterController::class, 'uploadAttachment'])->name('legal.attachments.upload');

    // Admin Contract Templates (manager+)
    Route::middleware(['role:manager|admin'])->group(function () {
        Route::get('/admin/contract-templates', [ContractTemplateController::class, 'index'])->name('admin.contract-templates.index');
        Route::get('/admin/contract-templates/create', [ContractTemplateController::class, 'create'])->name('admin.contract-templates.create');
        Route::post('/admin/contract-templates', [ContractTemplateController::class, 'store'])->name('admin.contract-templates.store');
        Route::get('/admin/contract-templates/{contractTemplate}/edit', [ContractTemplateController::class, 'edit'])->name('admin.contract-templates.edit');
        Route::put('/admin/contract-templates/{contractTemplate}', [ContractTemplateController::class, 'update'])->name('admin.contract-templates.update');
        Route::delete('/admin/contract-templates/{contractTemplate}', [ContractTemplateController::class, 'destroy'])->name('admin.contract-templates.destroy');
        Route::post('/admin/contract-templates/{contractTemplate}/restore', [ContractTemplateController::class, 'restore'])->name('admin.contract-templates.restore');
    });

    // Admin routes (manager+ access)
    Route::middleware(['role:manager|admin'])->group(function () {
        Route::get('/admin/pipelines', [PipelineWebController::class, 'index'])->name('admin.pipelines.index');
        Route::get('/admin/win-loss-reasons', [WinLossReasonWebController::class, 'index'])->name('admin.win-loss-reasons.index');
        Route::get('/admin/quote-templates', [QuoteTemplateWebController::class, 'index'])->name('admin.quote-templates.index');
        Route::get('/admin/scoring-rules', [ScoringRuleWebController::class, 'index'])->name('admin.scoring-rules.index');
        Route::get('/admin/custom-fields', [CustomFieldWebController::class, 'index'])->name('admin.custom-fields.index');
        Route::get('/admin/duplicates', [DuplicateContactsWebController::class, 'index'])->name('admin.duplicates.index');
        Route::get('/admin/deal-automations', [DealAutomationWebController::class, 'index'])->name('admin.deal-automations.index');

        // Campaigns
        Route::get('/admin/campaigns', [CampaignWebController::class, 'index'])->name('admin.campaigns.index');
        Route::get('/admin/campaigns/create', [CampaignWebController::class, 'create'])->name('admin.campaigns.create');
        Route::get('/admin/analytics/campaigns', [CampaignWebController::class, 'analytics'])->name('admin.campaign-analytics.index');
        Route::get('/admin/campaigns/{campaign}', [CampaignWebController::class, 'show'])->name('admin.campaigns.show');
        Route::get('/admin/campaigns/{campaign}/ab-test', [CampaignWebController::class, 'abTest'])->name('admin.campaigns.ab-test');

        // Campaign Templates
        Route::get('/admin/campaign-templates', [CampaignTemplateWebController::class, 'index'])->name('admin.campaign-templates.index');
        Route::get('/admin/campaign-templates/create', [CampaignTemplateWebController::class, 'create'])->name('admin.campaign-templates.create');

        // Drip Sequences
        Route::get('/admin/drip-sequences', [DripSequenceWebController::class, 'index'])->name('admin.drip-sequences.index');
        Route::get('/admin/drip-sequences/{sequence}', [DripSequenceWebController::class, 'show'])->name('admin.drip-sequences.show');

        // Social Posts
        Route::get('/admin/social-posts', [SocialPostWebController::class, 'index'])->name('admin.social-posts.index');

        Route::get('/admin/tags', [TagWebController::class, 'index'])->name('admin.tags.index');

        Route::get('/admin/analytics/campaigns-dashboard', [CampaignWebController::class, 'analyticsDashboard'])->name('admin.campaign-analytics.dashboard');

        // Loyalty
        Route::get('/admin/loyalty', [LoyaltyProgramWebController::class, 'index'])->name('admin.loyalty.index');
        Route::post('/admin/loyalty', [LoyaltyProgramWebController::class, 'store'])->name('admin.loyalty.store');
        Route::put('/admin/loyalty/{loyaltyProgram}', [LoyaltyProgramWebController::class, 'update'])->name('admin.loyalty.update');
        Route::get('/admin/loyalty/tiers', [LoyaltyProgramWebController::class, 'tiers'])->name('admin.loyalty.tiers');
        Route::post('/admin/loyalty/tiers', [LoyaltyProgramWebController::class, 'storeTier'])->name('admin.loyalty.tiers.store');
        Route::put('/admin/loyalty/tiers/{loyaltyTier}', [LoyaltyProgramWebController::class, 'updateTier'])->name('admin.loyalty.tiers.update');
        Route::get('/admin/loyalty/rules', [LoyaltyProgramWebController::class, 'rules'])->name('admin.loyalty.rules');
        Route::post('/admin/loyalty/rules', [LoyaltyProgramWebController::class, 'storeRule'])->name('admin.loyalty.rules.store');
        Route::get('/admin/loyalty/redemption-rules', [LoyaltyProgramWebController::class, 'redemptionRules'])->name('admin.loyalty.redemption-rules');
        Route::post('/admin/loyalty/redemption-rules', [LoyaltyProgramWebController::class, 'storeRedemptionRule'])->name('admin.loyalty.redemption-rules.store');
        Route::get('/admin/loyalty/ledger', [LoyaltyProgramWebController::class, 'ledger'])->name('admin.loyalty.ledger');
        Route::get('/admin/loyalty/enrollments', [LoyaltyProgramWebController::class, 'enrollments'])->name('admin.loyalty.enrollments');

        // Quotes
        Route::get('/admin/quotes', [QuoteWebController::class, 'index'])->name('admin.quotes.index');
        Route::get('/admin/quotes/create', [QuoteWebController::class, 'create'])->name('admin.quotes.create');
        Route::post('/admin/quotes', [QuoteWebController::class, 'store'])->name('admin.quotes.store');
        Route::get('/admin/quotes/{quote}', [QuoteWebController::class, 'show'])->name('admin.quotes.show');
        Route::patch('/admin/quotes/{quote}/status', [QuoteWebController::class, 'updateStatus'])->name('admin.quotes.status');

        // Surveys
        Route::get('/admin/surveys', [SurveyWebController::class, 'index'])->name('admin.surveys.index');
        Route::post('/admin/surveys', [SurveyWebController::class, 'store'])->name('admin.surveys.store');
        Route::put('/admin/surveys/{survey}', [SurveyWebController::class, 'update'])->name('admin.surveys.update');
        Route::get('/admin/surveys/responses', [SurveyWebController::class, 'responses'])->name('admin.surveys.responses');

        // SLA
        Route::get('/admin/sla', [SlaWebController::class, 'index'])->name('admin.sla.index');
        Route::post('/admin/sla', [SlaWebController::class, 'store'])->name('admin.sla.store');
        Route::put('/admin/sla/{slaDefinition}', [SlaWebController::class, 'update'])->name('admin.sla.update');
        Route::get('/admin/sla/instances', [SlaWebController::class, 'instances'])->name('admin.sla.instances');

        // Onboarding
        Route::get('/admin/onboarding', [OnboardingWebController::class, 'index'])->name('admin.onboarding.index');
        Route::post('/admin/onboarding/templates', [OnboardingWebController::class, 'storeTemplate'])->name('admin.onboarding.templates.store');
        Route::get('/admin/onboarding/records', [OnboardingWebController::class, 'records'])->name('admin.onboarding.records');
        Route::get('/admin/onboarding/activities', [OnboardingWebController::class, 'activities'])->name('admin.onboarding.activities');

        // Guided Journeys
        Route::get('/admin/journeys', [GuidedJourneyWebController::class, 'index'])->name('admin.journeys.index');
        Route::post('/admin/journeys', [GuidedJourneyWebController::class, 'store'])->name('admin.journeys.store');
        Route::put('/admin/journeys/{guidedJourney}', [GuidedJourneyWebController::class, 'update'])->name('admin.journeys.update');
        Route::get('/admin/journeys/completions', [GuidedJourneyWebController::class, 'completions'])->name('admin.journeys.completions');

        // Reactivation
        Route::get('/admin/reactivation', [ReactivationWebController::class, 'index'])->name('admin.reactivation.index');
        Route::post('/admin/reactivation', [ReactivationWebController::class, 'store'])->name('admin.reactivation.store');
        Route::put('/admin/reactivation/{reactivationConfig}', [ReactivationWebController::class, 'update'])->name('admin.reactivation.update');
        Route::get('/admin/reactivation/contacts', [ReactivationWebController::class, 'contacts'])->name('admin.reactivation.contacts');
        Route::get('/admin/reactivation/analytics', [ReactivationWebController::class, 'analytics'])->name('admin.reactivation.analytics');
        Route::post('/admin/reactivation/{reactivationConfig}/run', [GuidedJourneyWebController::class, 'run'])->name('admin.reactivation.run');

        // Welcome Email Templates
        Route::get('/admin/welcome-email-templates', [WelcomeEmailTemplateController::class, 'index'])->name('admin.welcome-email-templates.index');
        Route::post('/admin/welcome-email-templates', [WelcomeEmailTemplateController::class, 'store'])->name('admin.welcome-email-templates.store');
        Route::put('/admin/welcome-email-templates/{template}', [WelcomeEmailTemplateController::class, 'update'])->name('admin.welcome-email-templates.update');
        Route::delete('/admin/welcome-email-templates/{template}', [WelcomeEmailTemplateController::class, 'destroy'])->name('admin.welcome-email-templates.destroy');

        // CLV Analytics
        Route::get('/admin/clv-analytics', [ClvAnalyticsWebController::class, 'index'])->name('admin.clv-analytics.index');
        Route::get('/admin/clv-analytics/calculations', [ClvAnalyticsWebController::class, 'calculations'])->name('admin.clv-analytics.calculations');
        Route::post('/admin/clv-analytics/recalculate', [ClvAnalyticsWebController::class, 'recalculate'])->name('admin.clv-analytics.recalculate');

        // Interactions
        Route::prefix('admin/interactions')->name('admin.interactions.')->group(function () {
            Route::get('/', [InteractionWebController::class, 'index'])->name('index');
            Route::get('/inbox', [InteractionWebController::class, 'inbox'])->name('inbox');
            Route::post('/', [InteractionWebController::class, 'store'])->name('store');
            Route::get('/channels', [InteractionWebController::class, 'channels'])->name('channels');
            Route::get('/unmatched', [InteractionWebController::class, 'unmatched'])->name('unmatched');
            Route::post('/unmatched/{unmatchedItem}/resolve', [InteractionWebController::class, 'resolveUnmatched'])->name('unmatched.resolve');
        });

        // OmniChannel
        Route::prefix('admin/omni')->name('admin.omni.')->group(function () {
            Route::get('/dashboard', [OmniChannelWebController::class, 'dashboard'])->name('dashboard');
            Route::get('/tickets', [OmniChannelWebController::class, 'tickets'])->name('tickets');
            Route::get('/contact-center', [OmniChannelWebController::class, 'contactCenter'])->name('contact-center');
            Route::get('/kiosk', [OmniChannelWebController::class, 'kiosk'])->name('kiosk');
            Route::post('/kiosk', [OmniChannelWebController::class, 'storeKiosk'])->name('kiosk.store');
        });

        // Assets
        Route::middleware(['permission:assets.manage'])->group(function () {
            Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
            Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
            Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])->name('assets.assign');
            Route::post('/assets/{asset}/return', [AssetController::class, 'returnAsset'])->name('assets.return');
            Route::get('/assets/export', [AssetController::class, 'export'])->name('assets.export');
        });

        Route::middleware(['permission:assets.view'])->group(function () {
            Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
            Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
        });

        // Invoices
        Route::middleware(['permission:invoices.manage'])->group(function () {
            Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
            Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        });

        Route::middleware(['permission:invoices.view'])->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        });

        Route::middleware(['permission:invoices.payments'])->group(function () {
            Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        });

        // Vendors
        Route::middleware(['permission:vendors.manage'])->group(function () {
            Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
            Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
            Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
            Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
            Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
            Route::post('/vendors/{vendor}/rate', [VendorController::class, 'addRating'])->name('vendors.rate');
        });

        Route::middleware(['permission:vendors.view'])->group(function () {
            Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
            Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
        });

        // Purchase Orders
        Route::middleware(['permission:procurement.create'])->group(function () {
            Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
            Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
            Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
            Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
            Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
            Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
            Route::post('/purchase-orders/{purchaseOrder}/vendor-invoices', [PurchaseOrderController::class, 'linkVendorInvoice'])->name('purchase-orders.vendor-invoices.store');
        });

        Route::middleware(['permission:procurement.approve'])->group(function () {
            Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        });

        // Finance Dashboard
        Route::middleware(['permission:analytics.finance'])->group(function () {
            Route::get('/finance', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
            Route::post('/finance/refresh', [FinanceController::class, 'refreshDashboard'])->name('finance.refresh');
        });

        // Banking Relationships
        Route::middleware(['permission:banking.manage'])->group(function () {
            Route::get('/banking/create', [BankingRelationshipController::class, 'create'])->name('banking.create');
            Route::post('/banking', [BankingRelationshipController::class, 'store'])->name('banking.store');
            Route::get('/banking/{bankingRelationship}/edit', [BankingRelationshipController::class, 'edit'])->name('banking.edit');
            Route::put('/banking/{bankingRelationship}', [BankingRelationshipController::class, 'update'])->name('banking.update');
            Route::delete('/banking/{bankingRelationship}', [BankingRelationshipController::class, 'destroy'])->name('banking.destroy');
        });

        Route::middleware(['permission:banking.view'])->group(function () {
            Route::get('/banking', [BankingRelationshipController::class, 'index'])->name('banking.index');
            Route::get('/banking/{bankingRelationship}', [BankingRelationshipController::class, 'show'])->name('banking.show');
        });

        // Employees
        Route::middleware(['permission:hr.manage'])->group(function () {
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        });

        Route::middleware(['permission:hr.view'])->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        });

        // Calendar
        Route::get('/calendar', [CalendarWebController::class, 'index'])->name('calendar.index');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Web\NotificationWebController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Web\NotificationWebController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Web\NotificationWebController::class, 'markAllRead'])->name('notifications.readAll');

        // Documentation Center
        Route::get('/docs', [DocsWebController::class, 'index'])->name('docs.index');
        Route::get('/docs/category/{category:slug}', [DocsWebController::class, 'category'])->name('docs.category');
        Route::get('/docs/{article:slug}', [DocsWebController::class, 'show'])->name('docs.show');
        Route::post('/docs/{article}/verify', [DocsWebController::class, 'verify'])->name('docs.verify');

        // Onboarding Checklist
        Route::get('/onboarding/checklist', [DocsWebController::class, 'onboardingChecklist'])->name('onboarding.checklist');
        Route::post('/onboarding/checklist/complete', [DocsWebController::class, 'completeItem'])->name('onboarding.checklist.complete');
        Route::post('/onboarding/checklist/dismiss', [DocsWebController::class, 'dismissChecklist'])->name('onboarding.checklist.dismiss');

        // Admin - Docs Dashboard
        Route::get('/admin/docs', [\App\Http\Controllers\Admin\DocsDashboardController::class, 'index'])->name('admin.docs.index');
        Route::post('/admin/docs/{docRequest}/resolve', [\App\Http\Controllers\Admin\DocsDashboardController::class, 'resolveRequest'])->name('admin.docs.resolve-request');
    });
});
