"""
Navigation helper: maps CRM screens to URL patterns with pre-fill query params.

Mirrors the Inertia route structure so the assistant can produce deep links
like "overdue tickets for Acme Corp" that land directly on the right screen.

Covers all modules 4.1–4.15 per docs/agent.md spec.
"""

from __future__ import annotations

import logging
from dataclasses import dataclass
from typing import Any

from .state import NavigationTarget

logger = logging.getLogger(__name__)


@dataclass(frozen=True)
class RouteManifest:
    route: str
    label: str
    supports_prefill: bool = True
    prefill_keys: tuple[str, ...] = ()

    def url(self, query: dict[str, Any] | None = None) -> str:
        base = self.route
        if query and self.supports_prefill:
            pairs = [f"{k}={v}" for k, v in query.items() if k in self.prefill_keys or not self.prefill_keys]
            if pairs:
                base = f"{base}?{'&'.join(pairs)}"
        return base


MANIFEST = {
    # -----------------------------------------------------------------------
    # 4.1 Contacts & Accounts
    # -----------------------------------------------------------------------
    "contacts.index": RouteManifest(
        route="/contacts",
        label="Contacts",
        supports_prefill=True,
        prefill_keys=("search", "type", "status", "owner_id", "account_id"),
    ),
    "contacts.show": RouteManifest(
        route="/contacts/{id}",
        label="Contact Detail",
        supports_prefill=False,
    ),
    "contacts.create": RouteManifest(
        route="/contacts/create",
        label="Create Contact",
        supports_prefill=True,
        prefill_keys=("account_id", "type"),
    ),
    "accounts.index": RouteManifest(
        route="/accounts",
        label="Accounts",
        supports_prefill=True,
        prefill_keys=("search", "industry", "type"),
    ),
    "accounts.show": RouteManifest(
        route="/accounts/{id}",
        label="Account Detail",
        supports_prefill=False,
    ),
    "accounts.create": RouteManifest(
        route="/accounts/create",
        label="Create Account",
        supports_prefill=True,
        prefill_keys=("industry", "type"),
    ),
    "admin.duplicates": RouteManifest(
        route="/admin/duplicates",
        label="Duplicate Management",
        supports_prefill=False,
    ),
    "admin.custom_fields": RouteManifest(
        route="/admin/custom-fields",
        label="Custom Fields",
        supports_prefill=False,
    ),
    "admin.scoring_rules": RouteManifest(
        route="/admin/scoring-rules",
        label="Scoring Rules",
        supports_prefill=False,
    ),
    "admin.bulk_import": RouteManifest(
        route="/admin/import",
        label="Bulk Import",
        supports_prefill=False,
    ),
    "admin.bulk_export": RouteManifest(
        route="/admin/export",
        label="Bulk Export",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.2 Deals & Pipelines
    # -----------------------------------------------------------------------
    "deals.index": RouteManifest(
        route="/deals",
        label="Deals",
        supports_prefill=True,
        prefill_keys=("search", "stage", "pipeline_id", "owner_id", "value_min", "value_max"),
    ),
    "deals.board": RouteManifest(
        route="/deals/board",
        label="Deal Kanban Board",
        supports_prefill=True,
        prefill_keys=("pipeline_id",),
    ),
    "deals.show": RouteManifest(
        route="/deals/{id}",
        label="Deal Detail",
        supports_prefill=False,
    ),
    "deals.create": RouteManifest(
        route="/deals/create",
        label="Create Deal",
        supports_prefill=True,
        prefill_keys=("account_id", "contact_id", "pipeline_id"),
    ),
    "admin.pipelines": RouteManifest(
        route="/admin/pipelines",
        label="Pipeline Configuration",
        supports_prefill=True,
        prefill_keys=("assistant_prefill_name", "assistant_prefill_stages"),
    ),
    "admin.deal_automations": RouteManifest(
        route="/admin/deal-automations",
        label="Deal Automations",
        supports_prefill=False,
    ),
    "admin.win_loss_reasons": RouteManifest(
        route="/admin/win-loss-reasons",
        label="Win/Loss Reasons",
        supports_prefill=False,
    ),
    "admin.quote_templates": RouteManifest(
        route="/admin/quote-templates",
        label="Quote Templates",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.3 Omni-Channel
    # -----------------------------------------------------------------------
    "omni.dashboard": RouteManifest(
        route="/admin/omni/dashboard",
        label="Omni-Channel Dashboard",
        supports_prefill=False,
    ),
    "interactions.inbox": RouteManifest(
        route="/admin/interactions/inbox",
        label="Interaction Inbox",
        supports_prefill=True,
        prefill_keys=("search", "type", "contact_id", "direction"),
    ),
    "interactions.channels": RouteManifest(
        route="/admin/interactions/channels",
        label="Channels Configuration",
        supports_prefill=False,
    ),
    "interactions.unmatched": RouteManifest(
        route="/admin/interactions/unmatched",
        label="Unmatched Items",
        supports_prefill=False,
    ),
    "contact_centre.stats": RouteManifest(
        route="/admin/queue-stats",
        label="Queue Statistics",
        supports_prefill=False,
    ),
    "chat.inbox": RouteManifest(
        route="/admin/chat/inbox",
        label="Chat Inbox",
        supports_prefill=False,
    ),
    "email.compose": RouteManifest(
        route="/admin/email/compose",
        label="Email Composer",
        supports_prefill=True,
        prefill_keys=("contact_id", "account_id", "subject"),
    ),
    "sms.compose": RouteManifest(
        route="/admin/sms/compose",
        label="SMS Composer",
        supports_prefill=True,
        prefill_keys=("contact_id",),
    ),
    "call.log": RouteManifest(
        route="/admin/call/log",
        label="Call Log",
        supports_prefill=True,
        prefill_keys=("contact_id",),
    ),
    "interactions.kiosk": RouteManifest(
        route="/admin/kiosk",
        label="Kiosk Interactions",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.4 Campaigns
    # -----------------------------------------------------------------------
    "campaigns.index": RouteManifest(
        route="/admin/campaigns",
        label="Campaigns",
        supports_prefill=True,
        prefill_keys=("status", "type", "segment_id"),
    ),
    "campaigns.builder": RouteManifest(
        route="/admin/campaigns/create",
        label="Campaign Builder",
        supports_prefill=True,
        prefill_keys=("type", "name", "segment_id"),
    ),
    "campaigns.analytics": RouteManifest(
        route="/admin/analytics/campaigns-dashboard",
        label="Campaign Analytics",
        supports_prefill=True,
        prefill_keys=("campaign_id",),
    ),
    "campaigns.templates": RouteManifest(
        route="/admin/campaign-templates",
        label="Campaign Templates",
        supports_prefill=False,
    ),
    "campaigns.email_templates": RouteManifest(
        route="/admin/email-templates",
        label="Email Template Editor",
        supports_prefill=False,
    ),
    "campaigns.multichannel_builder": RouteManifest(
        route="/admin/multichannel-builder",
        label="Multi-Channel Builder",
        supports_prefill=False,
    ),
    "campaigns.ab_testing": RouteManifest(
        route="/admin/ab-testing",
        label="A/B Testing",
        supports_prefill=False,
    ),
    "campaigns.drip_sequences": RouteManifest(
        route="/admin/drip-sequences",
        label="Drip Sequences",
        supports_prefill=False,
    ),
    "campaigns.tags": RouteManifest(
        route="/admin/tags",
        label="Tag Management",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.5 Loyalty & CX
    # -----------------------------------------------------------------------
    "loyalty.programs": RouteManifest(
        route="/admin/loyalty",
        label="Loyalty Programs",
        supports_prefill=False,
    ),
    "loyalty.ledger": RouteManifest(
        route="/admin/loyalty/ledger",
        label="Points Ledger",
        supports_prefill=True,
        prefill_keys=("contact_id", "program_id"),
    ),
    "loyalty.tiers": RouteManifest(
        route="/admin/loyalty/tiers",
        label="Loyalty Tiers",
        supports_prefill=True,
        prefill_keys=("program_id",),
    ),
    "loyalty.tier_display": RouteManifest(
        route="/admin/loyalty/tier-display",
        label="Tier Display Configuration",
        supports_prefill=False,
    ),
    "loyalty.rules": RouteManifest(
        route="/admin/loyalty/rules",
        label="Loyalty Rules",
        supports_prefill=True,
        prefill_keys=("program_id",),
    ),
    "loyalty.enrollments": RouteManifest(
        route="/admin/loyalty/enrollments",
        label="Loyalty Enrollments",
        supports_prefill=True,
        prefill_keys=("program_id", "contact_id"),
    ),
    "surveys.index": RouteManifest(
        route="/admin/surveys",
        label="Surveys",
        supports_prefill=False,
    ),
    "surveys.show": RouteManifest(
        route="/admin/surveys/{id}",
        label="Survey Detail",
        supports_prefill=False,
    ),
    "surveys.responses": RouteManifest(
        route="/admin/surveys/responses",
        label="Survey Responses",
        supports_prefill=True,
        prefill_keys=("survey_id",),
    ),
    "onboarding.journeys": RouteManifest(
        route="/admin/onboarding",
        label="Onboarding & Journeys",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.6 Support
    # -----------------------------------------------------------------------
    "tickets.index": RouteManifest(
        route="/support/tickets",
        label="Support Tickets",
        supports_prefill=True,
        prefill_keys=("search", "status", "priority", "assigned_to", "contact_id", "account_id", "sla"),
    ),
    "tickets.show": RouteManifest(
        route="/support/tickets/{id}",
        label="Ticket Detail",
        supports_prefill=False,
    ),
    "tickets.create": RouteManifest(
        route="/support/tickets/create",
        label="Create Support Ticket",
        supports_prefill=True,
        prefill_keys=("contact_id", "account_id", "priority", "category_id"),
    ),
    "support.knowledge_base": RouteManifest(
        route="/docs",
        label="Knowledge Base",
        supports_prefill=True,
        prefill_keys=("search", "category_id"),
    ),
    "support.canned_responses": RouteManifest(
        route="/admin/support/canned-responses",
        label="Canned Responses",
        supports_prefill=False,
    ),
    "support.csat_ratings": RouteManifest(
        route="/admin/support/csat",
        label="CSAT Ratings",
        supports_prefill=False,
    ),
    "admin.sla": RouteManifest(
        route="/admin/sla",
        label="SLA Policies",
        supports_prefill=True,
        prefill_keys=("assistant_prefill_name", "assistant_prefill_priority", "assistant_prefill_first_response", "assistant_prefill_resolution"),
    ),
    "admin.sla_instances": RouteManifest(
        route="/admin/sla/instances",
        label="SLA Instances",
        supports_prefill=True,
        prefill_keys=("status", "priority"),
    ),
    # -----------------------------------------------------------------------
    # 4.7 Analytics
    # -----------------------------------------------------------------------
    "analytics.dashboard": RouteManifest(
        route="/admin/analytics/dashboard",
        label="Analytics Dashboard",
        supports_prefill=False,
    ),
    "analytics.customer": RouteManifest(
        route="/admin/analytics/customer",
        label="Customer Analytics",
        supports_prefill=True,
        prefill_keys=("contact_id", "period"),
    ),
    "analytics.growth": RouteManifest(
        route="/admin/analytics/growth",
        label="Growth Analytics",
        supports_prefill=True,
        prefill_keys=("period",),
    ),
    "analytics.finance": RouteManifest(
        route="/admin/analytics/finance",
        label="Finance Analytics",
        supports_prefill=True,
        prefill_keys=("period",),
    ),
    "analytics.compliance": RouteManifest(
        route="/admin/analytics/compliance",
        label="Compliance Analytics",
        supports_prefill=True,
        prefill_keys=("period",),
    ),
    "analytics.predictive_scoring": RouteManifest(
        route="/admin/analytics/predictive-scoring",
        label="Predictive Scoring",
        supports_prefill=False,
    ),
    "analytics.report_builder": RouteManifest(
        route="/admin/analytics/report-builder",
        label="Report Builder",
        supports_prefill=False,
    ),
    "analytics.clv": RouteManifest(
        route="/admin/analytics/customer",
        label="CLV Analytics",
        supports_prefill=True,
        prefill_keys=("contact_id", "period"),
    ),
    "analytics.forecast": RouteManifest(
        route="/admin/analytics/dashboard",
        label="Revenue Forecast",
        supports_prefill=False,
    ),
    "analytics.churn_risk": RouteManifest(
        route="/admin/analytics/churn-risk",
        label="Churn Risk",
        supports_prefill=False,
    ),
    "analytics.forecast_time_bucketed": RouteManifest(
        route="/admin/analytics/forecast-time-bucketed",
        label="Time-Bucketed Forecast",
        supports_prefill=False,
    ),
    "analytics.exploratory": RouteManifest(
        route="/admin/analytics/exploratory",
        label="Exploratory Analysis",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.8 Contracts & Legal
    # -----------------------------------------------------------------------
    "contracts.index": RouteManifest(
        route="/contracts",
        label="Contracts",
        supports_prefill=True,
        prefill_keys=("status", "account_id", "contact_id", "type"),
    ),
    "contracts.show": RouteManifest(
        route="/contracts/{id}",
        label="Contract Detail",
        supports_prefill=False,
    ),
    "contracts.create": RouteManifest(
        route="/contracts/create",
        label="Create Contract",
        supports_prefill=True,
        prefill_keys=("template_id", "account_id", "contact_id", "deal_id", "step", "type"),
    ),
    "contracts.milestones": RouteManifest(
        route="/contracts/{id}/milestones",
        label="Contract Milestones",
        supports_prefill=False,
    ),
    "contracts.renewal_reminders": RouteManifest(
        route="/contracts/renewals",
        label="Renewal Reminders",
        supports_prefill=False,
    ),
    "contracts.e_signature": RouteManifest(
        route="/contracts/{id}/e-signature",
        label="E-Signature",
        supports_prefill=False,
    ),
    "contracts.repository": RouteManifest(
        route="/contracts/repository",
        label="Contract Repository",
        supports_prefill=False,
    ),
    "legal.index": RouteManifest(
        route="/legal",
        label="Legal Matters",
        supports_prefill=True,
        prefill_keys=("status", "type"),
    ),
    "legal.show": RouteManifest(
        route="/legal/{id}",
        label="Legal Matter Detail",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.9 Finance & Procurement
    # -----------------------------------------------------------------------
    "invoices.index": RouteManifest(
        route="/invoices",
        label="Invoices",
        supports_prefill=True,
        prefill_keys=("account_id", "status"),
    ),
    "invoices.show": RouteManifest(
        route="/invoices/{id}",
        label="Invoice Detail",
        supports_prefill=False,
    ),
    "vendors.index": RouteManifest(
        route="/vendors",
        label="Vendors",
        supports_prefill=True,
        prefill_keys=("search", "status"),
    ),
    "purchase_orders.index": RouteManifest(
        route="/purchase-orders",
        label="Purchase Orders",
        supports_prefill=True,
        prefill_keys=("status", "vendor_id"),
    ),
    "assets.index": RouteManifest(
        route="/assets",
        label="Assets",
        supports_prefill=True,
        prefill_keys=("search", "status", "type"),
    ),
    "employees.index": RouteManifest(
        route="/employees",
        label="Employees",
        supports_prefill=True,
        prefill_keys=("search", "department"),
    ),
    "finance.overview": RouteManifest(
        route="/finance",
        label="Finance Overview",
        supports_prefill=False,
    ),
    "finance.payments": RouteManifest(
        route="/finance/payments",
        label="Payment Recording",
        supports_prefill=True,
        prefill_keys=("account_id",),
    ),
    "finance.bank_details": RouteManifest(
        route="/finance/bank-details",
        label="Bank Details",
        supports_prefill=False,
    ),
    "finance.headcount": RouteManifest(
        route="/finance/headcount",
        label="Headcount Planning",
        supports_prefill=False,
    ),
    "finance.procurement_approval": RouteManifest(
        route="/finance/procurement-approval",
        label="Procurement Approval",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.10 Security
    # -----------------------------------------------------------------------
    "mfa.setup": RouteManifest(
        route="/mfa",
        label="MFA Setup",
        supports_prefill=False,
    ),
    "admin.security_events": RouteManifest(
        route="/admin/security/events",
        label="Security Events",
        supports_prefill=True,
        prefill_keys=("type", "severity"),
    ),
    "admin.privileged_sessions": RouteManifest(
        route="/admin/privileged",
        label="Privileged Sessions",
        supports_prefill=False,
    ),
    "admin.rbac": RouteManifest(
        route="/admin/rbac",
        label="RBAC Matrix",
        supports_prefill=False,
    ),
    "admin.sso": RouteManifest(
        route="/admin/sso",
        label="SSO Configuration",
        supports_prefill=False,
    ),
    "admin.data_classification": RouteManifest(
        route="/admin/data-classification",
        label="Data Classification",
        supports_prefill=False,
    ),
    "admin.dsr": RouteManifest(
        route="/admin/dsr",
        label="DSR Module",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.11 Integrations
    # -----------------------------------------------------------------------
    "integrations.marketplace": RouteManifest(
        route="/admin/integrations/marketplace",
        label="Integration Marketplace",
        supports_prefill=False,
    ),
    "integrations.webhooks": RouteManifest(
        route="/admin/integrations/webhooks",
        label="Webhooks",
        supports_prefill=False,
    ),
    "integrations.api_tokens": RouteManifest(
        route="/admin/api-tokens",
        label="API Tokens",
        supports_prefill=False,
    ),
    "integrations.oauth": RouteManifest(
        route="/admin/oauth-clients",
        label="OAuth2 Clients",
        supports_prefill=False,
    ),
    "integrations.index": RouteManifest(
        route="/admin/integrations",
        label="Integrations",
        supports_prefill=False,
    ),
    "integrations.service_registry": RouteManifest(
        route="/admin/integrations/service-registry",
        label="Service Registry",
        supports_prefill=False,
    ),
    "integrations.rate_limit_config": RouteManifest(
        route="/admin/integrations/rate-limit-config",
        label="Rate Limit Config",
        supports_prefill=False,
    ),
    "integrations.openapi_docs": RouteManifest(
        route="/admin/integrations/openapi-docs",
        label="OpenAPI Docs",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.12 Calendar & Notifications
    # -----------------------------------------------------------------------
    "calendar.index": RouteManifest(
        route="/calendar",
        label="Calendar",
        supports_prefill=False,
    ),
    "notifications.index": RouteManifest(
        route="/notifications",
        label="Notifications",
        supports_prefill=False,
    ),
    "discussions.index": RouteManifest(
        route="/discussions",
        label="Discussion Boards",
        supports_prefill=False,
    ),
    "calendar.file_attachments": RouteManifest(
        route="/files",
        label="File Attachments",
        supports_prefill=False,
    ),
    "calendar.team_calendar": RouteManifest(
        route="/admin/calendar/team",
        label="Team Calendar",
        supports_prefill=False,
    ),
    "notifications.mentions": RouteManifest(
        route="/notifications/mentions",
        label="Mentions",
        supports_prefill=False,
    ),
    # -----------------------------------------------------------------------
    # 4.15 Service & Support
    # -----------------------------------------------------------------------
    "service_catalog.index": RouteManifest(
        route="/service-catalog",
        label="Service Catalog",
        supports_prefill=True,
        prefill_keys=("search", "category_id"),
    ),
    "service_requests.index": RouteManifest(
        route="/service-requests",
        label="Service Requests",
        supports_prefill=True,
        prefill_keys=("status", "contact_id"),
    ),
    "service_requests.create": RouteManifest(
        route="/service-requests/create",
        label="Create Service Request",
        supports_prefill=True,
        prefill_keys=("catalog_item_id", "contact_id"),
    ),
    "cases.index": RouteManifest(
        route="/cases",
        label="Cases",
        supports_prefill=True,
        prefill_keys=("status", "type", "contact_id"),
    ),
    "cases.create": RouteManifest(
        route="/cases/create",
        label="Create Case",
        supports_prefill=True,
        prefill_keys=("contact_id", "type"),
    ),
    "cases.show": RouteManifest(
        route="/cases/{id}",
        label="Case Detail",
        supports_prefill=False,
    ),
}


def resolve(route_name: str, params: dict[str, Any] | None = None) -> NavigationTarget | None:
    manifest_entry = MANIFEST.get(route_name)
    if not manifest_entry:
        logger.warning("Unknown route manifest key: %s", route_name)
        return None
    url = manifest_entry.url(params or {})
    return NavigationTarget(route=url, label=manifest_entry.label, query=params or {})


def pick_best_route(intent: str, entities: dict[str, Any]) -> NavigationTarget | None:
    mapping = [
        # 4.1 Contacts & Accounts
        ("contact", "contacts.show", {"id": entities.get("contact_id", "")}),
        ("contacts", "contacts.index", {"search": entities.get("search", ""), "account_id": entities.get("account_id", "")}),
        ("create_contact", "contacts.create", {"account_id": entities.get("account_id", ""), "type": entities.get("type", "")}),
        ("account", "accounts.show", {"id": entities.get("account_id", "")}),
        ("accounts", "accounts.index", {"search": entities.get("search", ""), "industry": entities.get("industry", "")}),
        ("duplicate", "admin.duplicates", {}),
        ("custom_field", "admin.custom_fields", {}),
        ("scoring", "admin.scoring_rules", {}),
        ("bulk_import", "admin.bulk_import", {}),
        ("bulk_export", "admin.bulk_export", {}),
        # 4.2 Deals & Pipelines
        ("deal", "deals.show", {"id": entities.get("deal_id", "")}),
        ("deals", "deals.index", {"search": entities.get("search", ""), "stage": entities.get("stage", ""), "owner_id": entities.get("owner_id", "")}),
        ("create_deal", "deals.create", {"account_id": entities.get("account_id", ""), "contact_id": entities.get("contact_id", "")}),
        ("pipeline_board", "deals.board", {"pipeline_id": entities.get("pipeline_id", "")}),
        ("pipeline_setup", "admin.pipelines", {}),
        ("deal_automation", "admin.deal_automations", {}),
        ("win_loss", "admin.win_loss_reasons", {}),
        ("quote", "admin.quote_templates", {}),
        # 4.3 Omni-Channel
        ("inbox", "interactions.inbox", {"search": entities.get("search", ""), "contact_id": entities.get("contact_id", "")}),
        ("omni", "omni.dashboard", {}),
        ("contact_center", "contact_centre.stats", {}),
        ("chat", "chat.inbox", {}),
        ("compose_email", "email.compose", {"contact_id": entities.get("contact_id", ""), "account_id": entities.get("account_id", "")}),
        ("compose_sms", "sms.compose", {"contact_id": entities.get("contact_id", "")}),
        ("call_log", "call.log", {"contact_id": entities.get("contact_id", "")}),
        ("kiosk", "interactions.kiosk", {}),
        # 4.4 Campaigns
        ("campaigns", "campaigns.index", {"status": entities.get("status", "")}),
        ("campaign_builder", "campaigns.builder", {"type": entities.get("type", "")}),
        ("campaign_analytics", "campaigns.analytics", {"campaign_id": entities.get("campaign_id", "")}),
        ("campaign_template", "campaigns.templates", {}),
        ("drip", "campaigns.drip_sequences", {}),
        ("tag", "campaigns.tags", {}),
        ("email_template", "campaigns.email_templates", {}),
        ("multichannel", "campaigns.multichannel_builder", {}),
        ("ab_test", "campaigns.ab_testing", {}),
        # 4.5 Loyalty & CX
        ("loyalty", "loyalty.programs", {}),
        ("points", "loyalty.ledger", {"contact_id": entities.get("contact_id", "")}),
        ("tier", "loyalty.tiers", {"program_id": entities.get("program_id", "")}),
        ("tier_display", "loyalty.tier_display", {"program_id": entities.get("program_id", "")}),
        ("survey", "surveys.index", {}),
        ("survey_detail", "surveys.show", {"id": entities.get("survey_id", "")}),
        ("survey_response", "surveys.responses", {"survey_id": entities.get("survey_id", "")}),
        ("onboarding", "onboarding.journeys", {}),
        # 4.6 Support
        ("ticket", "tickets.show", {"id": entities.get("ticket_id", "")}),
        ("tickets", "tickets.index", {"status": entities.get("status", ""), "priority": entities.get("priority", ""), "account_id": entities.get("account_id", ""), "sla": entities.get("sla", "")}),
        ("create_ticket", "tickets.create", {"contact_id": entities.get("contact_id", ""), "account_id": entities.get("account_id", "")}),
        ("knowledge", "support.knowledge_base", {"search": entities.get("search", "")}),
        ("canned", "support.canned_responses", {}),
        ("csat", "support.csat_ratings", {}),
        ("sla_setup", "admin.sla", {}),
        ("sla_instances", "admin.sla_instances", {"status": entities.get("status", "")}),
        # 4.7 Analytics
        ("analytics", "analytics.dashboard", {}),
        ("customer_analytics", "analytics.customer", {"contact_id": entities.get("contact_id", "")}),
        ("growth", "analytics.growth", {}),
        ("finance_analytics", "analytics.finance", {}),
        ("compliance", "analytics.compliance", {}),
        ("predictive", "analytics.predictive_scoring", {}),
        ("report", "analytics.report_builder", {}),
        ("clv", "analytics.clv", {"contact_id": entities.get("contact_id", "")}),
        ("forecast", "analytics.forecast", {}),
        ("churn_risk", "analytics.churn_risk", {}),
        ("forecast_time_bucketed", "analytics.forecast_time_bucketed", {}),
        ("exploratory", "analytics.exploratory", {}),
        # 4.8 Contracts & Legal
        ("contracts", "contracts.index", {"status": entities.get("status", "")}),
        ("contract", "contracts.show", {"id": entities.get("contract_id", "")}),
        ("contract_generate", "contracts.create", {"template_id": entities.get("template_id", ""), "account_id": entities.get("account_id", "")}),
        ("contract_milestones", "contracts.milestones", {"id": entities.get("contract_id", "")}),
        ("contract_renewals", "contracts.renewal_reminders", {}),
        ("contract_esignature", "contracts.e_signature", {"id": entities.get("contract_id", "")}),
        ("contract_repository", "contracts.repository", {}),
        ("legal", "legal.index", {"status": entities.get("status", "")}),
        ("legal_matter", "legal.show", {"id": entities.get("legal_matter_id", "")}),
        # 4.9 Finance & Procurement
        ("invoices", "invoices.index", {"account_id": entities.get("account_id", "")}),
        ("invoice", "invoices.show", {"id": entities.get("invoice_id", "")}),
        ("vendors", "vendors.index", {}),
        ("purchase_order", "purchase_orders.index", {}),
        ("assets", "assets.index", {}),
        ("employees", "employees.index", {}),
        ("finance_overview", "finance.overview", {}),
        ("finance_payments", "finance.payments", {}),
        ("finance_bank_details", "finance.bank_details", {}),
        ("finance_headcount", "finance.headcount", {}),
        ("finance_procurement", "finance.procurement_approval", {}),
        # 4.10 Security
        ("mfa", "mfa.setup", {}),
        ("security_event", "admin.security_events", {}),
        ("privileged", "admin.privileged_sessions", {}),
        ("rbac", "admin.rbac", {}),
        ("sso", "admin.sso", {}),
        ("data_classification", "admin.data_classification", {}),
        ("dsr", "admin.dsr", {}),
        # 4.11 Integrations
        ("marketplace", "integrations.marketplace", {}),
        ("webhook", "integrations.webhooks", {}),
        ("api_token", "integrations.api_tokens", {}),
        ("oauth", "integrations.oauth", {}),
        ("integration_setup", "integrations.index", {}),
        ("service_registry", "integrations.service_registry", {}),
        ("rate_limit_config", "integrations.rate_limit_config", {}),
        ("openapi_docs", "integrations.openapi_docs", {}),
        # 4.12 Calendar & Notifications
        ("calendar", "calendar.index", {}),
        ("notification", "notifications.index", {}),
        ("discussion", "discussions.index", {}),
        ("file_attachments", "calendar.file_attachments", {}),
        ("team_calendar", "calendar.team_calendar", {}),
        ("mentions", "notifications.mentions", {}),
        # 4.15 Service & Support
        ("service_catalog", "service_catalog.index", {}),
        ("service_request", "service_requests.index", {"status": entities.get("status", "")}),
        ("create_service_request", "service_requests.create", {"catalog_item_id": entities.get("catalog_item_id", "")}),
        ("cases", "cases.index", {"status": entities.get("status", "")}),
        ("create_case", "cases.create", {"contact_id": entities.get("contact_id", "")}),
        ("case_detail", "cases.show", {"id": entities.get("case_id", "")}),
    ]

    lowered = intent.lower()
    for keyword, route_name, defaults in mapping:
        if keyword in lowered:
            merged = {k: v for k, v in defaults.items() if v}
            merged.update({k: v for k, v in entities.items() if v})
            return resolve(route_name, merged if merged else None)
    return None