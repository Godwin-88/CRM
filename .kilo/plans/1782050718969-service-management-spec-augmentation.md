# Service Management Spec Augmentation Plan

Target reference: `docs/serviceman.md`

## Goal

Augment the current Service Management notes into an implementation-ready spec section for a first-class **Service & Support** module. The spec should unify service catalog, service requests, cases, tickets, SLA, self-service portal, collaboration, analytics, API/events, permissions, and assistant tools without duplicating existing ticket, legal matter, collaboration, or notification primitives.

## Current gaps found

- `docs/serviceman.md` is a useful conceptual draft but lacks concrete acceptance criteria, data model, permissions, routing rules, event/API contracts, and validation steps.
- Existing support spec `docs/4.6.md` covers tickets, SLA, categories, forms, KB, CSAT, agent performance, canned responses, and email-to-ticket, but does not define service catalog or service requests.
- Existing self-service portal spec `docs/4.5.md:103` supports tickets but not catalog-powered service requests.
- Existing legal matter spec `docs/4.8.md:201` is a case-management primitive but remains legal-only.
- Existing collaboration spec `docs/4.12.md:5` already defines polymorphic comments, mentions, attachments, notifications, and calendar integration.
- Existing AI assistant spec `docs/agent.md:66` defines tool registry patterns but does not include service requests or cases.
- Existing support migrations create ticket/category/form/SLA structures but no service catalog, service request, or case tables.

## Resolved design decisions

1. **Service Management is a first-class module** under a renamed **Service & Support** nav area, not a ticket-only extension.
2. **Legal Matters remain separate but linked to Cases.** Do not generalize legal matters into cases in this pass. Define explicit links from cases to legal matters and keep legal-specific permissions/visibility intact.
3. **Service requests use request-first routing.** A submitted service request always creates a `service_request` record first. A case is created only when an agent or automation explicitly opens one. Tickets are created only when a discrete support issue is detected.
4. **Intake forms should be generic reusable forms.** Introduce a generic form schema/response model that can serve service requests and later replace or coexist with ticket-only form tables. Do not reuse `TicketForm`/`TicketFormResponse` as the long-term service-request model.
5. **SLA is shared.** Extend the existing SLA engine so SLA instances can attach to tickets, service requests, and cases with entity-specific milestone semantics.
6. **Cases are formal longitudinal records.** Cases can originate from service requests, tickets, complaints, legal matters, contracts, or manual creation. They track ownership, linked records, communications, documents, investigation notes, root cause, resolution, closure report, and manager sign-off.
7. **Service Catalog is admin-configured.** Catalog items define the service name, category, description, active status, channel visibility, default team/owner, SLA policy, intake form schema, required documents, automation rules, and customer-facing instructions.
8. **Service Requests are operational records.** They capture catalog item, requester, contact, account, channel, intake responses, documents, status, assigned team/agent, SLA instance, and links to any generated ticket/case.
9. **Cases are formal lifecycle records.** They capture case number, type, owner, contacts, accounts, caseable links, status, priority, SLA instance, communications, documents, internal comments, investigation notes, resolution details, root cause, closure report, closure approver/sign-off, and audit trail.
10. **Portal/API/assistant surfaces are additive.** The self-service portal gains service catalog browsing and request submission. The API/webhook/event system gains service request and case events. The assistant gains read/search/create/update tools gated by RBAC.

## Required spec content

Add or revise the Service Management section to include the following numbered features and acceptance criteria.

### Feature 1: Service Catalog

- Admins can create catalog items with name, slug, description, category, active/inactive status, customer-facing instructions, default priority, default team, default owner role, SLA policy, intake form schema, required document policy, portal visibility, email/kiosk/API visibility, and automation configuration.
- Catalog items support versioning. Publishing a new version affects new requests only; historical requests retain the catalog version used at submission.
- Deactivated catalog items remain visible on historical service requests and cases.
- Catalog item configuration is restricted to admin/manager roles based on `services.manage`.
- Catalog items can be grouped by service category and filtered by active status, portal visibility, owning team, and SLA policy.

### Feature 2: Generic intake form schemas and responses

- Introduce reusable `form_schemas` and `form_responses` tables, or equivalent models, with `formable_type` and `formable_id`.
- Supported field types include text, textarea, number, decimal, date, datetime, dropdown, multiselect, checkbox, file upload, and conditional sections.
- Required fields are validated before service request submission.
- Form responses are stored as structured JSON with field metadata snapshots so historical responses render correctly after schema changes.
- File upload fields use the existing R2-backed polymorphic attachment pattern from `docs/4.12.md:53`.
- Ticket forms can either migrate to this generic model or be explicitly bridged to it; the spec must state which approach implementation chooses.

### Feature 3: Service Requests

