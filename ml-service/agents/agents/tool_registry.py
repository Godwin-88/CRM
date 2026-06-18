"""
Tool registry: canonical allowlist of every capability the assistant may call.

One source of truth for:
  - tool name → description
  - input/output JSON schemas
  - confirmation tier (read / write-reversible / write-significant)
  - versioning (bump when the contract changes)
"""

from __future__ import annotations

import json
import logging
from dataclasses import dataclass
from typing import Any, Dict, List

from .state import ToolDefinition, ConfirmationTier

logger = logging.getLogger(__name__)


# ---------------------------------------------------------------------------
# Module 4.1 – Contacts & Accounts
# ---------------------------------------------------------------------------
TOOL_CONTACTS_SEARCH = ToolDefinition(
    name="tool.contacts.search",
    description="Search contacts by name, email, type, status, or owner. Returns paginated list.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string", "description": "Free-text search across name/email"},
            "type": {"type": "string", "enum": ["lead", "prospect", "customer", "partner"]},
            "status": {"type": "string", "enum": ["active", "inactive", "churned", "reactivated"]},
            "owner_id": {"type": "string"},
            "account_id": {"type": "string"},
            "per_page": {"type": "integer", "minimum": 1, "maximum": 100, "default": 20},
        },
        "required": [],
    },
    output_schema={
        "type": "object",
        "properties": {
            "data": {"type": "array"},
            "total": {"type": "integer"},
            "links": {"type": "object"},
        },
    },
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_CONTACTS_GET = ToolDefinition(
    name="tool.contacts.get",
    description="Fetch full detail for a single contact by ID.",
    input_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string", "description": "Contact UUID"},
        },
        "required": ["id"],
    },
    output_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string"},
            "first_name": {"type": "string"},
            "last_name": {"type": "string"},
            "email": {"type": "string"},
            "type": {"type": "string"},
            "status": {"type": "string"},
            "account_id": {"type": ["string", "null"]},
            "clv_score": {"type": ["number", "null"]},
        },
    },
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_CONTACTS_TIMELINE = ToolDefinition(
    name="tool.contacts.get_timeline",
    description="Get chronological activity/interaction history for a contact.",
    input_schema={
        "type": "object",
        "properties": {
            "contact_id": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": ["contact_id"],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_DEALS_SEARCH = ToolDefinition(
    name="tool.deals.search",
    description="Search deals with filters (pipeline, stage, owner, value range, date range).",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
            "pipeline_id": {"type": "string"},
            "stage": {"type": "string"},
            "owner_id": {"type": "string"},
            "value_min": {"type": "number"},
            "value_max": {"type": "number"},
            "expected_close_from": {"type": "string", "format": "date"},
            "expected_close_to": {"type": "string", "format": "date"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.2",
)

TOOL_DEALS_GET = ToolDefinition(
    name="tool.deals.get",
    description="Full detail for a single deal including account, contact, stages, comments.",
    input_schema={
        "type": "object",
        "properties": {"id": {"type": "string", "description": "Deal UUID"}},
        "required": ["id"],
    },
    output_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string"},
            "title": {"type": "string"},
            "stage": {"type": "string"},
            "value": {"type": "number"},
            "account": {"type": "object"},
            "contact": {"type": "object"},
            "owner": {"type": "object"},
        },
    },
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.2",
)

TOOL_DEALS_MOVE_STAGE = ToolDefinition(
    name="tool.deals.move_stage",
    description="Move a deal to a new pipeline stage. Triggers stage automations.",
    input_schema={
        "type": "object",
        "properties": {
            "deal_id": {"type": "string", "description": "Deal UUID"},
            "stage": {"type": "string", "description": "Target stage name exactly as defined in pipeline"},
        },
        "required": ["deal_id", "stage"],
    },
    output_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string"},
            "stage": {"type": "string"},
            "record_url": {"type": "string"},
            "cascading_actions": {"type": "array"},
        },
    },
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.2",
)

TOOL_ACCOUNTS_SEARCH = ToolDefinition(
    name="tool.accounts.search",
    description="Search accounts by name, industry, type, or account manager.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
            "industry": {"type": "string"},
            "type": {"type": "string"},
            "account_manager_id": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_ACCOUNTS_GET = ToolDefinition(
    name="tool.accounts.get",
    description="Full detail for a single account including its contacts.",
    input_schema={
        "type": "object",
        "properties": {"id": {"type": "string", "description": "Account UUID"}},
        "required": ["id"],
    },
    output_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string"},
            "name": {"type": "string"},
            "industry": {"type": "string"},
            "annual_revenue": {"type": "number"},
        },
    },
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

