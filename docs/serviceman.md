# Service & Support Specification

## Overview

Service & Support is a first-class CRM module for proactive service delivery, reactive support, and formal case management. It combines:

- **Service Catalog**: admin-configured offerings with intake forms, SLA policies, default teams, visibility, and automation configuration.
- **Service Requests**: operational records created when a customer, agent, email, kiosk, IVR, or API submits a request for a catalog-defined service.
- **Cases**: formal longitudinal records for complex service delivery, complaints, compliance, disputes, investigations, escalations, and closure sign-off.
- **Tickets**: existing reactive support records for discrete support issues.

The module intentionally reuses existing collaboration, attachment, audit, SLA, permissions, notifications, and assistant-tool patterns rather than duplicating primitives.

---

## Relationship to Existing Modules

### Tickets

Tickets remain the primary record for reactive support incidents. A ticket can be linked to a case when it becomes part of a broader investigation or formal service process.

### Legal Matters

Legal matters remain separate. Cases may link to legal matters, but legal-specific permissions and visibility are not inherited by cases. Portal users must not see linked legal matters.

### Collaboration

Use existing polymorphic `comments`, mentions, Spatie Media Library attachments, notifications, activity logging, and audit trails for service requests and cases. Do not create service-specific comment or note tables.

### SLA

The existing SLA engine is extended to support tickets, service requests, and cases. No second SLA engine should be introduced.

---

## Feature 1: Service Catalog

### Story

As an admin or manager, I can define the services the organisation offers so agents and customers know what can be requested, what information is required, and which team handles the work.

### Acceptance Criteria

- Admins can create catalog items with:
  - name
  - slug
  - description
  - category
  - customer-facing instructions
  - default priority
  - default team
  - default owner role
  - SLA policy
  - intake form schema
  - required document policy
  - portal visibility
  - email/kiosk/API visibility
  - active/inactive status
  - automation configuration
- Catalog items support versioning. Publishing a new version affects new requests only; historical requests retain the catalog version used at submission.
- Deactivated catalog items remain visible on historical service requests and cases.
- Catalog item configuration is restricted to users with `services.manage`.
- Catalog items can be grouped by service category and filtered by active status, portal visibility, owning team, and SLA policy.
- Portal users can see only active, portal-visible catalog items relevant to their contact/account.

### Current Data Model

- `service_catalog_items`
- `service_catalog_item_versions`

---

## Feature 2: Generic Intake Form Schemas and Responses

### Story

As a CRM engineer, I need reusable form schemas so service requests can capture structured intake data without tying the schema to tickets.

### Acceptance Criteria

- Intake forms use generic `form_schemas`, `form_schema_versions`, and `form_responses`.
- `form_responses` are polymorphic through `formable_type` and `formable_id`.
- Supported field types include:
  - text
  - textarea
  - number
  - decimal
  - date
  - datetime
  - dropdown
  - multiselect
  - checkbox
  - file upload
  - conditional sections
- Required fields are validated before service request submission.
- Form responses are stored as structured JSON with field metadata snapshots so historical responses render correctly after schema changes.
- File upload fields use the existing R2/S3-backed polymorphic attachment pattern.
- Ticket forms remain ticket-specific for now. Service requests use the new generic form model.

### Current Data Model

- `form_schemas`
- `form_schema_versions`
- `form_responses`

---

## Feature 3: Service Requests

### Story

As a customer or agent, I can request a defined service and track it through acknowledgement, review, work, pending customer action, completion, and closure.

### Acceptance Criteria

- Customers can submit service requests from the self-service portal using only active, portal-visible catalog items.
- Agents can create service requests from contact, account, case, or service request list views.
- Inbound email, kiosk, IVR, and API channels can create service requests when the matched catalog item permits that channel.
- Required fields:
  - catalog item
  - requester
  - contact
  - account where applicable
  - channel
  - source identifier
  - status
