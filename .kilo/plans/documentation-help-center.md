# Implementation Plan: Platform Documentation & Help Center (Section 4.13)

## Codebase Coverage Analysis (4.1 - 4.12)

Based on analysis of existing Vue pages and routes, here is the current UI implementation status:

### Section 4.1 - Contact & Account Management: 60% UI Coverage
**Implemented:** Contacts Index/Show/Edit, Accounts Index/Show  
**Missing UI:** Merge workflow, duplicate detection, timeline view, custom fields management, bulk import/export, scoring rules UI

### Section 4.2 - Sales Pipeline & Deal Management: 40% UI Coverage
**Implemented:** Deals Index, Deals Board (Kanban), Deals Form, Deals Show, Pipelines Index  
**Missing UI:** Deal automations, win/loss reasons modal, quotes/proposals integration, stage probability weighting in forecast, collaboration comments UI

### Section 4.3 - Customer Interactions & Omni-Channel: 35% UI Coverage
**Implemented:** Admin/InteractionInbox, Admin/InteractionChannels, Admin/UnmatchedItems, Admin/OmniChannelDashboard, Admin/ContactCenter, Admin/Kiosk  
**Missing UI:** Email compose/reply UI, call logging UI, chat widget, SMS composer, IVR ingestion, field channel mobile, queue stats real-time dashboard, language selector in header

### Section 4.4 - Marketing & Campaign Orchestration: 45% UI Coverage
**Implemented:** Campaigns Index/Show, Admin/CampaignTemplates, DripSequences Index, Admin/SocialPosts, Admin/Surveys  
**Missing UI:** Email drag-drop template editor, multi-channel step builder, A/B testing UI, schedule/throttle controls, tag management, analytics dashboard

### Section 4.5 - Loyalty & CX: 40% UI Coverage
**Implemented:** Admin/Loyalty, Admin/Onboarding (for workflows)  
**Missing UI:** Points ledger view, tier display on contact, survey responses, kiosk interactions, welcome email templates, reactivation analytics

### Section 4.6 - Support & Service: 55% UI Coverage
**Implemented:** Support/Tickets (Index/Create/Show), Support/KnowledgeBase (Index/Show), Admin/Tickets, Support/Performance  
**Missing UI:** Ticket merge/split UI, internal notes on tickets, canned responses picker, CSAT rating UI, SLA configuration UI, escalation workflow

### Section 4.7 - Analytics & Intelligence: 75% UI Coverage
**Implemented:** Admin/Analytics (Dashboard, CustomerAnalytics, GrowthAnalytics, FinanceAnalytics, ComplianceAnalytics, PredictiveScoring, ReportBuilder), Analytics/Forecast  
**Missing UI:** Exploratory analysis tool UI, revenue forecast detailed view, churn risk list, time-bucketed forecast view

### Section 4.8 - Contracts & Legal: 65% UI Coverage
**Implemented:** Contracts (Index/Show/Create/Edit), Admin/ContractTemplates (Index/Create/Edit), Legal (Index/Show/Create)  
**Missing UI:** Milestone/KPI tracking panel, renewal reminder UI, contract repository search/filter/export, e-signature initiation workflow, duplicate contract UI

### Section 4.9 - Back-Office & Finance: 70% UI Coverage
**Implemented:** Invoices (Index/Show/Create), PurchaseOrders (Index/Show/Create), Vendors (Index/Show/Create), Assets (Index/Show/Create), Banking (Index/Show/Create), Employees (Index/Show/Edit)  
**Missing UI:** Ledger summary panel, payment recording UI, bank details fields, headcount planning, facility expiry warnings, procurement approval workflow

### Section 4.10 - Security & Access Control: 25% UI Coverage
**Implemented:** Auth/MfaSetup, Auth/MfaVerify, Admin/SecurityEvents, Admin/PrivilegedSessionChallenge  
**Missing UI:** RBAC permission matrix UI, SSO provider configuration, privileged session indicator in header, data classification UI, DSR module (Create/Show/Index exist but limited)

