# Unified Interaction Inbox - Initiation Actions Enhancement

## Goal
Add quick-action initiation buttons to the unified interaction inbox so agents can initiate customer interactions without leaving the view.

## Key Deficiencies Identified
1. Interactions.vue has only generic "Log Interaction" - missing channel-specific quick buttons
2. InteractionInbox.vue (workspace tab) is read-only with no creation capability
3. Detail panel lacks Reply/Assign/Link actions
4. No quick-reply workflow from row click

## Implementation Plan

### 1. Add Quick-Action Toolbar (Interactions.vue)
- Add channel-specific buttons: Send Email, Make Call, Send SMS, WhatsApp, Facebook, LinkedIn, Instagram
- Each button opens appropriate composer modal or navigates to dedicated page
- Buttons grouped by primary channels (email, call, sms) and social channels

### 2. Enhance InteractionInbox.vue (Workspace Tab)
- Add same quick-action toolbar when used as tab
- Enable "quick reply" mode when row clicked on unread interactions
- Add assign button for incoming unassigned interactions

### 3. Add Reply Actions to Detail Panel (Interactions.vue)
- Add "Reply" button that triggers channel-specific action
- Add "Assign to Agent" dropdown for manager/team view
- Add "Link to Deal/Ticket" quick link buttons
- Add "Mark Reviewed" toggle visibility

### 4. Add Keyboard Shortcuts
- 'E' = Compose Email (on selected/interaction)
- 'S' = Send SMS
- 'C' = Log Call
- 'R' = Reply
- 'A' = Assign
- 'N' = Mark as Reviewed

### 5. Backend Updates Required
- Add `can_initiate` permission checks based on `interactions.*` permissions
- Ensure API endpoints exist for quick link actions (deal-link, ticket-link)

## Files to Modify
- `resources/js/Pages/Admin/Interactions.vue` - Add toolbar, enhance detail panel
- `resources/js/Pages/Admin/InteractionInbox.vue` - Add creation capabilities
- `app/Http/Controllers/Admin/InteractionWebController.php` - Add quick action endpoints if missing
- `app/Policies/InteractionPolicy.php` - Ensure initiation permissions covered