- Default lifecycle:
  - `submitted`
  - `under_review`
  - `in_progress`
  - `pending_customer`
  - `completed`
  - `closed`
- Status transitions trigger notifications, tasks, SLA milestone updates, and optional case/ticket creation according to catalog automation rules.
- A service request can be reassigned, escalated, paused, cancelled, reopened, and closed with a reason.
- Portal users can view and update their own service requests, upload requested documents, reply to pending-customer requests, and see status history.
- Agents can merge duplicate service requests or link related requests without losing audit history.
- A service request does not automatically create a ticket or case unless catalog automation explicitly requires it.

### Current Data Model

- `service_requests`
- `service_request_status_history`
- `service_request_links`
- `service_request_document_requests`

### Current API Surface

- `GET /api/v1/service-requests`
- `POST /api/v1/service-requests`
- `GET /api/v1/service-requests/{serviceRequest}`
- `POST /api/v1/service-requests/{serviceRequest}/status`
- `POST /api/v1/service-requests/{serviceRequest}/document-requests`
- `POST /api/v1/service-requests/{serviceRequest}/merge`

---

## Feature 4: Cases

### Story

As an agent or manager, I can open a formal case to manage complex service delivery, complaints, compliance, disputes, investigations, and escalations through closure and sign-off.

### Acceptance Criteria

- Cases can originate from service requests, tickets, complaints, legal matters, contracts, or manual creation.
- Case types include:
  - `service_delivery`
  - `complaint`
  - `compliance`
  - `dispute`
  - `investigation`
  - `escalation`
  - `custom`
- Case status lifecycle:
  - `new`
  - `triaged`
  - `in_progress`
  - `pending_customer`
  - `pending_internal`
  - `resolution_proposed`
  - `closed`
  - `reopened`
- Cases have:
  - unique case number
  - title
  - type
  - priority
  - owner
  - linked contacts/accounts
  - caseable links
  - linked tickets
  - linked service requests
  - linked contracts
  - linked legal matters
  - SLA instance
  - communications thread
  - documents
  - internal comments
  - investigation notes
  - root cause
  - resolution details
  - closure report
  - closure approver/sign-off
  - audit trail
- Cases support manager sign-off before closure when required by case type, service catalog item, or SLA policy.
- Cases can be escalated with mandatory reason, priority increase, and manager notification.
- Closed cases can be reopened within a configurable window with audit logging.
- Legal matters remain separate but linkable to cases; legal-specific permissions and customer-facing exclusion remain unchanged.

### Current Data Model

- `case_records`
- `case_links`
- `case_status_history`
- `case_closure_reports`
- `case_signoffs`

### Current API Surface

- `GET /api/v1/cases`
- `POST /api/v1/cases`
- `GET /api/v1/cases/{caseRecord}`
- `POST /api/v1/cases/{caseRecord}/status`
- `POST /api/v1/cases/{caseRecord}/notes`
- `POST /api/v1/cases/{caseRecord}/signoff`

---

## Feature 5: Ticket, Request, and Case Relationship Rules

### Acceptance Criteria

- A ticket is a reactive support issue and remains the primary record for support incidents.
- A service request is a proactive request for a catalog-defined service.
- A case is a formal longitudinal wrapper for complex service delivery, complaints, compliance, disputes, or investigations.
- A service request does not automatically create a ticket or case unless catalog automation explicitly requires it.
- A ticket can be linked to a case when it becomes part of a broader investigation or formal service process.
- A case can have many linked tickets and service requests.
- Related records must be visible on each record's detail view with navigation links and status summaries.
- Merging or closing a parent service request must not silently close linked tickets or cases unless the transition rules explicitly allow it.

---

## Feature 6: SLA Integration

### Acceptance Criteria

- `sla_instances` support `target_type`, `target_id`, and `entity_type`.
- Milestone semantics differ by entity type:
  - Ticket: first response and resolution.
  - Service request: acknowledgement, review, next action, and completion.
  - Case: triage, investigation update, resolution proposal, and closure sign-off.
