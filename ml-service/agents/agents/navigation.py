"""
Navigation helper: maps CRM screens to URL patterns with pre-fill query params.

Mirrors the Inertia route structure so the assistant can produce deep links
like "overdue tickets for Acme Corp" that land directly on the right screen.
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
    "tickets.index": RouteManifest(
        route="/support/tickets",
        label="Support Tickets",
        supports_prefill=True,
        prefill_keys=("search", "status", "priority", "assigned_to", "contact_id", "account_id", "overdue"),
    ),
    "tickets.show": RouteManifest(
        route="/support/tickets/{id}",
        label="Ticket Detail",
        supports_prefill=False,
    ),
    "campaigns.index": RouteManifest(
        route="/campaigns",
        label="Campaigns",
        supports_prefill=True,
        prefill_keys=("status", "type", "segment_id"),
    ),
    "campaigns.builder": RouteManifest(
        route="/campaigns/builder",
        label="Campaign Builder",
        supports_prefill=False,
    ),
    "campaigns.analytics": RouteManifest(
        route="/campaigns/analytics",
        label="Campaign Analytics",
        supports_prefill=True,
        prefill_keys=("campaign_id",),
    ),
    "analytics.dashboard": RouteManifest(
        route="/analytics/dashboard",
        label="Analytics Dashboard",
        supports_prefill=False,
    ),
    "analytics.clv": RouteManifest(
        route="/analytics/clv",
        label="CLV Analytics",
        supports_prefill=True,
        prefill_keys=("contact_id", "period"),
    ),
    "analytics.forecast": RouteManifest(
        route="/analytics/forecast",
        label="Sales Forecast",
        supports_prefill=False,
    ),
    "contracts.index": RouteManifest(
        route="/contracts",
        label="Contracts",
        supports_prefill=True,
        prefill_keys=("status", "account_id", "contact_id", "type"),
    ),
    "contracts.generate": RouteManifest(
        route="/contracts/generate",
        label="Generate Contract",
        supports_prefill=True,
        prefill_keys=("template_id", "account_id", "contact_id", "deal_id"),
    ),
    "support.knowledge_base": RouteManifest(
        route="/support/knowledge-base",
        label="Knowledge Base",
        supports_prefill=True,
        prefill_keys=("search", "category_id"),
    ),
    "admin.pipelines": RouteManifest(
        route="/admin/pipelines",
        label="Pipeline Configuration",
        supports_prefill=False,
    ),
    "admin.sla": RouteManifest(
        route="/admin/sla-settings",
        label="SLA Settings",
        supports_prefill=False,
    ),
    "admin.integrations": RouteManifest(
        route="/admin/integrations",
        label="Integrations",
        supports_prefill=False,
    ),
    "admin.users": RouteManifest(
        route="/admin/users",
        label="User Management",
        supports_prefill=False,
    ),
    "invoices.index": RouteManifest(
        route="/finance/invoices",
        label="Invoices",
        supports_prefill=True,
        prefill_keys=("account_id", "status"),
    ),
    "settings.security": RouteManifest(
        route="/settings/security",
        label="Security Settings",
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
        ("contact", "contacts.show", {"id": entities.get("contact_id", "")}),
        ("contacts", "contacts.index", {"search": entities.get("search", ""), "account_id": entities.get("account_id", "")}),
        ("account", "accounts.show", {"id": entities.get("account_id", "")}),
        ("accounts", "accounts.index", {"search": entities.get("search", ""), "industry": entities.get("industry", "")}),
        ("deal", "deals.show", {"id": entities.get("deal_id", "")}),
        ("deals", "deals.board", {"pipeline_id": entities.get("pipeline_id", "")}),
        ("ticket", "tickets.show", {"id": entities.get("ticket_id", "")}),
        ("tickets", "tickets.index", {"status": entities.get("status", ""), "priority": entities.get("priority", ""), "account_id": entities.get("account_id", ""), "overdue": entities.get("overdue", "")}),
        ("campaigns", "campaigns.index", {"status": entities.get("status", "")}),
        ("campaign_builder", "campaigns.builder", {}),
        ("analytics", "analytics.dashboard", {}),
        ("clv", "analytics.clv", {"contact_id": entities.get("contact_id", "")}),
        ("forecast", "analytics.forecast", {}),
        ("contracts", "contracts.index", {"status": entities.get("status", "")}),
        ("contract_generate", "contracts.generate", {"template_id": entities.get("template_id", ""), "account_id": entities.get("account_id", "")}),
        ("invoices", "invoices.index", {"account_id": entities.get("account_id", "")}),
        ("knowledge", "support.knowledge_base", {"search": entities.get("search", "")}),
        ("pipeline_setup", "admin.pipelines", {}),
        ("sla_setup", "admin.sla", {}),
        ("integration_setup", "admin.integrations", {}),
        ("security", "settings.security", {}),
    ]

    lowered = intent.lower()
    for keyword, route_name, defaults in mapping:
        if keyword in lowered:
            merged = {k: v for k, v in defaults.items() if v}
            merged.update({k: v for k, v in entities.items() if v})
            return resolve(route_name, merged if merged else None)
    return None