### Section 4.11 - API & Integrations: 50% UI Coverage
**Implemented:** Admin/Integrations/Marketplace, Admin/ApiTokens, Admin/Integrations/Webhooks  
**Missing UI:** OAuth2 authorization screen, service registry UI, rate limit configuration UI, OpenAPI documentation portal

### Section 4.12 - Workspace & Collaboration: 30% UI Coverage
**Implemented:** Calendar/Index, Notifications/Index  
**Missing UI:** File attachment UI, discussion boards UI, shared team calendar UI, @mention dropdown, shared/team calendar

---

## Updated Implementation Strategy

Given the incomplete UI coverage, the documentation system must:
1. **Document existing features** - Cover what's already implemented
2. **Provide placeholder articles** - For features that exist in backend but lack UI
3. **Include "Coming Soon" markers** - For features not yet implemented
4. **Link to API docs** - For features that exist only in API layer

---

## Feature 1: Documentation Content Model & Authoring

### Changes Required:
1. **Migration**: Add new columns to `knowledge_base_articles`:
   - `audience` enum: `agent`, `manager`, `admin`, `all`
   - `feature_refs` JSON array (stores spec section refs like `4.2.2`, `4.8.4`)
   - `last_verified_at` timestamp (for staleness tracking)

2. **Model Update**: `KnowledgeBaseArticle.php`
   - Add `audience` to fillable/casts
   - Add `feature_refs` as JSON cast
   - Add `last_verified_at` cast
   - Add scope for audience filtering
   - Add `isStale()` method (checks 6-month verification)

3. **Controller**: Create `DocsWebController.php` with:
   - `index()` - documentation center landing
   - `show(KnowledgeBaseArticle $article)` - article display
   - `verify(Request $request, KnowledgeBaseArticle $article)` - set last verified date

4. **Permission**: `docs.manage` assigned to admin role by default

---

## Feature 2: Contextual In-App Help

### Changes Required:
1. **Component**: Create `HelpPanel.vue` slide-over component
   - Props: current route, user role-derived audience
   - Fetches relevant articles via `/api/v1/docs/contextual` endpoint
   - Displays max 5 ranked articles by feature_refs specificity
   - Search box at top
   - "Open in full view" link
   - "Request documentation" action for fallback case

2. **API Endpoint**: Add to `KnowledgeBaseController.php`:
   - `contextual(Request $request)` - returns articles filtered by route + audience
   - `recordView(Request $request)` - tracks view from route for analytics

3. **Layout Update**: `AppLayout.vue`
   - Add persistent help icon to header
   - Integrate HelpPanel component

---

## Feature 3: Full Documentation Centre

### Changes Required:
1. **Category Structure**: Create seeded `DocCategory` model or extend `KnowledgeBaseCategory`:
   - "Managing contacts and accounts" → contacts
   - "Running your sales pipeline" → pipelines/deals
   - "Customer conversations" → interactions
   - "Contracts & legal" → contracts
   - "Loyalty & CX" → loyalty/surveys
   - "Working together" → teams/collaboration
   - "Getting started" (pinned)

2. **Pages**: Create Vue pages:
   - `resources/js/Pages/Docs/Index.vue` - main center
   - `resources/js/Pages/Docs/Show.vue` - article detail
   - `resources/js/Pages/Docs/Category.vue` - category landing

3. **Routes**: Add to `web.php`:
   - `/docs` - center index
   - `/docs/{category}` - category view
   - `/docs/{category}/{article}` - article view

4. **Breadcrumbs**: Implement using Inertia breadcrumbs pattern

---

## Feature 4: Role-Based Onboarding Checklists

### Changes Required:
1. **Migration**: Add `user_doc_checklists` table:
   - `id` (primary)
   - `user_id` (foreign)
   - `checklist_item_key` string
   - `completed_at` timestamp (nullable)
   - `dismissed_at` timestamp (nullable)

