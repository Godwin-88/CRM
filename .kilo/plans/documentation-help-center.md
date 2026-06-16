# Section 4.13 Implementation Plan

Two separate deliverables in one focused implementation pass.

---

## 1. Onboarding Checklist Slider/Modal (Feature 4)

### Current state
- Backend fully implemented: `DocsWebController::onboardingChecklist`, routes, `UserDocChecklist` model, `config/docs.php` checklist items.
- Frontend page exists: `Onboarding/Checklist.vue` renders items, marks complete, dismisses.
- **Gap**: There is no automatic entry point to this page. A new user never sees it because nothing triggers the visit.

### Implementation

#### 1a. Add middleware / gate logic (optional but preferred)
Add a lightweight check either in a route middleware or in `AppLayout.vue` `onMounted` that:
- Fetches `/onboarding/checklist` (Inertia endpoint)
- If the returned checklist has any incomplete, non-dismissed items **and** the user has never been shown the checklist before → open the slider.

To avoid an extra API hit on every page load, persist state client-side only:
- Check `localStorage.getItem('onboarding_checklist_seen')` once on first load.
- If absent, open a `<Dialog>` or `<Slideover>` in `AppLayout.vue` containing the `Onboarding/Checklist.vue` (or inline the checklist markup).
- Set the localStorage flag on open (idempotent, just a UX flag, not authoritative).
- Once the user explicitly dismisses the checklist OR all items are complete, set the flag to `'true'`.

**Decision**: Use a Dialog/Slideover wrapper inside `AppLayout.vue` rather than requiring navigation to a separate page. The spec says "on first login" — a modal that appears over the dashboard is the standard pattern. The existing `Onboarding/Checklist.vue` page can remain as the permanent "Reopen" destination from the docs centre.

#### 1b. Add "Getting started" entry in docs centre (Docs/Index.vue)
Update `Docs/Index.vue` to show a pinned "Getting started" card at the top, linking to `/onboarding/checklist`.
This is already partially implied by `config/docs.php` having checklist items; no new backend needed.

---

## 2. Populate Help Panel Content (Features 1–3, 5)

### Root cause
`config/docs.php` `route_feature_map` has **8 entries** only. The app has **200+ routes**. The backend `KnowledgeBaseController::contextual` returns zero matching articles because:
1. The map does not match most routes.
2. The DB has no `knowledge_base_articles`.

### Implementation

#### 2a. Expand `config/docs.php`
Enumerate `routes/web.php` and add every major `path` to `route_feature_map` under the matching `section.subsection` format from the spec.

Key additions per section (example list, full file will be exhaustive):
- `4.1` contacts/accounts segments → `contacts`, `contacts/{id}`, `accounts`, `account/{id}`, `segments`
- `4.2` deals/pipelines → `deals`, `deals/create`, `deals/board`, `pipelines`, `pipelines/{id}/board`
- `4.3` omni-channel, tickets, kiosk, campaigns → all `support/tickets/*`, `admin/omni/*`, kiosk routes
- `4.4` campaigns/templates → `admin/campaigns`, `admin/campaign-templates`, `admin/email-templates`
- `4.5` loyalty/surveys → `admin/loyalty/*`, `admin/surveys`, `admin/kiosk`, `admin/welcome-email-templates`
- `4.6` support kb → keep existing + all ticket CRUD routes
- `4.7` analytics → all `admin/analytics/*`
- `4.8` contracts/quotes → `admin/contracts/`, contracts routes, quote routes
- `4.9` finance/vendors/employees → `admin/invoices`, `vendors`, `purchase-orders`, `assets`, `banking`, `employees`
- `4.10` security → `admin/security/*`, `mfa/*`, `admin/privileged/*`, `admin/dsr`, `admin/rbac/*` (add RBAC routes if they exist)
- `4.11` integrations → `admin/integrations/*`, `api-tokens`, `oauth-clients`, `webhooks`, `/docs` (developer portal)
- `4.12` calendar/notifications → `admin/calendar/*`, `notifications`, `discussion-boards`

Each key is the route path string (e.g. `contacts/{contact}`), ordered longest/most specific first. Generic parent routes come last so specificity scoring works.

#### 2b. Write `database/seeders/DocsSeeder.php`
Populate `knowledge_base_articles` with ~35–45 published articles matching spec sections. Each article:
- `status` = `published`
- `audience` = `agent|manager|admin|all` (as appropriate; configuration pages get `admin`, view-only pages get `agent`, dual-get `manager`)
- `feature_refs` = `['4.1.1']` etc.
- `category_id` = mapped to an existing `KnowledgeBaseCategory` (create seed lookup or hard-code IDs after reading the table)
- `body` = HTML Div with step-by-step how-to guidance