- Business hours, holidays, pause/resume rules, breach warnings, breach flags, and manager alerts reuse existing SLA engine behavior.
- SLA policy selection priority:
  1. catalog item override
  2. contact loyalty tier/account type override
  3. global/default policy
- SLA timers pause for `pending_customer` and other configured waiting statuses.
- Breached service requests and cases appear in manager/admin breach lists and support analytics.

### Current Data Model

- `sla_definitions.target_entity_type`
- `sla_definitions.service_catalog_item_id`
- `sla_definitions.milestone_definitions`
- `sla_instances.target_type`
- `sla_instances.target_id`
- `sla_instances.entity_type`
- `sla_instances.milestone_definitions`
- `sla_instances.milestone_states`

---

## Feature 7: Permissions and Access Control

### Acceptance Criteria

- Permissions use `{module}.{action}` naming.
- Minimum permissions:
  - `services.view`
  - `services.create`
  - `services.update`
  - `services.delete`
  - `services.manage`
  - `service_requests.view`
  - `service_requests.create`
  - `service_requests.update`
  - `service_requests.close`
  - `service_requests.reopen`
  - `service_requests.export`
  - `cases.view`
  - `cases.create`
  - `cases.update`
  - `cases.close`
  - `cases.reopen`
  - `cases.signoff`
  - `cases.export`
  - `case_comments.create`
  - `case_documents.upload`
  - `case_documents.download`
- Default assignments:
  - Admin: all service and case permissions.
  - Manager: view/create/update/close/reopen/export for requests and cases; manage catalog only if explicitly granted.
  - Agent: view assigned requests/cases, create requests, update assigned requests/cases, close only when permitted.
  - Read-only: view records scoped by existing team/account/contact permissions.
  - API Client: scoped by integration permissions.
- Access is scoped by team, assigned owner, linked account/contact, and sensitivity classification.
- Customer portal access is strictly limited to the contact's own requests, linked documents, and status history.

### Current Implementation

Policies and permissions are added for service catalog items, service requests, and cases. Role seeding must be run to materialize permissions.

---

## Feature 8: UI Navigation

### Acceptance Criteria

- Rename top-level nav from **Support** to **Service & Support**.
- Suggested sidebar:
  - Inbox / Tickets
  - Service Requests
  - Cases
  - Service Catalog
  - Knowledge Base
  - SLA Management
- Service Catalog is an admin/manager configuration area.
- Cases and Service Requests have list, detail, create, filter, export, and bulk update views.
- Detail views include:
  - timeline
  - linked records
  - documents
  - SLA panel
  - comments/mentions
  - attachments
  - audit history
  - related tickets/service requests/cases

---

## Feature 9: Self-Service Portal Updates

### Acceptance Criteria

- Add “Request a Service” section powered by active, portal-visible catalog items.
- Portal form rendering uses the generic intake form schema.
- Portal users can upload required documents using the existing R2/S3 attachment flow.
- Portal users can view status, request history, agent/customer messages, requested documents, and closure notifications for their own requests.
- Portal users cannot see internal comments, case investigation notes, SLA breach details, or linked tickets/cases unless explicitly exposed by policy.
- Portal actions are audited with source `self_service_portal`.
- Portal assistant remains restricted to own service requests and cannot access internal cases or case notes.

---

## Feature 10: Notifications, Events, and Webhooks

### Acceptance Criteria

Notification centre includes:

- service request assigned
- service request status changed
- service request pending customer
- service request document requested
- service request completed
- service request closed
- service request SLA warning
- service request SLA breached
- case assigned
- case escalated
- case pending sign-off
- case closed

Webhook event names use the existing event taxonomy:

- `service_request.created`
- `service_request.status_changed`
- `service_request.completed`
- `service_request.closed`
- `case.created`
- `case.status_changed`
- `case.escalated`
- `case.closed`
- `case.signoff_required`

