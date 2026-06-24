"""
Proactive suggestions (Feature 8).

Polls unread notifications from the Laravel API on session open,
ranks by urgency, and surfaces the most relevant one in the chat popup.
Respects user preferences and RBAC scoping.
"""

from __future__ import annotations

import logging
from typing import Any

from .laravel_client import call_tool

logger = logging.getLogger(__name__)

_PROACTIVE_TOOL = "tool.notifications.get_unread"


async def get_proactive_greeting(*, token: str, session_id: str, user: dict[str, Any]) -> str | None:
    role = (user.get("role") or "").lower()
    if role == "contact":
        return await _get_self_service_proactive(token=token, session_id=session_id, user=user)

    try:
        result = await call_tool(
            _PROACTIVE_TOOL,
            {},
            token=token,
            session_id=session_id,
        )
        if result.error or not result.ok:
            return None

        notifications = result.body.get("data", [])
        if not notifications:
            return None

        ranked = _rank_notifications(notifications)
        top = ranked[0]

        if top.get("read") or top.get("acknowledged"):
            return None

        n_type = (top.get("type") or "").lower()
        label = top.get("title") or top.get("subject") or "a notification"

        if "sla" in n_type or "breach" in n_type:
            return f"You have a ticket approaching its SLA breach — want me to open it?"
        if "mention" in n_type:
            return f"You were mentioned in **{label}** — want me to show you?"
        if "renewal" in n_type:
            return f"Contract renewal reminder: **{label}** — want me to open it?"
        return f"You have an unread notification: **{label}**."

    except Exception as exc:
        logger.warning("Proactive greeting fetch failed: %s", exc)
        return None


async def _get_self_service_proactive(*, token: str, session_id: str, user: dict[str, Any]) -> str | None:
    try:
        result = await call_tool(
            _PROACTIVE_TOOL,
            {},
            token=token,
            session_id=session_id,
        )
        if result.error or not result.ok:
            return None

        notifications = result.body.get("data", [])
        if not notifications:
            return None

        ranked = _rank_notifications(notifications)
        top = ranked[0]

        if top.get("read") or top.get("acknowledged"):
            return None

        n_type = (top.get("type") or "").lower()
        label = top.get("title") or top.get("subject") or "a notification"

        if "ticket" in n_type:
            return f"You have an update on your support ticket **{label}** — want me to show you?"
        if "contract" in n_type:
            return f"There's an update on your contract **{label}**."
        return None

    except Exception as exc:
        logger.warning("Self-service proactive fetch failed: %s", exc)
        return None


def _rank_notifications(notifications: list[dict[str, Any]]) -> list[dict[str, Any]]:
    priority = {"sla_breach": 0, "sla_breach_warning": 1, "mention": 2, "renewal": 3}

    def sort_key(n: dict[str, Any]) -> tuple[int, int]:
        n_type = (n.get("type") or "").lower()
        p = priority.get(n_type, 99)
        created = n.get("created_at") or n.get("timestamp") or ""
        return (p, created)

    return sorted(notifications, key=sort_key)