- Customers can submit service requests from the self-service portal using only catalog items visible to their account/contact.
- Agents can create service requests from contact, account, case, or service request list views.
- Inbound email, kiosk, IVR, and API channels can create service requests when configured by catalog item.
- Required fields: catalog item, requester, contact, account where applicable, channel, intake responses, source identifier, and status.
- Default lifecycle: `submitted` → `under_review` → `in_progress` → `pending_customer` → `completed` → `closed`.
- Status transitions trigger notifications, tasks, SLA milestone updates, and optional case/ticket creation according to catalog item automation rules.
- A service request can be reassigned, escalated, paused, cancelled, reopened, and closed with a reason.
- Portal users can view and update their own service requests, upload requested documents, reply to pending-customer requests, and see status history.
- Agents can merge duplicate service requests or link related requests without losing audit history.

### Feature 4: Cases

- Cases can originate from service requests, tickets, complaints, legal matters, contracts, or manual creation.
- Case types include service delivery, complaint, compliance, dispute, investigation, escalation, and custom.
- Case status lifecycle: `new` → `triaged` → `in_progress` → `pending_customer` → `pending_internal` → `resolution_proposed` → `closed` → `reopened`.
- Cases have a unique case number, title, type, priority, owner, linked contacts/accounts, caseable polymorphic links, linked tickets, linked service requests, linked contracts, linked legal matters, SLA instance, communications thread, documents, internal comments, investigation notes, root cause, resolution details, closure report, closure approver, and closure timestamp.
- Cases support manager sign-off before closure when required by case type, service catalog item, or SLA policy.
- Cases can be escalated with mandatory reason, priority increase, and manager notification.
- Closed cases can be reopened within a configurable window with audit logging.
- Legal matters remain separate but link to cases; legal-specific permissions and customer-facing exclusion remain unchanged.

### Feature 5: Ticket, request, and case relationship rules

- A ticket is a reactive support issue and remains the primary record for support incidents.
- A service request is a proactive request for a catalog-defined service.
- A case is a formal longitudinal wrapper for complex service delivery, complaints, compliance, disputes, or investigations.
- A service request does not automatically create a ticket or case unless catalog automation explicitly requires it.
- A ticket can be linked to a case when it becomes part of a broader investigation or formal service process.
- A case can have many linked tickets and service requests.
- Related records must be visible on each record’s detail view with navigation links and status summaries.
- Merging or closing a parent service request must not silently close linked tickets or cases unless the transition rules explicitly allow it.

### Feature 6: SLA integration

- Extend SLA definitions/instances to support target entity types: ticket, service request, case.
- Milestone semantics differ by entity type:
  - Ticket: first response and resolution.
  - Service request: acknowledgement, review, next action, and completion.
  - Case: triage, investigation update, resolution proposal, and closure sign-off.
- Business hours, holidays, pause/resume rules, breach warnings, breach flags, and manager alerts reuse existing SLA engine behavior.
- SLA policy selection priority: catalog item override, then contact loyalty tier/account type override, then global/default policy.
- SLA timers pause for `pending_customer` and other configured waiting statuses.
- Breached service requests and cases appear in manager/admin breach lists and support analytics.

### Feature 7: Permissions and access control

- Seed and document module permissions using `{module}.{action}` naming.
- Minimum permissions:
  - `services.view`, `services.create`, `services.update`, `services.delete`, `services.manage`
  - `service_requests.view`, `service_requests.create`, `service_requests.update`, `service_requests.close`, `service_requests.reopen`, `service_requests.export`
  - `cases.view`, `cases.create`, `cases.update`, `cases.close`, `cases.reopen`, `cases.signoff`, `cases.export`
  - `case_comments.create`, `case_documents.upload`, `case_documents.download`
- Default assignments:
  - Admin: all service and case permissions.
  - Manager: view/create/update/close/reopen/export for requests and cases; manage catalog only if explicitly granted.
  - Agent: view assigned requests/cases, create requests, update assigned requests/cases, close only when permitted.
  - Read-only: view records scoped by existing team/account/contact permissions.
  - API Client: scoped by integration permissions.
- Access is scoped by team, assigned owner, linked account/contact, and sensitivity classification from `docs/4.10.md:71`.
- Customer portal access is strictly limited to the contact’s own requests, linked documents, and status history.

### Feature 8: UI navigation

- Rename top-level nav from **Support** to **Service & Support**.
- Suggested sidebar:
  - Inbox / Tickets
  - Service Requests
  - Cases
  - Service Catalog
  - Knowledge Base
  - SLA Management
- Service Catalog is admin/manager configuration area.
- Cases and Service Requests have list, detail, create, filter, export, and bulk update views.
- Detail views include timeline, linked records, documents, SLA panel, comments/mentions, attachments, audit history, and related tickets/service requests/cases.

### Feature 9: Self-service portal updates

- Add “Request a Service” section powered by active, portal-visible catalog items.
- Portal form rendering uses the generic intake form schema.
- Portal users can upload required documents using the existing R2 attachment flow.
- Portal users can view status, request history, agent/customer messages, requested documents, and closure notifications for their own requests.
- Portal users cannot see internal comments, case investigation notes, SLA breach details, or linked tickets/cases unless explicitly exposed by policy.
- Portal actions are audited with source `self_service_portal`.

### Feature 10: Notifications, events, and webhooks

