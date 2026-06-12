<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CampaignWebController;
use App\Http\Controllers\Admin\ClvAnalyticsWebController;
use App\Http\Controllers\Admin\GuidedJourneyWebController;
use App\Http\Controllers\Admin\InteractionWebController;
use App\Http\Controllers\Admin\LoyaltyProgramWebController;
use App\Http\Controllers\Admin\OmniChannelWebController;
use App\Http\Controllers\Admin\OnboardingWebController;
use App\Http\Controllers\Admin\QuoteWebController;
use App\Http\Controllers\Admin\ReactivationWebController;
use App\Http\Controllers\Admin\SurveyWebController;
use App\Http\Controllers\Admin\SlaWebController;
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

    // Pipelines
    Route::get('/pipelines', [PipelineController::class, 'index'])->name('pipelines.index');
    Route::get('/pipelines/{pipeline}/board', [PipelineController::class, 'showBoard'])->name('pipelines.board');

    // Support Routes
    Route::get('/support/tickets', [\App\Http\Controllers\Support\TicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support/tickets/create', [\App\Http\Controllers\Support\TicketController::class, 'create'])->name('support.tickets.create');
    Route::post('/support/tickets', [\App\Http\Controllers\Support\TicketController::class, 'store'])->name('support.tickets.store');
    Route::get('/support/tickets/{ticket}', [\App\Http\Controllers\Support\TicketController::class, 'show'])->name('support.tickets.show');
    Route::put('/support/tickets/{ticket}', [\App\Http\Controllers\Support\TicketController::class, 'update'])->name('support.tickets.update');
    Route::delete('/support/tickets/{ticket}', [\App\Http\Controllers\Support\TicketController::class, 'destroy'])->name('support.tickets.destroy');
    Route::post('/support/tickets/{ticket}/assign', [\App\Http\Controllers\Support\TicketController::class, 'assign'])->name('support.tickets.assign');
    Route::post('/support/tickets/{ticket}/escalate', [\App\Http\Controllers\Support\TicketController::class, 'escalate'])->name('support.tickets.escalate');
    Route::post('/support/tickets/{ticket}/resolve', [\App\Http\Controllers\Support\TicketController::class, 'resolve'])->name('support.tickets.resolve');
    Route::post('/support/tickets/{ticket}/close', [\App\Http\Controllers\Support\TicketController::class, 'close'])->name('support.tickets.close');

    Route::get('/support/knowledge-base', [\App\Http\Controllers\Support\KnowledgeBaseController::class, 'index'])->name('support.knowledge-base.index');
    Route::get('/support/knowledge-base/{article}', [\App\Http\Controllers\Support\KnowledgeBaseController::class, 'show'])->name('support.knowledge-base.show');
    Route::post('/support/knowledge-base/{article}/rate', [\App\Http\Controllers\Support\KnowledgeBaseController::class, 'rate'])->name('support.knowledge-base.rate');
    Route::post('/support/knowledge-base/{article}/link', [\App\Http\Controllers\Support\KnowledgeBaseController::class, 'linkToTicket'])->name('support.knowledge-base.link');

    Route::get('/support/performance', [\App\Http\Controllers\Support\PerformanceController::class, 'index'])->name('support.performance.index');

    Route::middleware('role:manager|admin')->group(function () {
        Route::get('/admin/support/categories', [\App\Http\Controllers\Admin\SupportCategoryController::class, 'index'])->name('admin.support.categories.index');
        Route::post('/admin/support/categories', [\App\Http\Controllers\Admin\SupportCategoryController::class, 'store'])->name('admin.support.categories.store');
        Route::put('/admin/support/categories/{ticketCategory}', [\App\Http\Controllers\Admin\SupportCategoryController::class, 'update'])->name('admin.support.categories.update');

        Route::get('/admin/support/forms', [\App\Http\Controllers\Admin\TicketFormController::class, 'index'])->name('admin.support.forms.index');
        Route::post('/admin/support/forms', [\App\Http\Controllers\Admin\TicketFormController::class, 'store'])->name('admin.support.forms.store');
        Route::put('/admin/support/forms/{ticketForm}', [\App\Http\Controllers\Admin\TicketFormController::class, 'update'])->name('admin.support.forms.update');

        Route::get('/admin/support/canned-responses', [\App\Http\Controllers\Admin\CannedResponseController::class, 'index'])->name('admin.support.canned-responses.index');
        Route::post('/admin/support/canned-responses', [\App\Http\Controllers\Admin\CannedResponseController::class, 'store'])->name('admin.support.canned-responses.store');

        Route::get('/admin/support/sla-breaches', [\App\Http\Controllers\Admin\SlaBreachController::class, 'index'])->name('admin.support.sla-breaches.index');
    });

    // Admin routes (manager+ access)
    Route::middleware(['role:manager|admin'])->group(function () {
        Route::get('/admin/pipelines', [\App\Http\Controllers\Admin\PipelineWebController::class, 'index'])->name('admin.pipelines.index');
        Route::get('/admin/win-loss-reasons', [\App\Http\Controllers\Admin\WinLossReasonWebController::class, 'index'])->name('admin.win-loss-reasons.index');
        Route::get('/admin/quote-templates', [\App\Http\Controllers\Admin\QuoteTemplateWebController::class, 'index'])->name('admin.quote-templates.index');
        Route::get('/admin/scoring-rules', [\App\Http\Controllers\Admin\ScoringRuleWebController::class, 'index'])->name('admin.scoring-rules.index');

        // Campaigns
        Route::get('/admin/campaigns', [\App\Http\Controllers\Admin\CampaignWebController::class, 'index'])->name('admin.campaigns.index');
        Route::get('/admin/campaigns/create', [\App\Http\Controllers\Admin\CampaignWebController::class, 'create'])->name('admin.campaigns.create');
        Route::get('/admin/analytics/campaigns', [\App\Http\Controllers\Admin\CampaignWebController::class, 'analytics'])->name('admin.campaign-analytics.index');
        Route::get('/admin/campaigns/{campaign}', [\App\Http\Controllers\Admin\CampaignWebController::class, 'show'])->name('admin.campaigns.show');

        // Campaign Templates
        Route::get('/admin/campaign-templates', [\App\Http\Controllers\Admin\CampaignTemplateWebController::class, 'index'])->name('admin.campaign-templates.index');

        // Drip Sequences
        Route::get('/admin/drip-sequences', [\App\Http\Controllers\DripSequenceWebController::class, 'index'])->name('admin.drip-sequences.index');

        // Social Posts
        Route::get('/admin/social-posts', [\App\Http\Controllers\Admin\SocialPostWebController::class, 'index'])->name('admin.social-posts.index');

        // Loyalty
        Route::get('/admin/loyalty', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'index'])->name('admin.loyalty.index');
        Route::post('/admin/loyalty', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'store'])->name('admin.loyalty.store');
        Route::put('/admin/loyalty/{loyaltyProgram}', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'update'])->name('admin.loyalty.update');
        Route::get('/admin/loyalty/tiers', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'tiers'])->name('admin.loyalty.tiers');
        Route::post('/admin/loyalty/tiers', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'storeTier'])->name('admin.loyalty.tiers.store');
        Route::put('/admin/loyalty/tiers/{loyaltyTier}', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'updateTier'])->name('admin.loyalty.tiers.update');
        Route::get('/admin/loyalty/rules', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'rules'])->name('admin.loyalty.rules');
        Route::post('/admin/loyalty/rules', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'storeRule'])->name('admin.loyalty.rules.store');
        Route::get('/admin/loyalty/redemption-rules', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'redemptionRules'])->name('admin.loyalty.redemption-rules');
        Route::post('/admin/loyalty/redemption-rules', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'storeRedemptionRule'])->name('admin.loyalty.redemption-rules.store');
        Route::get('/admin/loyalty/ledger', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'ledger'])->name('admin.loyalty.ledger');
        Route::get('/admin/loyalty/enrollments', [\App\Http\Controllers\Admin\LoyaltyProgramWebController::class, 'enrollments'])->name('admin.loyalty.enrollments');

        // Quotes
        Route::get('/admin/quotes', [\App\Http\Controllers\Admin\QuoteWebController::class, 'index'])->name('admin.quotes.index');
        Route::get('/admin/quotes/create', [\App\Http\Controllers\Admin\QuoteWebController::class, 'create'])->name('admin.quotes.create');
        Route::post('/admin/quotes', [\App\Http\Controllers\Admin\QuoteWebController::class, 'store'])->name('admin.quotes.store');
        Route::get('/admin/quotes/{quote}', [\App\Http\Controllers\Admin\QuoteWebController::class, 'show'])->name('admin.quotes.show');
        Route::patch('/admin/quotes/{quote}/status', [\App\Http\Controllers\Admin\QuoteWebController::class, 'updateStatus'])->name('admin.quotes.status');

        // Surveys
        Route::get('/admin/surveys', [\App\Http\Controllers\Admin\SurveyWebController::class, 'index'])->name('admin.surveys.index');
        Route::post('/admin/surveys', [\App\Http\Controllers\Admin\SurveyWebController::class, 'store'])->name('admin.surveys.store');
        Route::put('/admin/surveys/{survey}', [\App\Http\Controllers\Admin\SurveyWebController::class, 'update'])->name('admin.surveys.update');
        Route::get('/admin/surveys/responses', [\App\Http\Controllers\Admin\SurveyWebController::class, 'responses'])->name('admin.surveys.responses');

        // SLA
        Route::get('/admin/sla', [\App\Http\Controllers\Admin\SlaWebController::class, 'index'])->name('admin.sla.index');
        Route::post('/admin/sla', [\App\Http\Controllers\Admin\SlaWebController::class, 'store'])->name('admin.sla.store');
        Route::put('/admin/sla/{slaDefinition}', [\App\Http\Controllers\Admin\SlaWebController::class, 'update'])->name('admin.sla.update');
        Route::get('/admin/sla/instances', [\App\Http\Controllers\Admin\SlaWebController::class, 'instances'])->name('admin.sla.instances');

        // Onboarding
        Route::get('/admin/onboarding', [\App\Http\Controllers\Admin\OnboardingWebController::class, 'index'])->name('admin.onboarding.index');
        Route::post('/admin/onboarding/templates', [\App\Http\Controllers\Admin\OnboardingWebController::class, 'storeTemplate'])->name('admin.onboarding.templates.store');
        Route::get('/admin/onboarding/records', [\App\Http\Controllers\Admin\OnboardingWebController::class, 'records'])->name('admin.onboarding.records');
        Route::get('/admin/onboarding/activities', [\App\Http\Controllers\Admin\OnboardingWebController::class, 'activities'])->name('admin.onboarding.activities');

        // Guided Journeys
        Route::get('/admin/journeys', [\App\Http\Controllers\Admin\GuidedJourneyWebController::class, 'index'])->name('admin.journeys.index');
        Route::post('/admin/journeys', [\App\Http\Controllers\Admin\GuidedJourneyWebController::class, 'store'])->name('admin.journeys.store');
        Route::put('/admin/journeys/{guidedJourney}', [\App\Http\Controllers\Admin\GuidedJourneyWebController::class, 'update'])->name('admin.journeys.update');
        Route::get('/admin/journeys/completions', [\App\Http\Controllers\Admin\GuidedJourneyWebController::class, 'completions'])->name('admin.journeys.completions');

        // Reactivation
        Route::get('/admin/reactivation', [\App\Http\Controllers\Admin\ReactivationWebController::class, 'index'])->name('admin.reactivation.index');
        Route::post('/admin/reactivation', [\App\Http\Controllers\Admin\ReactivationWebController::class, 'store'])->name('admin.reactivation.store');
        Route::put('/admin/reactivation/{reactivationConfig}', [\App\Http\Controllers\Admin\ReactivationWebController::class, 'update'])->name('admin.reactivation.update');
        Route::get('/admin/reactivation/contacts', [\App\Http\Controllers\Admin\ReactivationWebController::class, 'contacts'])->name('admin.reactivation.contacts');

        // CLV Analytics
        Route::get('/admin/clv-analytics', [\App\Http\Controllers\Admin\ClvAnalyticsWebController::class, 'index'])->name('admin.clv-analytics.index');
        Route::get('/admin/clv-analytics/calculations', [\App\Http\Controllers\Admin\ClvAnalyticsWebController::class, 'calculations'])->name('admin.clv-analytics.calculations');
        Route::post('/admin/clv-analytics/recalculate', [\App\Http\Controllers\Admin\ClvAnalyticsWebController::class, 'recalculate'])->name('admin.clv-analytics.recalculate');

        // Interactions
        Route::prefix('admin/interactions')->name('admin.interactions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\InteractionWebController::class, 'index'])->name('index');
            Route::get('/inbox', [\App\Http\Controllers\Admin\InteractionWebController::class, 'inbox'])->name('inbox');
            Route::post('/', [\App\Http\Controllers\Admin\InteractionWebController::class, 'store'])->name('store');
            Route::get('/channels', [\App\Http\Controllers\Admin\InteractionWebController::class, 'channels'])->name('channels');
            Route::get('/unmatched', [\App\Http\Controllers\Admin\InteractionWebController::class, 'unmatched'])->name('unmatched');
            Route::post('/unmatched/{unmatchedItem}/resolve', [\App\Http\Controllers\Admin\InteractionWebController::class, 'resolveUnmatched'])->name('unmatched.resolve');
        });

        // OmniChannel
        Route::prefix('admin/omni')->name('admin.omni.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\OmniChannelWebController::class, 'dashboard'])->name('dashboard');
            Route::get('/tickets', [\App\Http\Controllers\Admin\OmniChannelWebController::class, 'tickets'])->name('tickets');
            Route::get('/contact-center', [\App\Http\Controllers\Admin\OmniChannelWebController::class, 'contactCenter'])->name('contact-center');
            Route::get('/kiosk', [\App\Http\Controllers\Admin\OmniChannelWebController::class, 'kiosk'])->name('kiosk');
            Route::post('/kiosk', [\App\Http\Controllers\Admin\OmniChannelWebController::class, 'storeKiosk'])->name('kiosk.store');
        });

        // Pipelines (admin with stage management)
        Route::post('/admin/pipelines/{pipeline}/stages', [\App\Http\Controllers\Admin\PipelineController::class, 'storeStage'])->name('admin.pipelines.stages.store');
        Route::put('/admin/pipelines/stages/{stage}', [\App\Http\Controllers\Admin\PipelineController::class, 'updateStage'])->name('admin.pipelines.stages.update');
        Route::delete('/admin/pipelines/stages/{stage}', [\App\Http\Controllers\Admin\PipelineController::class, 'destroyStage'])->name('admin.pipelines.stages.destroy');
    });
});

