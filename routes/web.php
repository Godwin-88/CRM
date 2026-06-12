<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CampaignWebController;
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

        // Surveys
        Route::get('/admin/surveys', [\App\Http\Controllers\Admin\SurveyWebController::class, 'index'])->name('admin.surveys.index');

        // SLA
        Route::get('/admin/sla', [\App\Http\Controllers\Admin\SlaWebController::class, 'index'])->name('admin.sla.index');

        // Onboarding
        Route::get('/admin/onboarding', [\App\Http\Controllers\Admin\OnboardingWebController::class, 'index'])->name('admin.onboarding.index');

        // Guided Journeys
        Route::get('/admin/journeys', [\App\Http\Controllers\Admin\GuidedJourneyWebController::class, 'index'])->name('admin.journeys.index');

        // Reactivation
        Route::get('/admin/reactivation', [\App\Http\Controllers\Admin\ReactivationWebController::class, 'index'])->name('admin.reactivation.index');

        // CLV Analytics
        Route::get('/admin/clv-analytics', [\App\Http\Controllers\Admin\ClvAnalyticsWebController::class, 'index'])->name('admin.clv-analytics.index');
    });
});