- Notification centre includes service request assigned, status changed, pending customer, document requested, completed, closed, SLA warning, SLA breached, case assigned, case escalated, case pending sign-off, and case closed events.
- Webhook event names should use the existing event taxonomy pattern:
  - `service_request.created`
  - `service_request.status_changed`
  - `service_request.completed`
  - `service_request.closed`
  - `case.created`
  - `case.status_changed`
  - `case.escalated`
  - `case.closed`
  - `case.signoff_required`
- Payloads use API resource shapes and include record IDs, status, owner, linked contact/account, SLA state, and timestamps.
- Inbound email-to-ticket processing should optionally create or update a service request when the matched catalog/service routing rules require it.

### Feature 11: Analytics and reporting

- Add support/service analytics views for:
  - Open service requests by status, catalog item, team, owner, and channel.
  - Open cases by type, priority, status, owner, and linked account/contact.
  - Average acknowledgement, completion, and closure time.
  - SLA breach rate for tickets, service requests, and cases.
  - Catalog item demand and conversion from request to case.
  - Case root cause distribution and closure sign-off times.
- Report builder and exploratory analysis entity lists must include service requests and cases.
- Dashboard widgets should include assigned open service requests, assigned open cases, service SLA breaches, and pending case sign-offs.

### Feature 12: AI assistant tools

Add to the tool registry in `docs/agent.md:66` using the existing RBAC-gated pattern:
- `tool.services.search`
- `tool.services.get`
- `tool.service_requests.search`
- `tool.service_requests.create`
- `tool.service_requests.get_status`
- `tool.service_requests.update_status`
- `tool.service_requests.add_document_request`
- `tool.cases.search`
- `tool.cases.create`
- `tool.cases.get`
- `tool.cases.update_status`
- `tool.cases.add_note`
- `tool.cases.request_signoff`
- Customer portal assistant remains restricted to own service requests and cannot access internal cases or case notes.

### Feature 13: Data model and migrations

Define concrete tables and relationships:
- `service_catalog_items`
- `service_catalog_item_versions`
- `service_requests`
- `case_records` or `cases`
- `case_links` or polymorphic `caseables`
- `form_schemas`
- `form_schema_versions`
- `form_responses`
- `service_request_status_history`
- `case_status_history`
- `case_closure_reports`
- `case_signoffs`
- `service_request_sla_instances` or extend `sla_instances`
- `case_sla_instances` or extend `sla_instances`
- `service_request_notifications` only if not covered by existing notification model.

Required relationships:
- Catalog item has many service requests.
- Service request belongs to contact, account, user/requester, catalog item, assigned user/team, SLA instance, form response, and optional case.
- Case belongs to owner, primary contact, primary account, SLA instance, closure report, and has many case links.
- Case links connect to tickets, service requests, contracts, legal matters, invoices, deals, or other approved models.
- Form schema is polymorphic and versioned.
- Form response belongs to schema version and polymorphic owner.

### Feature 14: Validation and edge cases

The implementation agent must cover:
- Customer submits a portal request for a deactivated catalog item: rejected with clear message.
- Customer submits a portal request missing required fields: field-level validation errors.
- Customer uploads disallowed file type or oversized file: rejected and audited.
- Agent creates request for a customer without account context: allowed only if catalog item permits it.
- Service request moves to `pending_customer`: SLA timer pauses and customer notification is sent.
- Service request is completed without required closure reason: rejected.
- Case requires manager sign-off: cannot close until approved.
- Legal matter remains inaccessible in portal and respects `legal.view` / `legal.manage`.
- Case links to ticket, contract, and legal matter are visible only to users with access to each linked record.
- API/webhook payloads include enough IDs for idempotent consumers but do not expose restricted fields.
- Assistant tools cannot expose internal case notes to portal users.

## Implementation boundaries

- Do not change existing ticket behaviour unless required to link tickets to service requests/cases.
- Do not merge legal matters into cases in this implementation pass.
- Do not create a second SLA engine.
- Do not duplicate polymorphic comments, mentions, attachments, notifications, or audit logging; reuse existing shared primitives.
- Do not implement UI screens in this planning pass; this plan only specifies what the docs/spec should require.

## Validation plan for implementation agent

1. Add spec sections and acceptance criteria matching this plan.
2. Add or update migrations/models for service catalog, service requests, cases, generic forms, case links, status history, closure reports, and sign-offs.
3. Reuse existing R2, comments, mentions, attachments, notifications, audit logging, SLA, webhooks, report builder, and assistant tool patterns.
4. Add policies/gates for all new permissions and portal scoping.
5. Add tests for routing, lifecycle transitions, SLA pause/resume, sign-off, permissions, portal isolation, webhook payloads, analytics filters, and assistant tool gating.
6. Update documentation seed category/article references only if required by the repo’s documentation indexing approach.

## Open questions for implementation phase

- Whether existing `ticket_forms`/`ticket_form_responses` should be migrated into the new generic form model or bridged through a compatibility layer.
- Exact UI route names for Service Requests, Cases, Service Catalog, and Case detail views.
- Whether service request completion should automatically notify the customer or wait for case closure when both records exist.