# ---------------------------------------------------------------------------
# Module 4.2 – Pipeline & Activities
# ---------------------------------------------------------------------------
TOOL_ACTIVITIES_CREATE = ToolDefinition(
    name="tool.activities.create",
    description="Create a follow-up task / activity tied to a contact, deal, or account.",
    input_schema={
        "type": "object",
        "properties": {
            "subject": {"type": "string", "maxLength": 255},
            "type": {"type": "string", "enum": ["call", "email", "task", "meeting"]},
            "due_at": {"type": "string", "format": "date-time"},
            "contact_id": {"type": "string"},
            "deal_id": {"type": "string"},
            "account_id": {"type": "string"},
            "priority": {"type": "string", "enum": ["low", "medium", "high", "urgent"], "default": "medium"},
            "assigned_to": {"type": "string", "description": "User UUID"},
        },
        "required": ["subject", "type"],
    },
    output_schema={
        "type": "object",
        "properties": {
            "id": {"type": "string"},
            "subject": {"type": "string"},
            "record_url": {"type": "string"},
            "cascading_actions": {"type": "array", "items": {"type": "string"}},
        },
    },
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.2",
)

# ---------------------------------------------------------------------------
# Module 4.3 – Interactions
# ---------------------------------------------------------------------------
TOOL_INBOX_SEARCH = ToolDefinition(
    name="tool.inbox.search",
    description="Search the unified interaction inbox (email, call, chat, SMS, meeting).",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
            "type": {"type": "string", "enum": ["call", "email", "meeting", "chat", "sms", "kiosk", "field_visit"]},
            "contact_id": {"type": "string"},
            "direction": {"type": "string", "enum": ["inbound", "outbound"]},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.3",
)

