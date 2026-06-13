<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AnalyticsWebController;
use App\Http\Controllers\Admin\CampaignTemplateWebController;
use App\Http\Controllers\Admin\CampaignWebController;
use App\Http\Controllers\Admin\CannedResponseController;
use App\Http\Controllers\Admin\ClvAnalyticsWebController;
use App\Http\Controllers\Admin\ContractTemplateController;
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
use App\Http\Controllers\Admin\TicketFormController;
use App\Http\Controllers\Admin\WinLossReasonWebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DripSequenceWebController;
use App\Http\Controllers\LegalMatterController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\Support\KnowledgeBaseController;
use App\Http\Controllers\Support\PerformanceController;
use App\Http\Controllers\Support\TicketController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Authentication ───────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email tracking redirect
Route::get('/t/{token}', [TrackingController::class, 'redirect'])->name('tracking.redirect');
Route::get('/open/{token}', [TrackingController::class, 'openPixel'])->name('tracking.open');

// Home page - public access
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// ─── Auth required routes ─────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
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
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

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
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
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
    Route::get('/legal/{legalMatter}', [LegalMatterController::class, 'show'])->name('legal.show');
    Route::get('/legal/create', [LegalMatterController::class, 'create'])->name('legal.create');
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

        // Campaigns
        Route::get('/admin/campaigns', [CampaignWebController::class, 'index'])->name('admin.campaigns.index');
        Route::get('/admin/campaigns/create', [CampaignWebController::class, 'create'])->name('admin.campaigns.create');
        Route::get('/admin/analytics/campaigns', [CampaignWebController::class, 'analytics'])->name('admin.campaign-analytics.index');
        Route::get('/admin/campaigns/{campaign}', [CampaignWebController::class, 'show'])->name('admin.campaigns.show');

        // Campaign Templates
        Route::get('/admin/campaign-templates', [CampaignTemplateWebController::class, 'index'])->name('admin.campaign-templates.index');

        // Drip Sequences
        Route::get('/admin/drip-sequences', [DripSequenceWebController::class, 'index'])->name('admin.drip-sequences.index');

        // Social Posts
        Route::get('/admin/social-posts', [SocialPostWebController::class, 'index'])->name('admin.social-posts.index');

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

        // Pipelines (admin with stage management)
        Route::post('/admin/pipelines/{pipeline}/stages', [App\Http\Controllers\Admin\PipelineController::class, 'storeStage'])->name('admin.pipelines.stages.store');
        Route::put('/admin/pipelines/stages/{stage}', [App\Http\Controllers\Admin\PipelineController::class, 'updateStage'])->name('admin.pipelines.stages.update');
        Route::delete('/admin/pipelines/stages/{stage}', [App\Http\Controllers\Admin\PipelineController::class, 'destroyStage'])->name('admin.pipelines.stages.destroy');
    });
});
