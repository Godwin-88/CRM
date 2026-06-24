"""
Quality and evaluation (Feature 9).

Tracks conversation feedback, aggregates tool-call failures, and surfaces
assistant-specific metrics for maintainer review.
"""

from __future__ import annotations

import json
import logging
from dataclasses import dataclass, field
from enum import Enum
from typing import Any

from .laravel_client import call_rest

logger = logging.getLogger(__name__)

_FEEDBACK_ENDPOINT = "/assistant/internal/feedback"
_FAILURES_ENDPOINT = "/assistant/internal/tool-failures"


class FeedbackSentiment(str, Enum):
    POSITIVE = "positive"
    NEGATIVE = "negative"


@dataclass
class ConversationFeedback:
    session_id: str
    sentiment: FeedbackSentiment
    comment: str | None = None
    intent: str | None = None
    tools_used: list[str] = field(default_factory=list)
    transcript_snippet: str | None = None


async def submit_feedback(feedback: ConversationFeedback, *, token: str) -> bool:
    payload = {
        "session_id": feedback.session_id,
        "sentiment": feedback.sentiment.value,
        "comment": feedback.comment,
        "intent": feedback.intent,
        "tools_used": feedback.tools_used,
        "transcript_snippet": _mask_sensitive(feedback.transcript_snippet or ""),
    }
    try:
        result = await call_rest(
            "POST",
            _FEEDBACK_ENDPOINT,
            json=payload,
            service_api_key=token,
        )
        return bool(result.get("ok", result.get("success", False)))
    except Exception as exc:
        logger.warning("Feedback submission failed: %s", exc)
        return False


async def report_tool_failure(
    *,
    session_id: str,
    token: str,
    tool_name: str,
    error: str,
) -> None:
    try:
        await call_rest(
            "POST",
            _FAILURES_ENDPOINT,
            json={
                "session_id": session_id,
                "tool_name": tool_name,
                "error": error,
            },
            service_api_key=token,
        )
    except Exception as exc:
        logger.warning("Tool failure report failed: %s", exc)


def compute_session_metrics(session_state: dict[str, Any]) -> dict[str, Any]:
    tools_to_call = session_state.get("tools_to_call", [])
    errors = [tc for tc in tools_to_call if tc.get("error")]
    confirm_required = session_state.get("confirm_required", False)

    intents: list[str] = []
    tools_used: list[str] = []
    write_significant_count = 0
    write_significant_confirmed = 0

    for tc in tools_to_call:
        tool = tc.get("tool") if isinstance(tc, dict) else tc.tool
        if isinstance(tool, str):
            tools_used.append(tool)
        elif hasattr(tool, "name"):
            tools_used.append(tool.name)

    intent = session_state.get("intent")
    if hasattr(intent, "value"):
        intents.append(intent.value)
    elif intent:
        intents.append(str(intent))

    for tc in tools_to_call:
        tier = None
        tool = tc.get("tool") if isinstance(tc, dict) else tc.tool
        if hasattr(tool, "tier"):
            tier = tool.tier
        if tier and str(tier).endswith("write_significant") or str(tier) == "write-significant":
            write_significant_count += 1
            if tc.get("confirmed"):
                write_significant_confirmed += 1

    return {
        "intents": intents,
        "tools_used": tools_used,
        "error_count": len(errors),
        "write_significant_proposed": write_significant_count,
        "write_significant_confirmed": write_significant_confirmed,
        "write_significant_cancelled": write_significant_count - write_significant_confirmed,
        "low_confidence_flagged": bool(session_state.get("low_confidence_flagged", False)),
    }


_MASK_PATTERNS = [
    "password",
    "secret",
    "token",
    "api_key",
    "authorization",
    "credit_card",
    "ssn",
    "national_id",
]


def _mask_sensitive(text: str) -> str:
    lowered = text.lower()
    for pattern in _MASK_PATTERNS:
        if pattern in lowered:
            return "[redacted-sensitive-content]"
    return text
