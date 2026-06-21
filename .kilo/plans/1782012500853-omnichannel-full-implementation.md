# OmniChannel CRM Enhancement Plan - Features 1-10

## Goal
Add quick-action initiation buttons to the unified interaction inbox so agents can initiate customer interactions without leaving the view.

## Critical Bug to Fix First
- `Interactions.vue` line 163 calls `submitInteraction()` but method is undefined
- This breaks the entire "Log Interaction" dialog - users cannot create interactions

## Implementation Tasks (Ordered)

### Phase 1: Fix Interactions.vue (Critical)
**File:** `resources/js/Pages/Admin/Interactions.vue`
- Add `submitInteraction()` method (lines 61-120 area): POST via router to `admin.interactions.store` with `channel_id`, `direction`, `subject`, `body`, `contact_id`, `occurred_at`
- Add agent filter dropdown (after line 205 in filters panel): Select with `agents` prop options
- Add `is_reviewed` filter (after line 197): Toggle or Select for reviewed/unreviewed
- Add Reply/Assign/Link Deal/Ticket buttons in detail panel (line 339-342):
  - Reply: Opens channel-specific composer based on interaction type
  - Assign: Dropdown of agents (visible when teamView=true)
  - Link Deal: Modal with deal search
- Add quick-action toolbar (line 154): Email, Call, SMS, WhatsApp, Facebook, LinkedIn buttons

### Phase 2: Enhance InteractionInbox.vue (Workspace Tab)
**File:** `resources/js/Pages/Admin/InteractionInbox.vue`
- Add `isTab` detection for toolbar visibility
- Add "Log Interaction" button when used as standalone page
- Add row click handler for quick reply mode on unread items

### Phase 3: Integrate Channel Composers
**Routes to use:**
- Email: `/admin/email/compose` (EmailCompose.vue) - pre-fill contact and subject
- SMS: `/admin/sms/compose` (SmsCompose.vue) - pre-fill contact
- Calls: `/admin/call/log` (CallLog.vue) - pre-fill contact
- Chat: `/admin/chat/inbox` (ChatInbox.vue) - accept session action

### Phase 4: Add Reassignment to OmniSupervisor.vue
**File:** `resources/js/Pages/Admin/OmniSupervisor.vue`
- Add real-time WebSocket listener for `contact-centre.stats`
- Add reassign dropdown in agent list (each agent row)
- Add historical chart using `history` data from API

### Phase 5: Add IVR Ingest Form
**File:** `resources/js/Pages/Admin/IvrTranscriptions.vue`
- Add "Ingest Transcript" button with JSON form
- Replace raw JSON path display with step-by-step component

### Phase 6: Add Kiosk Events Table
**File:** `resources/js/Pages/Admin/Kiosk.vue`
- Add kiosk events table from API
- Add "Log Kiosk Interaction" button

### Phase 7: Add Language Selector
**File:** `resources/js/Layouts/AppLayout.vue`
- Add language selector dropdown in header (line 473 area)
- Wire to `setI18nLocale()` from i18n.ts

## Validation Steps
1. Verify "Log Interaction" dialog works (creates interaction, appears in feed)
2. Test all channel quick buttons navigate/open composers
3. Test Reply action on email interaction opens EmailCompose
4. Test WebSocket updates appear without refresh
5. Verify IVR ingest creates interaction with proper channel type

## Risks
- Missing `POST /api/v1/interactions` endpoint - will use web route `admin.interactions.store`
- Echo WebSocket may need auth channel verification
- Channel types (whatsapp, facebook, linkedin, tiktok) need proper icons in `iconFor` mapping