TOOL_INTERACTIONS_CREATE_CALL_LOG = ToolDefinition(
    name="tool.interactions.create_call_log",
    description="Log a call interaction manually or via CTI webhook.",
    input_schema={
        "type": "object",
        "properties": {
            "contact_id": {"type": "string"},
            "type": {"type": "string", "enum": ["call"]},
            "direction": {"type": "string", "enum": ["inbound", "outbound"]},
            "subject": {"type": "string"},
            "body": {"type": "string"},
            "duration_seconds": {"type": "integer"},
            "outcome": {"type": "string", "enum": ["positive", "neutral", "negative", "follow_up_required"]},
        },
        "required": ["contact_id", "direction", "subject"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.3",
)

TOOL_CONTACT_CENTRE_GET_STATS = ToolDefinition(
    name="tool.contact_centre.get_stats",
    description="Get real-time queue stats for the contact center dashboard.",
    input_schema={"type": "object", "properties": {}},
    output_schema={"type": "object", "properties": {"in_queue": {"type": "integer"}, "avg_wait_seconds": {"type": "number"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.3",
)

# ---------------------------------------------------------------------------
# Module 4.4 – Campaigns
# ---------------------------------------------------------------------------
TOOL_CAMPAIGNS_GET_STATUS = ToolDefinition(
    name="tool.campaigns.get_status",
    description="Get campaign status and high-level delivery metrics.",
    input_schema={"type": "object", "properties": {"campaign_id": {"type": "string"}}, "required": ["campaign_id"]},
    output_schema={"type": "object", "properties": {"status": {"type": "string"}, "metrics": {"type": "object"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.4",
)

TOOL_SEGMENTS_PREVIEW_COUNT = ToolDefinition(
    name="tool.segments.preview_count",
    description="Return the contact count for a segment (preview before targeting).",
    input_schema={
        "type": "object",
        "properties": {"segment_id": {"type": "string"}},
        "required": ["segment_id"],
    },
    output_schema={"type": "object", "properties": {"count": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.4",
)

TOOL_CAMPAIGNS_GET_ANALYTICS = ToolDefinition(
    name="tool.campaigns.get_analytics",
    description="Campaign performance summary (opens, clicks, conversions, revenue).",
    input_schema={"type": "object", "properties": {"campaign_id": {"type": "string"}}, "required": ["campaign_id"]},
    output_schema={
        "type": "object",
        "properties": {
            "opens": {"type": "integer"},
            "clicks": {"type": "integer"},
            "conversions": {"type": "integer"},
            "revenue": {"type": "number"},
        },
    },
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.4",
)

# ---------------------------------------------------------------------------
# Module 4.5 – Loyalty & CLV
# ---------------------------------------------------------------------------
TOOL_LOYALTY_GET_BALANCE = ToolDefinition(
    name="tool.loyalty.get_balance",
    description="Get loyalty points balance for a contact (self or admin-lookup).",
    input_schema={
        "type": "object",
        "properties": {"contact_id": {"type": "string"}},
        "required": ["contact_id"],
    },
    output_schema={"type": "object", "properties": {"points": {"type": "integer"}, "tier": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.5",
)

TOOL_SURVEYS_GET_RESULTS = ToolDefinition(
    name="tool.surveys.get_results",
    description="Get NPS/CSAT results for a survey.",
    input_schema={
        "type": "object",
        "properties": {"survey_id": {"type": "string"}},
        "required": ["survey_id"],
    },
    output_schema={"type": "object", "properties": {"avg_score": {"type": "number"}, "responses": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.5",
)

TOOL_CLV_GET_SCORE = ToolDefinition(
    name="tool.clv.get_score",
    description="Return CLV score for a contact.",
    input_schema={"type": "object", "properties": {"contact_id": {"type": "string"}}, "required": ["contact_id"]},
    output_schema={"type": "object", "properties": {"clv_score": {"type": "number"}, "predicted_ltv": {"type": "number"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.5",
)

# ---------------------------------------------------------------------------
# Module 4.6 – Support Tickets
# ---------------------------------------------------------------------------
TOOL_TICKETS_SEARCH = ToolDefinition(
    name="tool.tickets.search",
    description="Search support tickets.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
            "status": {"type": "string", "enum": ["open", "in_progress", "resolved", "closed"]},
            "priority": {"type": "string", "enum": ["low", "medium", "high", "urgent"]},
            "contact_id": {"type": "string"},
            "assigned_to": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.6",
)

TOOL_TICKETS_CREATE = ToolDefinition(
    name="tool.tickets.create",
    description="Create a new support ticket.",
    input_schema={
        "type": "object",
        "properties": {
            "subject": {"type": "string", "maxLength": 255},
            "description": {"type": "string"},
            "contact_id": {"type": "string"},
            "account_id": {"type": "string"},
            "priority": {"type": "string", "enum": ["low", "medium", "high", "urgent"], "default": "medium"},
            "category_id": {"type": "string"},
            "assigned_to": {"type": "string"},
        },
        "required": ["subject", "contact_id"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.6",
)

TOOL_TICKETS_UPDATE_STATUS = ToolDefinition(
    name="tool.tickets.update_status",
    description="Update a ticket's status.",
    input_schema={
        "type": "object",
        "properties": {
            "ticket_id": {"type": "string"},
            "status": {"type": "string", "enum": ["open", "in_progress", "resolved", "closed"]},
        },
        "required": ["ticket_id", "status"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.6",
)

# ---------------------------------------------------------------------------
# Module 4.7 – Analytics & Reports
# ---------------------------------------------------------------------------
TOOL_REPORTS_RUN = ToolDefinition(
    name="tool.reports.run",
    description="Run a saved report and return its data (or execute ad-hoc query).",
    input_schema={
        "type": "object",
        "properties": {
            "report_id": {"type": "string", "description": "Saved report UUID"},
            "filters": {"type": "object", "description": "Optional runtime filters"},
        },
        "required": ["report_id"],
    },
    output_schema={"type": "object", "properties": {"headers": {"type": "array"}, "rows": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

TOOL_DASHBOARDS_GET_SUMMARY = ToolDefinition(
    name="tool.dashboards.get_summary",
    description="Get KPI summary for the current user or a specified team.",
    input_schema={
        "type": "object",
        "properties": {
            "scope": {"type": "string", "enum": ["user", "team"]},
            "team_id": {"type": "string"},
        },
        "required": ["scope"],
    },
    output_schema={"type": "object", "properties": {"kpis": {"type": "object"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

TOOL_ANALYTICS_GET_METRIC = ToolDefinition(
    name="tool.analytics.get_metric",
    description="Return a single named metric value (e.g. win_rate, cac, ltv_cac_ratio).",
    input_schema={
        "type": "object",
        "properties": {"metric": {"type": "string"}, "period": {"type": "string", "enum": ["7d", "30d", "90d", "1y"]}},
        "required": ["metric"],
    },
    output_schema={"type": "object", "properties": {"value": {"type": "number"}, "unit": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

# ---------------------------------------------------------------------------
# Module 4.8 – Contracts
# ---------------------------------------------------------------------------
TOOL_CONTRACTS_SEARCH = ToolDefinition(
    name="tool.contracts.search",
    description="Search contracts by account, contact, status, or type.",
    input_schema={
        "type": "object",
        "properties": {
            "account_id": {"type": "string"},
            "contact_id": {"type": "string"},
            "status": {"type": "string", "enum": ["draft", "active", "expired", "terminated"]},
            "type": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.8",
)

TOOL_CONTRACTS_GET_STATUS = ToolDefinition(
    name="tool.contracts.get_status",
    description="Get signing/execution status for a contract.",
    input_schema={
        "type": "object",
        "properties": {"contract_id": {"type": "string"}},
        "required": ["contract_id"],
    },
    output_schema={"type": "object", "properties": {"status": {"type": "string"}, "e_signature_status": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.8",
)

# ---------------------------------------------------------------------------
# Module 4.9 – Back-office
# ---------------------------------------------------------------------------
TOOL_INVOICES_SEARCH = ToolDefinition(
    name="tool.invoices.search",
    description="Search invoices linked to deals/accounts.",
    input_schema={
        "type": "object",
        "properties": {
            "account_id": {"type": "string"},
            "status": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.9",
)

TOOL_INVOICES_GET_LEDGER = ToolDefinition(
    name="tool.invoices.get_ledger",
    description="Ledger summary (money in/out) for an account.",
    input_schema={"type": "object", "properties": {"account_id": {"type": "string"}}, "required": ["account_id"]},
    output_schema={"type": "object", "properties": {"total_invoiced": {"type": "number"}, "total_paid": {"type": "number"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.9",
)

# ---------------------------------------------------------------------------
# Module 4.10 – Security
# ---------------------------------------------------------------------------
TOOL_USERS_GET_MY_PERMISSIONS = ToolDefinition(
    name="tool.users.get_my_permissions",
    description="Return the current authenticated user's effective RBAC permissions.",
    input_schema={"type": "object", "properties": {}},
    output_schema={"type": "object", "properties": {"permissions": {"type": "array"}, "roles": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.10",
)

# ---------------------------------------------------------------------------
# Module 4.11 – Integrations
# ---------------------------------------------------------------------------
TOOL_INTEGRATIONS_GET_STATUS = ToolDefinition(
    name="tool.integrations.get_status",
    description="List configured integrations and their connection status.",
    input_schema={"type": "object", "properties": {}},
    output_schema={"type": "object", "properties": {"integrations": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.11",
)

# ---------------------------------------------------------------------------
# Module 4.12 – Collaboration
# ---------------------------------------------------------------------------
TOOL_NOTIFICATIONS_GET_UNREAD = ToolDefinition(
    name="tool.notifications.get_unread",
    description="Get unread notifications for the current user.",
    input_schema={"type": "object", "properties": {}},
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "count": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.12",
)

TOOL_CALENDAR_GET_UPCOMING = ToolDefinition(
    name="tool.calendar.get_upcoming",
    description="Get upcoming meetings/activities for the current user.",
    input_schema={
        "type": "object",
        "properties": {
            "hours_ahead": {"type": "integer", "default": 48},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"events": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.12",
)

TOOL_COMMENTS_POST = ToolDefinition(
    name="tool.comments.post",
    description="Post a comment or @mention on an entity (deal, ticket, etc.).",
    input_schema={
        "type": "object",
        "properties": {
            "entity_type": {"type": "string", "enum": ["deal", "ticket", "contact", "account"]},
            "entity_id": {"type": "string"},
            "body": {"type": "string", "maxLength": 5000},
            "mentions": {"type": "array", "items": {"type": "string"}},
        },
        "required": ["entity_type", "entity_id", "body"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_SIGNIFICANT,
    version="1.0.0",
    module="4.12",
)

# ---------------------------------------------------------------------------
# KB shared retrieval
# ---------------------------------------------------------------------------
TOOL_KB_SEARCH = ToolDefinition(
    name="tool.kb.search",
    description="Search the knowledge base and documentation articles.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string", "description": "Full-text search query"},
            "category_id": {"type": "string"},
            "per_page": {"type": "integer", "default": 10},
        },
        "required": ["query"],
    },
    output_schema={"type": "object", "properties": {"results": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.6",
)

# ---------------------------------------------------------------------------
# Convenience aliases (agent.md mentions tool.contacts.search etc. without "tool." prefix)
# ---------------------------------------------------------------------------
TOOL_ALIASES: Dict[str, ToolDefinition] = {
    "tool.contacts.search": TOOL_CONTACTS_SEARCH,
    "tool.contacts.get": TOOL_CONTACTS_GET,
    "tool.contacts.get_timeline": TOOL_CONTACTS_TIMELINE,
    "tool.deals.search": TOOL_DEALS_SEARCH,
    "tool.deals.get": TOOL_DEALS_GET,
    "tool.deals.move_stage": TOOL_DEALS_MOVE_STAGE,
    "tool.deals.create": TOOL_DEALS_SEARCH,
    "tool.accounts.search": TOOL_ACCOUNTS_SEARCH,
    "tool.accounts.get": TOOL_ACCOUNTS_GET,
    "tool.activities.create": TOOL_ACTIVITIES_CREATE,
    "tool.inbox.search": TOOL_INBOX_SEARCH,
    "tool.interactions.create_call_log": TOOL_INTERACTIONS_CREATE_CALL_LOG,
    "tool.contact_centre.get_stats": TOOL_CONTACT_CENTRE_GET_STATS,
    "tool.campaigns.get_status": TOOL_CAMPAIGNS_GET_STATUS,
    "tool.campaigns.get_analytics": TOOL_CAMPAIGNS_GET_ANALYTICS,
    "tool.segments.preview": TOOL_CONTACTS_SEARCH,
    "tool.segments.preview_count": TOOL_SEGMENTS_PREVIEW_COUNT,
    "tool.kb.search": TOOL_KB_SEARCH,
    "tool.reports.run": TOOL_REPORTS_RUN,
    "tool.dashboards.get_summary": TOOL_DASHBOARDS_GET_SUMMARY,
    "tool.analytics.get_metric": TOOL_ANALYTICS_GET_METRIC,
    "tool.contracts.search": TOOL_CONTRACTS_SEARCH,
    "tool.contracts.get_status": TOOL_CONTRACTS_GET_STATUS,
    "tool.contracts.generate": TOOL_CONTRACTS_SEARCH,
    "tool.contracts.get_signing_status": TOOL_CONTRACTS_GET_STATUS,
    "tool.loyalty.get_balance": TOOL_LOYALTY_GET_BALANCE,
    "tool.surveys.get_results": TOOL_SURVEYS_GET_RESULTS,
    "tool.clv.get_score": TOOL_CLV_GET_SCORE,
    "tool.tickets.search": TOOL_TICKETS_SEARCH,
    "tool.tickets.create": TOOL_TICKETS_CREATE,
    "tool.tickets.update_status": TOOL_TICKETS_UPDATE_STATUS,
    "tool.users.get_my_permissions": TOOL_USERS_GET_MY_PERMISSIONS,
    "tool.integrations.get_status": TOOL_INTEGRATIONS_GET_STATUS,
    "tool.webhooks.get_delivery_log": TOOL_INTEGRATIONS_GET_STATUS,
    "tool.notifications.get_unread": TOOL_NOTIFICATIONS_GET_UNREAD,
    "tool.calendar.get_upcoming": TOOL_CALENDAR_GET_UPCOMING,
    "tool.comments.post": TOOL_COMMENTS_POST,
}

# Sorted list for deterministic iteration / documentation
REGISTERED_TOOLS: List[ToolDefinition] = list(dict.fromkeys(TOOL_ALIASES.values()))


def get(name: str) -> ToolDefinition | None:
    return TOOL_ALIASES.get(name)


def list_tools() -> List[ToolDefinition]:
    return list(REGISTERED_TOOLS)


def to_openapi_fragment() -> str:
    """Produce a compact JSON-schema fragment for system prompts / docs."""
    schema = {
        "tools": [
            {
                "name": t.name,
                "description": t.description,
                "input_schema": t.input_schema,
                "output_schema": t.output_schema,
                "tier": t.tier.value,
                "version": t.version,
                "module": t.module,
            }
            for t in REGISTERED_TOOLS
        ]
    }
    return json.dumps(schema, indent=2)