Payloads include record IDs, status, owner, linked contact/account, SLA state, and timestamps. Inbound email-to-ticket processing should optionally create or update a service request when matched catalog/service routing rules require it.

---

## Feature 11: Analytics and Reporting

### Acceptance Criteria

Add support/service analytics views for:

- open service requests by status, catalog item, team, owner, and channel
- open cases by type, priority, status, owner, and linked account/contact
- average acknowledgement, completion, and closure time
- SLA breach rate for tickets, service requests, and cases
- catalog item demand and conversion from request to case
- case root cause distribution and closure sign-off times

Report builder and exploratory analysis entity lists must include service requests and cases. Dashboard widgets should include assigned open service requests, assigned open cases, service SLA breaches, and pending case sign-offs.

---

## Feature 12: AI Assistant Tools

### Acceptance Criteria

The agent-facing assistant tool registry includes:

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

Customer portal assistant remains restricted to own service requests and cannot access internal cases or case notes.

### Current Implementation

Backend tool registry entries and assistant routes are registered in `AgentToolController` and `routes/api.php`.

---

## Feature 13: Data Model and Migrations

### Core Tables

- `service_catalog_items`
- `service_catalog_item_versions`
- `service_requests`
- `case_records`
- `case_links`
- `form_schemas`
- `form_schema_versions`
- `form_responses`
- `service_request_status_history`
- `service_request_links`
- `service_request_document_requests`
- `case_status_history`
- `case_closure_reports`
- `case_signoffs`
- extended `sla_definitions`
- extended `sla_instances`

### Required Relationships

- Catalog item has many service requests.
- Service request belongs to contact, account, user/requester, catalog item, assigned user/team, SLA instance, form response, and optional case.
- Case belongs to owner, primary contact, primary account, SLA instance, closure report, and has many case links.
- Case links connect to tickets, service requests, contracts, legal matters, invoices, deals, or other approved models.
- Form schema is polymorphic and versioned.
- Form response belongs to schema version and polymorphic owner.

---

## Feature 14: Validation and Edge Cases

### Acceptance Criteria

- Customer submits a portal request for a deactivated catalog item: rejected with clear message.
- Customer submits a portal request missing required fields: field-level validation errors.
- Customer uploads disallowed file type or oversized file: rejected and audited.
- Agent creates request for a customer without account context: allowed only if catalog item permits it.
- Service request moves to `pending_customer`: SLA timer pauses and customer notification is sent.
- Service request is completed without required closure reason: rejected.
- Case requires manager sign-off: cannot close until approved.
- Legal matter remains inaccessible in portal and respects legal permissions.
- Case links to ticket, contract, and legal matter are visible only to users with access to each linked record.
- API/webhook payloads include enough IDs for idempotent consumers but do not expose restricted fields.
- Assistant tools cannot expose internal case notes to portal users.

---

## Current Implementation Status

- Added service management migration and SLA extension migrations.
- Added models for service catalog items, service requests, cases, generic forms, status history, links, document requests, closure reports, and sign-offs.
- Added policies for service catalog items, service requests, cases, and form schemas, with explicit gate registration.
- Added API controllers for service catalog CRUD, service request lifecycle actions, and case lifecycle/link/sign-off actions.
- Added service/case events, notifications, webhook payloads, and listener wiring.
- Added assistant tool registry entries and API routes.
- Added RBAC permissions to the role seeder.

### Remaining Work

- Add frontend pages for Service Requests, Cases, and Service Catalog.
- Add self-service portal request submission and request history views.
- Add inbound email/kiosk/IVR channel routing to service requests.
- Add SLA breach checking and manager alerts for service requests and cases.
- Add full analytics/report-builder widgets for service requests and cases.
- Add tests for portal isolation, webhook payloads, analytics filters, assistant tool gating, and inbound channel routing.
