## What you're describing

In Dynamics 365 this is the **Service Management** or **Customer Service Hub** module — it covers three distinct but related workflows:

**Service Catalog** — defining what services your organisation actually offers (e.g. "Account Opening", "Loan Application", "Device Repair", "Technical Support"). Each service has its own intake form, SLA, assigned team, and workflow.

**Service Requests** — a customer or agent initiates a request for a specific service from the catalog. This is distinct from a support ticket because it's *proactive* (customer wants something) rather than *reactive* (customer has a problem). Think "I want to open a savings account" vs "my account is broken."

**Cases** — the full lifecycle record that wraps around a service request or complaint, from initiation through investigation, resolution, and formal closure. A case has owners, escalation paths, linked documents, communications history, audit trail, and a closure report. This is closer to what legal or regulated industries call a "matter" — it has a formal open/close lifecycle with sign-off.

These three things together form a **Service Management** module, and you're right that it's currently fragmented across the specs rather than unified.

---

## Where it currently lives in the specs (scattered)

Right now the specs touch on this in three separate places without connecting them:

Section 4.6 (Support) covers tickets, SLA, and knowledge base — this handles the reactive/complaint side well but has no service catalog or formal case lifecycle.

Section 4.5 Feature 7 (Self-service portal) mentions customers submitting requests — but there's no backend service catalog powering what they're requesting.

Section 4.8 (Contracts & Legal) has the legal matter log, which is essentially a case management primitive but scoped only to legal — not generalized.

None of these give you the full "define a service → receive a request → work a case → close formally" flow.

---

## What needs to be added: Section 4.6b — Service Management

This should be a dedicated sub-module within the Support section of the nav, since Support is the natural home for service delivery alongside tickets and SLA. Here's how I'd define the three layers:

**Service Catalog**
The admin defines every service the organisation offers. Each service has a name, category, description, intake form (same custom form builder from 4.6 Feature 3), default SLA, owning team, required documents, and whether it's available via the self-service portal. Think of this as the master list of "things we do for customers."

**Service Requests**
When a customer or agent initiates a service, a service request record is created, linked to the contact and account, referencing the specific catalog item. The request captures the intake form responses, any uploaded documents, the requesting channel (portal, agent-created, email, kiosk), and a status: Submitted → Under Review → In Progress → Pending Customer → Completed → Closed. Each status transition can have configured automations (notify agent, create task, request document) exactly like the deal stage automations in 4.2 Feature 3 — because the underlying mechanism is the same event/listener pattern.

**Cases**
A case is the formal lifecycle wrapper — it can originate from a service request, a ticket, a complaint, or be opened directly. Where a ticket is transactional (resolve the immediate problem), a case is longitudinal (manage a situation from start to formal closure). A case has: a case number, a type (service delivery, complaint, compliance, dispute), a formal case owner, linked contacts and accounts, a communications thread, linked documents (stored on R2 per the patterns in 4.8 and 4.9), investigation notes, resolution details, a root cause field, and a formal closure report requiring a manager sign-off. Cases can link to tickets, service requests, contracts, and legal matters — they're the highest-level record type in the support domain.

---

## Where it sits in the UI nav

Given everything above, here's how I'd restructure the Support section in your sidebar:

```
Support
├── Inbox / Tickets          ← existing 4.6
├── Service Requests         ← new
├── Cases                    ← new
├── Service Catalog          ← new (admin-only sub-section)
├── Knowledge Base           ← existing 4.6
└── SLA Management           ← existing 4.6 (admin-only)
```

And the top-level nav item should be renamed from "Support" to **"Service & Support"** to signal that it covers both reactive support (tickets) and proactive service delivery (requests and cases), which is exactly what Dynamics 365's Customer Service Hub communicates with its naming.

---

## How the three relate in practice

A typical flow through all three might look like this: a customer logs into the self-service portal and submits a **service request** for "Business Loan Application" from the service catalog. This creates a request record and notifies the assigned relationship manager. The manager reviews the request, opens a **case** ("Loan Application — Acme Ltd — June 2026") which becomes the formal tracking record for the entire process. As the case progresses, the manager logs investigation notes, requests documents (stored on R2), links the associated contract when it's generated (4.8), and coordinates with the credit team via internal case comments (4.12 Feature 1). When the loan is approved and documents signed, the manager submits a closure report and a second manager countersigns, formally closing the case. The customer receives a closure notification. The entire thread — from portal submission to case closure — is in one record with a full audit trail.

---

## Impact on the rest of the specs

Adding this properly requires:

A `services` catalog table, a `service_requests` table with a polymorphic link to the intake form data, and a `cases` table with its own status lifecycle, owner, type, and closure fields. Cases get a polymorphic `caseable` relationship so they can link to tickets, service requests, contracts, and legal matters without each being a separate foreign key.

The self-service portal (4.5 Feature 7) gains a "Request a Service" section powered by the catalog. The notification system (4.12 Feature 7) gains service request and case status events. The analytics module (4.7) gains a case analytics view showing open cases by type, average closure time, and SLA performance per service type. The AI assistant (4.14) gains tools for `tool.cases.search`, `tool.cases.create`, and `tool.service_requests.get_status`.

This is genuinely the most significant gap in the current spec relative to what a full enterprise CRM should offer — and your instinct to flag it from Dynamics 365 is exactly right. Dynamics, Salesforce Service Cloud, and Zoho CRM all treat service management as a first-class module on par with sales pipeline, and for good reason — especially for your target market of SACCOs and financial institutions where service delivery tracking is a regulatory and operational requirement, not just a nice-to-have.