Article targets (at minimum one per spec subsection):
- 4.1.1 Contact Management, 4.1.2 Account Management, 4.1.3 Merge Contacts, 4.1.5 Timeline View, 4.1.7 Bulk Import/Export, 4.1.8 Scoring Rules
- 4.2.1 Deal Management, 4.2.2 Pipeline Kanban, 4.2.3 Deal Automations, 4.2.4 Win/Loss Reasons
- 4.3.1 Omni-Channel Dashboard, 4.3.2 Interaction Inbox, 4.3.3 Channels Configuration, 4.3.5 Contact Center, 4.3.6 Kiosk, 4.3.7 Email Composer, 4.3.8 Call Logging
- 4.4.1 Campaigns, 4.4.2 Campaign Templates, 4.4.3 Email Template Editor
- 4.5.1 Loyalty Program, 4.5.2 Points Ledger, 4.5.4 Surveys, 4.5.5 Survey Responses, 4.5.6 Kiosk Interactions
- 4.6.1 Ticket Management, 4.6.2 Knowledge Base, 4.6.5 Canned Responses, 4.6.6 CSAT Ratings
- 4.7.1 Analytics Dashboard, 4.7.2 Customer Analytics, 4.7.3 Growth Analytics, 4.7.7 Report Builder, 4.7.10 Churn Risk
- 4.8.1 Contract Management, 4.8.2 Contract Creation, 4.8.4 Milestone Tracking, 4.8.6 E-Signature
- 4.9.1 Invoice Management, 4.9.2 Payment Recording, 4.9.5 Asset Management, 4.9.7 Ledger Summary
- 4.10.1 MFA, 4.10.2 Security Events, 4.10.4 RBAC Matrix
- 4.11.1 Integration Marketplace, 4.11.2 API Tokens, 4.11.3 Webhooks, 4.11.7 OpenAPI Docs
- 4.12.1 Calendar, 4.12.2 Notifications, 4.12.3 File Attachments, 4.12.4 Discussion Boards

For audience assignment:
- `admin` → configuration/setup screens (`admin/pipelines`, `admin/integrations/marketplace`, `admin/rbac`, `admin/contracts`)
- `manager` → analytics, campaign, team overviews
- `agent` → contact/deal/ticket views, omni-channel
- `all` → generic dashboard, contact show page

#### 2c. Ensure `DocRequest` fallback creates records
The `HelpPanel.vue` already calls `/api/v1/doc-requests` on empty result. This is wired to `firstOrCreate` + increment. No code change required; verify it works once the API route is live (it already is in `routes/api.php:169`).

#### 2d. Docs centre full view (Docs/Index.vue)
Already functional once articles exist. No new feature needed for the MVP, but add a "Getting started" pinned section by grouping category `slug=getting-started` (or simply rendering a manual card in the template).

---

## 3. Verification Checklist

After code changes, verify in order:
1. `php -l database/seeders/DocsSeeder.php`
2. `php -l config/docs.php` (just validates array syntax)
3. `php artisan db:seed --class=DocsSeeder`
4. Login as agent → visit `/contacts` → click Help icon → 3–5 articles appear in panel
5. Login as admin → visit `/admin/integrations/marketplace` → click Help → admin-audience articles appear
6. Login as new user → visit `/` → onboarding slider appears once, dismisses
7. Re-visit `/` → slider does NOT reappear unless all checklist items marked incomplete again
8. Docs centre `/docs` renders categories + article counts
9. Docs article page `/docs/{slug}` renders and rate buttons work
10. Submit doc request from empty panel → record created in `doc_requests` table

---

## Files Modified

| File | Action |
|------|--------|
| `config/docs.php` | Major `route_feature_map` expansion |
| `database/seeders/DocsSeeder.php` | **New** – populate docs articles |
| `resources/js/Layouts/AppLayout.vue` | Add onboarding checklist dialog + localStorage gate |
| `resources/js/Pages/Onboarding/Checklist.vue` | No changes (reuse as-is), OR extract to a Dialog prop component |
| `resources/js/Pages/Docs/Index.vue` | Add "Getting started" pinned section |
| `resources/js/Components/HelpPanel.vue` | Minor: suppress `feature_refs` display (currently leaks metadata to end users) |

## Files Not Touched
- `KnowledgeBaseController`, `DocsWebController` – already correct
- `DocRequest` model/route – already correct
- `Docs/Show.vue` – rendering is fine
