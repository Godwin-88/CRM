# Section 4.14: AI CRM Assistant (ML-Service Layer Agent)

## Overview

This module specifies an embedded, agentic assistant — distinct from the documentation/help center (4.13) and distinct from a passive chatbot. The assistant sits as its own service within the architecture (alongside the Laravel application, not inside it), has tool-calling access into the CRM's actual data and actions via authenticated internal APIs, and is grounded in the documentation corpus from 4.13. Its job is twofold: **navigate** (understand what the user is trying to do and take them there, pre-filled where possible) and **execute** (perform permitted actions directly when the user asks, subject to the same RBAC as if they'd clicked through the UI themselves). Every module in 4.1–4.12 is a covered domain; the assistant is the single conversational entry point across all of them.

---

## Feature 1: ML-service architecture and placement

**Story:** As a platform architect, I need the assistant to run as an isolated service so that its model calls, latency, and failure modes don't affect the core Laravel application.

Acceptance criteria:
- The assistant runs as a separate service (`crm-assistant-service`) within the existing Docker Compose / deployment architecture, communicating with the Laravel application via a dedicated internal API namespace `/api/v1/assistant/*`, never directly querying the database.
- The Laravel application exposes a constrained internal "agent tool API" — a curated set of endpoints the assistant is permitted to call, distinct from the general `/api/v1/` surface in section 4.11. This separation means the assistant's capabilities are explicitly allowlisted, not implicitly inherited from whatever the REST API happens to expose.
- Each agent tool API endpoint maps to one discrete, named capability (e.g. `tool.contacts.search`, `tool.deals.move_stage`, `tool.tickets.create`, `tool.reports.run`). New tools require an explicit registration entry — there is no generic "execute arbitrary query" tool.
- All requests from the assistant service to the agent tool API carry the originating user's identity (via a short-lived, scoped internal token minted at conversation start), ensuring every tool call is subject to that user's RBAC permissions (section 4.10 Feature 1) exactly as if performed through the UI. The assistant has no standing credentials of its own beyond service-to-service authentication.
- The assistant service connects to an LLM provider (configurable — Anthropic API per the existing `anthropic_api_in_artifacts` pattern used elsewhere, or self-hosted) via the `HttpClientService` circuit breaker pattern established in section 4.9 Feature 9 and mandated in section 4.11 Feature 10 for all outbound third-party calls.
- The assistant service is stateless between conversation turns from an infrastructure perspective — conversation history and tool-call context are persisted in Redis (keyed per conversation session) and reloaded on each turn, consistent with the "no memory between completions" pattern in the `context_window_management` guidance for AI-powered features.
- If the assistant service is unreachable or the LLM provider call fails, the chat popup degrades gracefully to the static documentation search (section 4.13 Feature 3) rather than showing an error state — the user always has a fallback path to help.

---

## Feature 2: Conversational entry point — the chat popup

**Story:** As any user, I can open a chat popup from anywhere in the CRM and describe what I want in plain language so that I don't need to know which module or screen handles it.