2. **Models**: 
   - `UserDocChecklist.php` - tracks per-user completion
   - Seed checklist items in config or database

3. **Pages**: `resources/js/Pages/Onboarding/Checklist.vue`
   - Shows checklist for user's role(s)
   - Links to screens with automatic help panel open
   - Manual "mark complete" action

4. **Controller**: Add to `DocsWebController.php`:
   - `onboardingChecklist()` - returns user's checklist items
   - `completeItem(Request $request)` - marks item complete
   - `dismissChecklist()` - dismisses checklist

5. **First Login Middleware**: Check if user should see checklist

---

## Feature 5: Documentation Coverage & Feedback Loop

### Changes Required:
1. **Migration**: Create `doc_requests` table:
   - `id`
   - `screen_identifier` string
   - `user_id`
   - `comment` text (nullable)
   - `request_count` integer
   - `resolved_at` timestamp (nullable)

2. **Models**:
   - `DocRequest.php` - documentation requests
   - Add view tracking for articles in `UserDocChecklist` or separate table

3. **Controller**: `Admin/DocsDashboardController.php`
   - `index()` - maintainer dashboard
   - Shows: low helpfulness articles, stale articles, coverage gaps, request queue

4. **API**: Add endpoints to `KnowledgeBaseController.php`:
   - `helpfulRatio()` - returns ratio for stale/low-helpfulness check
   - `coverageGap()` - lists features without articles

5. **Coverage Gap Logic**: Predefined list of spec sections for cross-reference:
   - Sections 4.1-4.12, each numbered feature

---

## Implementation Order

1. **Database migrations** (Feature 1 & 5 data structures)
2. **Model updates** (audience field, methods)
3. **API endpoints** (search, contextual, feedback)
4. **Web routes & controllers** (full docs center)
5. **Vue components** (HelpPanel, Docs pages)
6. **Layout integration** (help icon in header)
7. **Onboarding system** (checklists, first-login flow)
8. **Maintainer dashboard**

---

## Key Decisions

1. **Reuse KnowledgeBaseArticle table** - Documentation articles use same table as KB articles, filtered by `audience` field and `feature_refs` tagging. This follows the spec's requirement.

2. **Signed URLs for media** - Use existing spatie-medialibrary + R2 driver for screenshots/videos at `docs/{article_id}/media/{filename}`

3. **Route-to-feature mapping** - Store mapping in config:
   ```php
   'route_feature_map' => [
     '/deals/board' => ['4.2.2', '4.2'], // Kanban board
     '/admin/loyalty' => ['4.5.1', '4.5'], // Loyalty configuration
   ]
   ```

4. **Audience derivation** - User's primary role determines default audience:
   - `agent` role → `agent` audience
   - `manager` role → `manager` audience
   - `admin` role → `admin` audience

5. **No duplicate API docs** - Link out to existing developer portal from docs center header

---

## Files to Create/Modify

### New Files:
- `app/Models/DocRequest.php`
- `app/Http/Controllers/DocsWebController.php`
- `app/Http/Controllers/Admin/DocsDashboardController.php`
- `resources/js/Pages/Docs/Index.vue`
- `resources/js/Pages/Docs/Show.vue`
- `resources/js/Pages/Docs/Category.vue`
- `resources/js/Pages/Onboarding/Checklist.vue`
- `resources/js/Components/HelpPanel.vue`

### Modified Files:
- `database/migrations/2026_06_12_100000_create_support_module_tables.php` (add columns)
- `app/Models/KnowledgeBaseArticle.php` (add audience, feature_refs, last_verified_at)
- `app/Http/Controllers/Api/V1/KnowledgeBaseController.php` (contextual endpoints)
- `routes/web.php` (docs routes)
- `routes/api.php` (contextual API routes)
- `resources/js/Layouts/AppLayout.vue` (help icon)