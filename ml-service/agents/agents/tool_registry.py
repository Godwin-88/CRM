"""
Tool registry: canonical allowlist of every capability the assistant may call.

One source of truth for:
  - tool name → description
  - input/output JSON schemas
  - confirmation tier (read / write-reversible / write-significant)
  - versioning (bump when the contract changes)

Covers all modules 4.1–4.15 per docs/agent.md spec.
"""

from __future__ import annotations

import json
import logging
from typing import Any, Dict, List

from .state import ToolDefinition, ConfirmationTier

logger = logging.getLogger(__name__)


# ---------------------------------------------------------------------------
# 4.1 Contacts & Accounts
# ---------------------------------------------------------------------------
TOOL_CONTACTS_CREATE = ToolDefinition(
    name="tool.contacts.create",
    description="Create a new contact (lead, prospect, customer, etc.).",
    input_schema={
        "type": "object",
        "properties": {
            "first_name": {"type": "string", "maxLength": 100},
            "last_name": {"type": "string", "maxLength": 100},
            "email": {"type": "string", "format": "email"},
            "phone": {"type": "string"},
            "type": {"type": "string", "enum": ["lead", "prospect", "customer", "partner"], "default": "lead"},
            "status": {"type": "string", "enum": ["active", "inactive"], "default": "active"},
            "account_id": {"type": "string"},
            "owner_id": {"type": "string"},
        },
        "required": ["first_name", "last_name", "email"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.1",
)

TOOL_CONTACTS_SEARCH = ToolDefinition(
    name="tool.contacts.search",
    description="Search contacts by name, email, type, status, or owner. Returns paginated list.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
            "type": {"type": "string", "enum": ["lead", "prospect", "customer", "partner"]},
            "status": {"type": "string", "enum": ["active", "inactive", "churned", "reactivated"]},
            "owner_id": {"type": "string"},
            "account_id": {"type": "string"},
            "per_page": {"type": "integer", "minimum": 1, "maximum": 100, "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_CONTACTS_GET = ToolDefinition(
    name="tool.contacts.get",
    description="Fetch full detail for a single contact by ID.",
    input_schema={"type": "object", "properties": {"id": {"type": "string"}}, "required": ["id"]},
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "first_name": {"type": "string"}, "last_name": {"type": "string"}, "email": {"type": "string"}, "type": {"type": "string"}, "status": {"type": "string"}, "account_id": {"type": ["string", "null"]}, "clv_score": {"type": ["number", "null"]}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

TOOL_CONTACTS_TIMELINE = ToolDefinition(
    name="tool.contacts.get_timeline",
    description="Get chronological activity/interaction history for a contact.",
    input_schema={"type": "object", "properties": {"contact_id": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}, "required": ["contact_id"]},
    output_schema={"type": "object", "properties": {"data": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
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
    input_schema={"type": "object", "properties": {"id": {"type": "string"}}, "required": ["id"]},
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "name": {"type": "string"}, "industry": {"type": "string"}, "annual_revenue": {"type": "number"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.1",
)

# ---------------------------------------------------------------------------
# 4.2 Deals & Pipelines
# ---------------------------------------------------------------------------
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
    input_schema={"type": "object", "properties": {"id": {"type": "string"}}, "required": ["id"]},
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "title": {"type": "string"}, "stage": {"type": "string"}, "value": {"type": "number"}, "account": {"type": "object"}, "contact": {"type": "object"}, "owner": {"type": "object"}}},
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
            "deal_id": {"type": "string"},
            "stage": {"type": "string"},
        },
        "required": ["deal_id", "stage"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "stage": {"type": "string"}, "record_url": {"type": "string"}, "cascading_actions": {"type": "array"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.2",
)

TOOL_DEALS_GET_FORECAST = ToolDefinition(
    name="tool.deals.get_forecast",
    description="Get stage-probability weighted revenue forecast for a pipeline or team.",
    input_schema={
        "type": "object",
        "properties": {
            "pipeline_id": {"type": "string"},
            "team_id": {"type": "string"},
            "period": {"type": "string", "enum": ["this_quarter", "next_quarter", "this_month", "next_month"]},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"forecast": {"type": "number"}, "weighted": {"type": "number"}, "stages": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.2",
)

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
            "assigned_to": {"type": "string"},
        },
        "required": ["subject", "type"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "subject": {"type": "string"}, "record_url": {"type": "string"}, "cascading_actions": {"type": "array"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.2",
)

# ---------------------------------------------------------------------------
# 4.3 Omni-Channel Interactions
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
# 4.4 Campaigns
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

TOOL_CAMPAIGNS_GET_ANALYTICS = ToolDefinition(
    name="tool.campaigns.get_analytics",
    description="Campaign performance summary (opens, clicks, conversions, revenue).",
    input_schema={"type": "object", "properties": {"campaign_id": {"type": "string"}}, "required": ["campaign_id"]},
    output_schema={"type": "object", "properties": {"opens": {"type": "integer"}, "clicks": {"type": "integer"}, "conversions": {"type": "integer"}, "revenue": {"type": "number"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.4",
)

TOOL_SEGMENTS_PREVIEW_COUNT = ToolDefinition(
    name="tool.segments.preview_count",
    description="Return the contact count for a segment (preview before targeting).",
    input_schema={"type": "object", "properties": {"segment_id": {"type": "string"}}, "required": ["segment_id"]},
    output_schema={"type": "object", "properties": {"count": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.4",
)

# ---------------------------------------------------------------------------
# 4.5 Loyalty & CX
# ---------------------------------------------------------------------------
TOOL_LOYALTY_GET_BALANCE = ToolDefinition(
    name="tool.loyalty.get_balance",
    description="Get loyalty points balance for a contact (self or admin-lookup).",
    input_schema={"type": "object", "properties": {"contact_id": {"type": "string"}}, "required": ["contact_id"]},
    output_schema={"type": "object", "properties": {"points": {"type": "integer"}, "tier": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.5",
)

TOOL_SURVEYS_GET_RESULTS = ToolDefinition(
    name="tool.surveys.get_results",
    description="Get NPS/CSAT results for a survey.",
    input_schema={"type": "object", "properties": {"survey_id": {"type": "string"}}, "required": ["survey_id"]},
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
# 4.6 Support Tickets
# ---------------------------------------------------------------------------
TOOL_TICKETS_SEARCH = ToolDefinition(
    name="tool.tickets.search",
    description="Search support tickets with filters.",
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

TOOL_KB_SEARCH = ToolDefinition(
    name="tool.kb.search",
    description="Search the knowledge base and documentation articles.",
    input_schema={
        "type": "object",
        "properties": {
            "query": {"type": "string"},
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

TOOL_KB_CREATE = ToolDefinition(
    name="tool.kb.create",
    description="Create a new knowledge base article (admin/manager only).",
    input_schema={
        "type": "object",
        "required": ["title", "body", "category_id"],
        "properties": {
            "title": {"type": "string", "maxLength": 255},
            "body": {"type": "string"},
            "category_id": {"type": "string"},
            "slug": {"type": "string", "maxLength": 255},
            "status": {"type": "string", "enum": ["draft", "in_review", "approved", "published", "archived"]},
            "audience": {"type": "string", "enum": ["agent", "manager", "admin", "all"]},
            "feature_refs": {"type": "array"},
        },
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "title": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_SIGNIFICANT,
    version="1.0.0",
    module="4.6",
)

TOOL_KB_UPDATE = ToolDefinition(
    name="tool.kb.update",
    description="Update an existing knowledge base article (admin/manager only).",
    input_schema={
        "type": "object",
        "required": ["article_id"],
        "properties": {
            "article_id": {"type": "string"},
            "title": {"type": "string", "maxLength": 255},
            "body": {"type": "string"},
            "category_id": {"type": "string"},
            "slug": {"type": "string", "maxLength": 255},
            "status": {"type": "string", "enum": ["draft", "in_review", "approved", "published", "archived"]},
            "audience": {"type": "string", "enum": ["agent", "manager", "admin", "all"]},
            "feature_refs": {"type": "array"},
        },
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "title": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_SIGNIFICANT,
    version="1.0.0",
    module="4.6",
)

# ---------------------------------------------------------------------------
# 4.7 Analytics & Reports
# ---------------------------------------------------------------------------
TOOL_REPORTS_RUN = ToolDefinition(
    name="tool.reports.run",
    description="Run a saved report and return its data (or execute ad-hoc query).",
    input_schema={"type": "object", "properties": {"report_id": {"type": "string"}, "filters": {"type": "object"}}, "required": ["report_id"]},
    output_schema={"type": "object", "properties": {"headers": {"type": "array"}, "rows": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

TOOL_DASHBOARDS_GET_SUMMARY = ToolDefinition(
    name="tool.dashboards.get_summary",
    description="Get KPI summary for the current user or a specified team.",
    input_schema={"type": "object", "properties": {"scope": {"type": "string", "enum": ["user", "team"]}, "team_id": {"type": "string"}}, "required": ["scope"]},
    output_schema={"type": "object", "properties": {"kpis": {"type": "object"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

TOOL_ANALYTICS_GET_METRIC = ToolDefinition(
    name="tool.analytics.get_metric",
    description="Return a single named metric value (e.g. win_rate, cac, ltv_cac_ratio).",
    input_schema={"type": "object", "properties": {"metric": {"type": "string"}, "period": {"type": "string", "enum": ["7d", "30d", "90d", "1y"]}}, "required": ["metric"]},
    output_schema={"type": "object", "properties": {"value": {"type": "number"}, "unit": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.7",
)

# ---------------------------------------------------------------------------
# 4.8 Contracts
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
    input_schema={"type": "object", "properties": {"contract_id": {"type": "string"}}, "required": ["contract_id"]},
    output_schema={"type": "object", "properties": {"status": {"type": "string"}, "e_signature_status": {"type": "string"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.8",
)

TOOL_CONTRACTS_GENERATE = ToolDefinition(
    name="tool.contracts.generate",
    description="Generate a contract from a template with variable substitution. Triggers downstream signature workflows.",
    input_schema={
        "type": "object",
        "properties": {
            "template_id": {"type": "string"},
            "account_id": {"type": "string"},
            "contact_id": {"type": "string"},
            "deal_id": {"type": "string"},
            "variables": {"type": "object"},
        },
        "required": ["template_id", "account_id"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_SIGNIFICANT,
    version="1.0.0",
    module="4.8",
)

TOOL_CONTRACTS_GET_SIGNING_STATUS = ToolDefinition(
    name="tool.contracts.get_signing_status",
    description="Get detailed e-signature workflow status for a contract.",
    input_schema={"type": "object", "properties": {"contract_id": {"type": "string"}}, "required": ["contract_id"]},
    output_schema={"type": "object", "properties": {"status": {"type": "string"}, "e_signature_status": {"type": "string"}, "signers": {"type": "array"}, "completed_at": {"type": ["string", "null"]}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.8",
)

# ---------------------------------------------------------------------------
# 4.9 Back-office (Finance, Assets, Procurement)
# ---------------------------------------------------------------------------
TOOL_INVOICES_SEARCH = ToolDefinition(
    name="tool.invoices.search",
    description="Search invoices linked to deals/accounts.",
    input_schema={"type": "object", "properties": {"account_id": {"type": "string"}, "status": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}, "required": []},
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

TOOL_ASSETS_SEARCH = ToolDefinition(
    name="tool.assets.search",
    description="Search company assets by status, type, or assigned employee.",
    input_schema={
        "type": "object",
        "properties": {
            "search": {"type": "string"},
            "status": {"type": "string", "enum": ["active", "maintenance", "retired", "lost"]},
            "type": {"type": "string"},
            "per_page": {"type": "integer", "default": 20},
        },
        "required": [],
    },
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.9",
)

# ---------------------------------------------------------------------------
# 4.10 Security
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

TOOL_SECURITY_GET_MY_RECENT_EVENTS = ToolDefinition(
    name="tool.security.get_my_recent_events",
    description="Return recent security/audit events for the current user (self-service only).",
    input_schema={"type": "object", "properties": {"limit": {"type": "integer", "default": 10}}},
    output_schema={"type": "object", "properties": {"events": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.10",
)

# ---------------------------------------------------------------------------
# 4.11 Integrations
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

TOOL_WEBHOOKS_GET_DELIVERY_LOG = ToolDefinition(
    name="tool.webhooks.get_delivery_log",
    description="Get recent webhook delivery attempts and their statuses.",
    input_schema={"type": "object", "properties": {"webhook_id": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}},
    output_schema={"type": "object", "properties": {"deliveries": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.11",
)

# ---------------------------------------------------------------------------
# 4.12 Calendar & Notifications
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
    input_schema={"type": "object", "properties": {"hours_ahead": {"type": "integer", "default": 48}}, "required": []},
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
# 4.15 Service & Support
# ---------------------------------------------------------------------------
TOOL_SERVICES_SEARCH = ToolDefinition(
    name="tool.services.search",
    description="Search the service catalog for available services/offerings.",
    input_schema={"type": "object", "properties": {"search": {"type": "string"}, "category_id": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}, "required": []},
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICES_GET = ToolDefinition(
    name="tool.services.get",
    description="Get full detail for a single service catalog item.",
    input_schema={"type": "object", "properties": {"id": {"type": "string"}}, "required": ["id"]},
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "name": {"type": "string"}, "description": {"type": "string"}, "category": {"type": "string"}, "form_fields": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICE_REQUESTS_SEARCH = ToolDefinition(
    name="tool.service_requests.search",
    description="Search service requests by status, contact, or catalog item.",
    input_schema={"type": "object", "properties": {"status": {"type": "string"}, "contact_id": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}, "required": []},
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICE_REQUESTS_CREATE = ToolDefinition(
    name="tool.service_requests.create",
    description="Create a new service request from a catalog item.",
    input_schema={
        "type": "object",
        "properties": {
            "catalog_item_id": {"type": "string"},
            "contact_id": {"type": "string"},
            "subject": {"type": "string", "maxLength": 255},
            "description": {"type": "string"},
        },
        "required": ["catalog_item_id", "contact_id", "subject"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICE_REQUESTS_GET_STATUS = ToolDefinition(
    name="tool.service_requests.get_status",
    description="Get the current status and workflow stage for a service request.",
    input_schema={"type": "object", "properties": {"service_request_id": {"type": "string"}}, "required": ["service_request_id"]},
    output_schema={"type": "object", "properties": {"status": {"type": "string"}, "stage": {"type": "string"}, "assigned_to": {"type": ["string", "null"]}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICE_REQUESTS_UPDATE_STATUS = ToolDefinition(
    name="tool.service_requests.update_status",
    description="Update the status of a service request.",
    input_schema={
        "type": "object",
        "properties": {
            "service_request_id": {"type": "string"},
            "status": {"type": "string", "enum": ["submitted", "in_review", "in_progress", "completed", "cancelled"]},
        },
        "required": ["service_request_id", "status"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_SERVICE_REQUESTS_ADD_DOCUMENT_REQUEST = ToolDefinition(
    name="tool.service_requests.add_document_request",
    description="Request a document from the contact as part of a service request.",
    input_schema={
        "type": "object",
        "properties": {
            "service_request_id": {"type": "string"},
            "document_type": {"type": "string"},
            "description": {"type": "string", "maxLength": 500},
        },
        "required": ["service_request_id", "document_type"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_SEARCH = ToolDefinition(
    name="tool.cases.search",
    description="Search cases by status, type, or contact.",
    input_schema={"type": "object", "properties": {"status": {"type": "string"}, "type": {"type": "string"}, "contact_id": {"type": "string"}, "per_page": {"type": "integer", "default": 20}}, "required": []},
    output_schema={"type": "object", "properties": {"data": {"type": "array"}, "total": {"type": "integer"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_CREATE = ToolDefinition(
    name="tool.cases.create",
    description="Create a new case (complaint, investigation, service case).",
    input_schema={
        "type": "object",
        "properties": {
            "contact_id": {"type": "string"},
            "type": {"type": "string", "enum": ["complaint", "investigation", "service_case"]},
            "subject": {"type": "string", "maxLength": 255},
            "description": {"type": "string"},
            "priority": {"type": "string", "enum": ["low", "medium", "high", "critical"], "default": "medium"},
        },
        "required": ["contact_id", "type", "subject"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "record_url": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_GET = ToolDefinition(
    name="tool.cases.get",
    description="Get full detail for a single case including notes and timeline.",
    input_schema={"type": "object", "properties": {"id": {"type": "string"}}, "required": ["id"]},
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "subject": {"type": "string"}, "status": {"type": "string"}, "type": {"type": "string"}, "contact": {"type": "object"}, "notes": {"type": "array"}}},
    tier=ConfirmationTier.READ,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_UPDATE_STATUS = ToolDefinition(
    name="tool.cases.update_status",
    description="Update the status of a case.",
    input_schema={
        "type": "object",
        "properties": {
            "case_id": {"type": "string"},
            "status": {"type": "string", "enum": ["open", "in_progress", "resolved", "closed", "escalated"]},
        },
        "required": ["case_id", "status"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_ADD_NOTE = ToolDefinition(
    name="tool.cases.add_note",
    description="Add an internal note to a case.",
    input_schema={
        "type": "object",
        "properties": {
            "case_id": {"type": "string"},
            "body": {"type": "string", "maxLength": 5000},
        },
        "required": ["case_id", "body"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "created_at": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_REVERSIBLE,
    version="1.0.0",
    module="4.15",
)

TOOL_CASES_REQUEST_SIGNOFF = ToolDefinition(
    name="tool.cases.request_signoff",
    description="Request sign-off on a case from a manager or stakeholder.",
    input_schema={
        "type": "object",
        "properties": {
            "case_id": {"type": "string"},
            "user_id": {"type": "string", "description": "User UUID to request sign-off from"},
            "notes": {"type": "string", "maxLength": 1000},
        },
        "required": ["case_id", "user_id"],
    },
    output_schema={"type": "object", "properties": {"id": {"type": "string"}, "status": {"type": "string"}}},
    tier=ConfirmationTier.WRITE_SIGNIFICANT,
    version="1.0.0",
    module="4.15",
)


# ---------------------------------------------------------------------------
# Tool aliases — one source of truth
# ---------------------------------------------------------------------------
TOOL_ALIASES: Dict[str, ToolDefinition] = {
    # 4.1 Contacts & Accounts
    "tool.contacts.search": TOOL_CONTACTS_SEARCH,
    "tool.contacts.get": TOOL_CONTACTS_GET,
    "tool.contacts.get_timeline": TOOL_CONTACTS_TIMELINE,
    "tool.contacts.create": TOOL_CONTACTS_CREATE,
    "tool.accounts.search": TOOL_ACCOUNTS_SEARCH,
    "tool.accounts.get": TOOL_ACCOUNTS_GET,
    # 4.2 Deals & Pipelines
    "tool.deals.search": TOOL_DEALS_SEARCH,
    "tool.deals.get": TOOL_DEALS_GET,
    "tool.deals.move_stage": TOOL_DEALS_MOVE_STAGE,
    "tool.deals.get_forecast": TOOL_DEALS_GET_FORECAST,
    "tool.deals.create": TOOL_DEALS_SEARCH,
    "tool.activities.create": TOOL_ACTIVITIES_CREATE,
    # 4.3 Interactions
    "tool.inbox.search": TOOL_INBOX_SEARCH,
    "tool.interactions.create_call_log": TOOL_INTERACTIONS_CREATE_CALL_LOG,
    "tool.contact_centre.get_stats": TOOL_CONTACT_CENTRE_GET_STATS,
    # 4.4 Campaigns
    "tool.campaigns.get_status": TOOL_CAMPAIGNS_GET_STATUS,
    "tool.campaigns.get_analytics": TOOL_CAMPAIGNS_GET_ANALYTICS,
    "tool.segments.preview_count": TOOL_SEGMENTS_PREVIEW_COUNT,
    "tool.segments.preview": TOOL_SEGMENTS_PREVIEW_COUNT,
    # 4.5 Loyalty & CX
    "tool.loyalty.get_balance": TOOL_LOYALTY_GET_BALANCE,
    "tool.surveys.get_results": TOOL_SURVEYS_GET_RESULTS,
    "tool.clv.get_score": TOOL_CLV_GET_SCORE,
    # 4.6 Support
    "tool.tickets.search": TOOL_TICKETS_SEARCH,
    "tool.tickets.create": TOOL_TICKETS_CREATE,
    "tool.tickets.update_status": TOOL_TICKETS_UPDATE_STATUS,
    "tool.kb.search": TOOL_KB_SEARCH,
    "tool.kb.create": TOOL_KB_CREATE,
    "tool.kb.update": TOOL_KB_UPDATE,
    # 4.7 Analytics
    "tool.reports.run": TOOL_REPORTS_RUN,
    "tool.dashboards.get_summary": TOOL_DASHBOARDS_GET_SUMMARY,
    "tool.analytics.get_metric": TOOL_ANALYTICS_GET_METRIC,
    # 4.8 Contracts
    "tool.contracts.search": TOOL_CONTRACTS_SEARCH,
    "tool.contracts.get_status": TOOL_CONTRACTS_GET_STATUS,
    "tool.contracts.generate": TOOL_CONTRACTS_GENERATE,
    "tool.contracts.get_signing_status": TOOL_CONTRACTS_GET_SIGNING_STATUS,
    # 4.9 Back-office
    "tool.invoices.search": TOOL_INVOICES_SEARCH,
    "tool.invoices.get_ledger": TOOL_INVOICES_GET_LEDGER,
    "tool.assets.search": TOOL_ASSETS_SEARCH,
    # 4.10 Security
    "tool.users.get_my_permissions": TOOL_USERS_GET_MY_PERMISSIONS,
    "tool.security.get_my_recent_events": TOOL_SECURITY_GET_MY_RECENT_EVENTS,
    # 4.11 Integrations
    "tool.integrations.get_status": TOOL_INTEGRATIONS_GET_STATUS,
    "tool.webhooks.get_delivery_log": TOOL_WEBHOOKS_GET_DELIVERY_LOG,
    # 4.12 Collaboration
    "tool.notifications.get_unread": TOOL_NOTIFICATIONS_GET_UNREAD,
    "tool.calendar.get_upcoming": TOOL_CALENDAR_GET_UPCOMING,
    "tool.comments.post": TOOL_COMMENTS_POST,
    # 4.15 Service & Support
    "tool.services.search": TOOL_SERVICES_SEARCH,
    "tool.services.get": TOOL_SERVICES_GET,
    "tool.service_requests.search": TOOL_SERVICE_REQUESTS_SEARCH,
    "tool.service_requests.create": TOOL_SERVICE_REQUESTS_CREATE,
    "tool.service_requests.get_status": TOOL_SERVICE_REQUESTS_GET_STATUS,
    "tool.service_requests.update_status": TOOL_SERVICE_REQUESTS_UPDATE_STATUS,
    "tool.service_requests.add_document_request": TOOL_SERVICE_REQUESTS_ADD_DOCUMENT_REQUEST,
    "tool.cases.search": TOOL_CASES_SEARCH,
    "tool.cases.create": TOOL_CASES_CREATE,
    "tool.cases.get": TOOL_CASES_GET,
    "tool.cases.update_status": TOOL_CASES_UPDATE_STATUS,
    "tool.cases.add_note": TOOL_CASES_ADD_NOTE,
    "tool.cases.request_signoff": TOOL_CASES_REQUEST_SIGNOFF,
}

# Sorted list for deterministic iteration / documentation
REGISTERED_TOOLS: List[ToolDefinition] = list({t.name: t for t in TOOL_ALIASES.values()}.values())


# ---------------------------------------------------------------------------
# RBAC role-tool mappings and self-service allowlist
# ---------------------------------------------------------------------------
ROLE_RULES: dict[str, set[str]] = {
    "admin": {t.name for t in REGISTERED_TOOLS},
    "manager": {t.name for t in REGISTERED_TOOLS if t.module not in ("4.10", "4.11")},
    "agent": {
        t.name for t in REGISTERED_TOOLS
        if t.module in ("4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.12", "4.15", "4.8", "4.9")
    },
    "contact": set(),
}

SELF_SERVICE_TOOLS: List[ToolDefinition] = [
    TOOL_TICKETS_SEARCH,
    TOOL_TICKETS_CREATE,
    TOOL_TICKETS_UPDATE_STATUS,
    TOOL_KB_SEARCH,
    TOOL_LOYALTY_GET_BALANCE,
    TOOL_CONTRACTS_SEARCH,
    TOOL_INVOICES_SEARCH,
    TOOL_CONTACTS_GET,
    TOOL_CONTACTS_SEARCH,
    TOOL_NOTIFICATIONS_GET_UNREAD,
]


def _filter_tools_for_user(user: dict[str, Any], tools: list[ToolDefinition]) -> list[ToolDefinition]:
    role = (user.get("role") or "agent").lower()
    if role == "contact":
        return SELF_SERVICE_TOOLS
    allowed_names = ROLE_RULES.get(role)
    if allowed_names is None:
        allowed_names = ROLE_RULES["agent"]
    return [t for t in tools if t.name in allowed_names]


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