Acceptance criteria:
- A persistent chat popup icon is available in the application header, distinct from the contextual help icon (section 4.13 Feature 2) — the help icon shows documentation; the assistant icon opens a conversational interface. Both can co-exist; clicking the assistant icon does not close the help panel and vice versa.
- The popup opens as a slide-over panel (consistent with the help panel's non-disruptive pattern from 4.13 Feature 2), preserving the user's current screen and task underneath.
- The assistant receives, with every message, the user's current route/screen context (e.g. "user is viewing Deal #4521 on the Kanban board") as part of its system context — this allows responses like "I can move this deal to Proposal stage right now, want me to?" without the user needing to specify which deal.
- The popup supports both free-text input and quick-reply suggestion chips for common follow-ups (e.g. after the assistant answers a question about SLA breaches, chips might offer "Show me breached tickets" or "Open SLA settings").
- Conversation history within a session persists across screen navigations (the popup can be closed and reopened without losing context) but is not retained indefinitely — sessions expire after 60 minutes of inactivity, after which a new conversation starts. This mirrors the self-service portal's session timeout pattern (section 4.5 Feature 7) for consistency.
- The popup is available to all authenticated roles (agent, manager, admin) and to contacts in the self-service portal (section 4.5 Feature 7) as a separate, more constrained instance — see Feature 7 for the customer-facing scope distinction.
- Every assistant response that references a CRM record, screen, or action includes inline clickable links/buttons — never plain text describing where to go. "Navigate to X" is always an actual navigation action, not an instruction to the user to find it themselves.

---

## Feature 3: Intent classification and routing across 4.1–4.12

**Story:** As a user, when I describe a problem in my own words, the assistant correctly identifies which module and feature it relates to so that I land on the right functionality without knowing the CRM's structure.

Acceptance criteria:
- The assistant's system prompt includes a structured index of all features across sections 4.1–4.12 (the same `feature_refs` taxonomy established in section 4.13 Feature 1), giving it a complete map of "what exists and where" without needing to search documentation for basic routing decisions.
- For ambiguous requests (e.g. "I need to follow up with someone" could relate to activities in 4.2 Feature 6, the unified inbox in 4.3 Feature 1, or a drip sequence in 4.4 Feature 6), the assistant asks one clarifying question with 2–3 concrete options rather than guessing — consistent with the `ask_user_input_v0` pattern of narrowing before acting, not after.
- Once intent is resolved, the assistant's response falls into one of three categories, and it tells the user which kind of help it's giving: **navigate** ("Here's the link to..."), **explain** (pulls from 4.13 documentation and answers inline), or **execute** (performs an action directly, subject to Feature 4's confirmation rules).
- Routing is grounded against the live documentation corpus from section 4.13 — the assistant performs a retrieval call against the Meilisearch/Scout-indexed documentation articles (4.13 Feature 1) before responding to "how do I / what is / where is" style questions, rather than relying solely on the system prompt's static feature index. The static index handles routing; the retrieved articles handle explanation accuracy.
- If the assistant's retrieval returns no relevant documentation for a request (the same gap-detection mechanism as section 4.13 Feature 5), it still attempts to route based on the static feature index but flags lower confidence to the user ("I think this relates to contract renewals, but I don't have detailed docs on this yet — here's the closest screen") and logs a documentation request identical to the 4.13 Feature 5 mechanism, attributing it to the assistant rather than the help panel.
- Cross-module requests are explicitly supported — e.g. "show me all deals where the customer has an open high-priority ticket" spans 4.2 (deals) and 4.6 (tickets). The assistant decomposes such requests into multiple tool calls (Feature 5) and synthesizes a combined response, rather than only handling single-module intents.

---

## Feature 4: Guided navigation with pre-filled context

**Story:** As a user, when the assistant sends me to a screen, I want it to already be in the state I need — filtered, pre-filled, or scrolled to the right place — so that navigation actually saves me steps.

Acceptance criteria:
- Navigation links generated by the assistant carry query parameters or application state that pre-configure the destination screen wherever the underlying screen supports it — e.g. "show me overdue tickets for the Acme account" navigates to the ticket list (section 4.6 Feature 1) pre-filtered by account and an overdue/SLA-breach condition (section 4.6 Feature 2), not just to the generic ticket list.
- For multi-step UI flows (e.g. campaign builder section 4.4 Feature 1, contract generation section 4.8 Feature 2), the assistant can deep-link directly into a specific step of the flow if the user's request implies they're past the earlier steps — e.g. "I already picked the NDA template, I just need to fill in the variables" links to the variable fill screen of contract generation, not the template selection screen.
- Where a destination screen is a record detail view (contact, deal, ticket, contract, account), the assistant resolves ambiguous references using the agent tool API's search capabilities (Feature 5) before navigating — "open John's deal" triggers a contact search, and if multiple contacts named John exist, the assistant disambiguates via a short clarifying question (consistent with Feature 3) rather than navigating to an arbitrary match.
- The assistant respects the same RBAC-scoped visibility as the destination screen — it never generates a navigation link to a record or screen the current user doesn't have permission to view (section 4.10 Feature 1). If a request would require permissions the user lacks, the assistant says so plainly and, where applicable, identifies who does have access (e.g. "Your manager [Name] can configure this — want me to draft a request?").
- For configuration/admin screens (pipeline setup 4.2 Feature 1, SLA policies 4.6 Feature 2, RBAC matrix 4.10 Feature 1, connector marketplace 4.11 Feature 6), the assistant can pre-fill a configuration form's fields based on the conversation — e.g. "set up a new pipeline for renewals with three stages: contacted, negotiating, renewed" navigates to pipeline creation (4.2 Feature 1) with the name and stage list pre-populated, requiring only the user's review and save action, not blind submission.

---

## Feature 5: Tool-calling capabilities (agent tool API surface)

**Story:** As a CRM engineer, I need a well-defined, growable set of tools the assistant can call so that its capabilities map cleanly to the existing module specs without becoming an unbounded attack surface.

Acceptance criteria:
- Tools are grouped by module, with each tool corresponding to a specific feature from 4.1–4.12. A representative (non-exhaustive) initial set:
  - **4.1 Contacts/Accounts**: `tool.contacts.search`, `tool.contacts.get_timeline`, `tool.contacts.create`, `tool.segments.preview_count`
  - **4.2 Pipeline**: `tool.deals.search`, `tool.deals.move_stage`, `tool.deals.get_forecast`, `tool.activities.create`
  - **4.3 Interactions**: `tool.inbox.search`, `tool.interactions.create_call_log`, `tool.contact_centre.get_stats`
  - **4.4 Campaigns**: `tool.campaigns.get_status`, `tool.segments.preview_count` (shared with 4.1), `tool.campaigns.get_analytics`
  - **4.5 Experience/Loyalty**: `tool.loyalty.get_balance`, `tool.surveys.get_results`, `tool.clv.get_score`
  - **4.6 Support**: `tool.tickets.search`, `tool.tickets.create`, `tool.tickets.update_status`, `tool.kb.search` (shared retrieval with 4.13)
  - **4.7 Analytics**: `tool.reports.run`, `tool.dashboards.get_summary`, `tool.analytics.get_metric`
  - **4.8 Contracts**: `tool.contracts.search`, `tool.contracts.get_status`, `tool.contracts.generate` (gated, see Feature 6), `tool.contracts.get_signing_status`
  - **4.9 Back-office**: `tool.invoices.search`, `tool.invoices.get_ledger`, `tool.assets.search`
  - **4.10 Security**: `tool.users.get_my_permissions`, `tool.security.get_my_recent_events` (self-service only — never another user's)
  - **4.11 Integrations**: `tool.integrations.get_status`, `tool.webhooks.get_delivery_log`
  - **4.12 Collaboration**: `tool.notifications.get_unread`, `tool.calendar.get_upcoming`, `tool.comments.post` (gated, see Feature 6)
  - **4.15 Service & Support**: `tool.services.search`, `tool.services.get`, `tool.service_requests.search`, `tool.service_requests.create`, `tool.service_requests.get_status`, `tool.service_requests.update_status`, `tool.service_requests.add_document_request`, `tool.cases.search`, `tool.cases.create`, `tool.cases.get`, `tool.cases.update_status`, `tool.cases.add_note`, `tool.cases.request_signoff`
- Every tool's input schema and output schema is versioned alongside the API versioning scheme from section 4.11 Feature 1 — a `tool.deals.move_stage` change that alters its parameters bumps a tool version, and the assistant service is updated in lockstep, preventing silent drift between what the assistant "thinks" a tool does and what it actually does.
- Read-only tools (search, get_*, preview_*) require no special confirmation flow beyond standard RBAC — if the user can view the data via the UI, the assistant can retrieve and present it.
- Tools are rate-limited using the same per-token infrastructure as section 4.10 Feature 9 — the assistant's internal token for a given user session has its own rate limit tier, preventing a single conversation from generating excessive tool-call load (e.g. a poorly-scoped "show me everything about every contact" request).
- All tool calls are logged to the audit trail (section 4.10, Spatie Activitylog) with an additional `actor_type: assistant` flag and the originating conversation session ID, distinguishing assistant-initiated actions from direct UI actions for audit purposes — this is additive to, not a replacement for, the standard audit logging each underlying action already performs.

---

## Feature 6: Action execution with confirmation gating

**Story:** As a user, I want the assistant to be able to actually do things for me — but I don't want it making changes to records, sending communications, or triggering workflows without my explicit confirmation.

Acceptance criteria:
- Tools are classified into three tiers: **read** (no confirmation needed — search, view, preview), **write-reversible** (requires inline confirmation — e.g. moving a deal stage, creating an activity, updating a ticket status, all of which have clear undo paths via the existing UI), and **write-significant** (requires explicit confirmation with a summary of consequences — e.g. generating a contract section 4.8 Feature 2, sending a campaign section 4.4 Feature 5, posting a comment with @mentions section 4.12 Feature 2, creating an invoice section 4.9 Feature 1).
- For write-reversible actions, the assistant presents the intended action as a confirmable card within the chat ("Move Deal #4521 to Proposal stage?") with Confirm/Cancel — consistent with the `ask_user_input_v0` pattern of presenting choices rather than silently acting, but action-specific rather than purely elicitative.
- For write-significant actions, the confirmation card includes the downstream consequences drawn from the relevant module's automation specs — e.g. confirming a deal stage move that has configured stage automations (section 4.2 Feature 3) shows "this will also: create a follow-up task for [agent], send a notification to [team]" before the user confirms, so they're not surprised by cascading effects.
- No tool call in the **write-significant** tier executes without the user's explicit affirmative response in the same conversation turn or the immediately following one. The assistant never chains multiple write-significant actions on a single confirmation ("yes" to "send this campaign" does not also confirm a subsequent, different significant action proposed later).
- Destructive or irreversible actions (anything matching the "privileged action" list from section 4.10 Feature 4 — purging records, disconnecting integrations, terminating contracts) are **out of scope for assistant execution entirely**. The assistant can navigate the user to the relevant screen and explain the privileged-session requirement (section 4.10 Feature 4), but cannot perform these actions itself even with confirmation. This is a hard exclusion, not a confirmation tier.
- Every executed action (write-reversible or write-significant) results in the assistant explicitly stating what was done, including a link to view the affected record, so the user has an immediate audit trail within the conversation itself in addition to the system audit log (Feature 5).
- If a tool call fails (validation error, permission error, downstream system error), the assistant surfaces the actual error reason in plain language (not a generic "something went wrong") and, where the error is a permissions issue, follows the same "who can do this" pattern as Feature 4.

---

## Feature 7: Role and audience-scoped behaviour

**Story:** As a platform operator, I need the assistant's capabilities and tone to adapt to who's asking — an agent, a manager, an admin, or a customer in the self-service portal — so that each gets relevant help without overstepping their access.

Acceptance criteria:
- The assistant's available tool set (Feature 5) is filtered server-side based on the requesting user's RBAC permissions at the start of each session — an agent's session simply does not have `tool.contracts.generate` available if they lack `contracts.create` (section 4.8 Feature 2), regardless of how the request is phrased. This is enforced at the agent tool API layer (Feature 1), not by instructing the LLM to "refuse" — removing the capability removes the risk of prompt-based bypass.
- For agents, the assistant's framing emphasizes task completion within their own scope — "your deals," "your tickets," "your team" where team-scoping applies (consistent with the dashboard scoping in section 4.7 Feature 1).
- For managers, the assistant additionally surfaces team-level navigation and read-only team analytics (agent performance section 4.6 Feature 7, team pipeline section 4.2 Feature 4) and can answer "how is my team doing on X" style questions by calling the relevant `tool.dashboards.get_summary` or `tool.analytics.get_metric` tools scoped to the manager's team.
- For admins, the assistant's scope additionally includes configuration navigation and pre-fill (Feature 4's admin examples) and read-only access to system health tools (`tool.integrations.get_status`, `tool.webhooks.get_delivery_log`) — but write-significant configuration changes (creating roles, connecting integrations) still require navigation-to-screen rather than direct execution, as these are typically multi-step guided flows (section 4.11 Feature 6) better served by the existing UI than by chat-driven form-filling.
- A **separate, more constrained assistant instance** is available within the self-service portal (section 4.5 Feature 7) for contacts. This instance's tool set is limited to: `tool.tickets.search` (own tickets only), `tool.tickets.create`, `tool.service_requests.search` (own requests only), `tool.service_requests.create`, `tool.service_requests.get_status` (own requests only), `tool.service_requests.add_document_request` (own requests only), `tool.kb.search`, `tool.loyalty.get_balance` (own balance only), `tool.contracts.search` (own contracts only, read-only), and navigation within the portal itself — it has zero access to internal/agent-facing tools, internal comments (section 4.12 Feature 1), internal cases/case notes, SLA breach details, or any cross-contact data. This is enforced by the portal instance using a fundamentally different, narrower tool registration, not by filtering the full registry at runtime.
- The customer-facing instance's system prompt explicitly excludes any internal CRM terminology, module names, or references to agent-side screens — its documentation grounding (Feature 3) draws only from the customer-audience subset of articles (`audience: all` or a dedicated `audience: customer` tag from section 4.13 Feature 1), mirroring the audience filtering already specified for the documentation centre.

---

## Feature 8: Proactive suggestions

**Story:** As a user, I want the assistant to occasionally surface relevant help based on what's happening in the CRM around me, not just respond when I ask.

Acceptance criteria:
- Proactive suggestions are limited to a small, explicitly defined set of triggers tied to existing notification-worthy events from section 4.12 Feature 7 (the notification centre) — the assistant does not independently decide what's "worth" surfacing; it responds to the same event taxonomy already driving notifications.
- When a user opens the chat popup (Feature 2) and has unread notifications of certain types (SLA breach warnings, mention notifications, contract renewal reminders — per section 4.12 Feature 7's list), the assistant's opening message can reference the most urgent one with a direct action — "You have a ticket approaching its SLA breach in 20 minutes — want me to open it or reassign it?" — rather than a generic greeting.
- This proactive opening is suppressed if the user has interacted with the relevant notification already (read status from section 4.12 Feature 7) — the assistant does not re-surface things the user has already acknowledged through the normal notification centre.
- Proactive suggestions never use a separate notification channel (no push notifications or emails originate from the assistant itself) — they only appear within the chat popup at the moment the user opens it, keeping the assistant's proactivity scoped to "when you're already here, here's what's relevant" rather than an additional outbound communication vector that would need its own opt-out (avoiding overlap with the notification preference panels established throughout 4.4, 4.5, 4.8, and 4.12).
- A user can disable proactive openings entirely from their notification preferences panel (section 4.12 Feature 7) — the assistant then always opens with a neutral greeting and waits for the user's first message.
- Proactive suggestions respect the same RBAC/audience scoping as Feature 7 — the self-service portal instance's proactive triggers are limited to the contact's own ticket and contract notifications, never internal events.

---

## Feature 9: Quality, evaluation, and grounding maintenance

**Story:** As a documentation maintainer and platform operator, I need confidence that the assistant's answers stay accurate as the platform evolves, and a way to measure whether it's actually helping.

Acceptance criteria:
- The assistant's retrieval grounding (Feature 3) is automatically re-indexed whenever documentation articles are published or updated (section 4.13 Feature 1's lifecycle) — there is no separate "assistant knowledge base" to maintain manually; it queries the same Meilisearch index as the documentation centre and contextual help panel in real time.
- Each assistant conversation can end with the same "was this helpful" prompt used for documentation articles (section 4.6 Feature 4, section 4.13 Feature 5) — a lightweight thumbs up/down on the overall conversation, optionally with a comment. Negative feedback is logged with the conversation transcript (minus any sensitive data per section 4.10's classification rules — transcripts referencing classified fields are themselves subject to the same masking on review) for maintainer review.
- The documentation maintainer dashboard (section 4.13 Feature 5) gains an additional view: assistant-specific metrics — most common intents/routes requested, routes where the assistant's confidence was flagged as low (Feature 3), and the rate of write-significant actions confirmed vs. cancelled (Feature 6), the latter being a signal of whether the assistant is proposing the right actions.
- Tool-call failures (Feature 6) are aggregated and surfaced to engineering separately from the documentation maintainer view — a tool failing repeatedly indicates an API contract issue (Feature 5's versioning) rather than a documentation gap, and should route to the appropriate team.
- A staging/canary mechanism is required before any change to the assistant's system prompt, tool registry, or underlying model is rolled out platform-wide — given the assistant has write-execution capability (Feature 6), prompt changes carry more risk than a typical chatbot update and warrant the same change-control rigor as a backend deployment, not a content edit.
- The assistant's own usage (sessions, tool calls, action confirmations) is itself tracked through the existing audit and analytics infrastructure (section 4.10 Feature 8, section 4.7) rather than a bespoke analytics system — `actor_type: assistant` (Feature 5) is the consistent tag enabling this across all existing reporting surfaces.

---

## Architecture note

This module is intentionally specified as **additive infrastructure with a constrained interface**, not a generalized "AI does anything" layer. The three things that keep it safe and maintainable as the platform grows: (1) the agent tool API is an explicit allowlist that mirrors the existing feature specs feature-by-feature, so adding a new CRM feature in 4.1–4.12 means deliberately deciding whether and how the assistant gets access to it, rather than it inheriting access automatically; (2) RBAC enforcement happens at the tool API layer using the user's real permissions, so the assistant can never do more than the user already could through the UI; (3) the documentation corpus from 4.13 is the single source of truth for "explain" responses, so improving the assistant's accuracy and improving the documentation are the same maintenance activity, not two parallel ones.