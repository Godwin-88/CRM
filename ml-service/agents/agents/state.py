import json
import logging
from dataclasses import dataclass, field, asdict
from enum import Enum
from typing import Any, Dict, List, Optional, Sequence, Union
from langchain_core.messages import BaseMessage
from langgraph.graph import StateGraph, END
from langgraph.graph.message import add_messages
import operator
from .utils import load_prompt

logger = logging.getLogger(__name__)


@dataclass(frozen=True)
class ToolResult:
    ok: bool
    status: int
    body: Dict[str, Any]
    latency_ms: int = 0
    error: Optional[str] = None


class IntentType(str, Enum):
    NAVIGATE = "navigate"
    EXPLAIN = "explain"
    EXECUTE = "execute"
    CLARIFY = "clarify"
    UNKNOWN = "unknown"


class ConfirmationTier(str, Enum):
    READ = "read"
    WRITE_REVERSIBLE = "write-reversible"
    WRITE_SIGNIFICANT = "write-significant"


class RouteConfidence(str, Enum):
    CONFIDENT = "confident"
    AMBIGUOUS = "ambiguous"
    UNCLEAR = "unclear"


@dataclass(frozen=True)
class ToolDefinition:
    name: str
    description: str
    input_schema: Dict[str, Any]
    output_schema: Dict[str, Any]
    tier: ConfirmationTier
    version: str = "1.0.0"
    module: Optional[str] = None


@dataclass
class ToolCall:
    tool: ToolDefinition
    arguments: Dict[str, Any] = field(default_factory=dict)
    confirmation_required: bool = False
    confirmed: Optional[bool] = None
    result: Optional[Dict[str, Any]] = None
    error: Optional[str] = None


@dataclass
class NavigationTarget:
    route: str
    label: str
    query: Dict[str, Any] = field(default_factory=dict)
    prefill: Dict[str, Any] = field(default_factory=dict)


class AssistantState(dict):
    messages: List[BaseMessage]
    context: Dict[str, Any]
    user: Dict[str, Any]
    validated_token: str
    session_id: str
    available_tools: List[ToolDefinition]
    intent: Optional[IntentType]
    intent_confidence: Optional[RouteConfidence]
    entities: Dict[str, Any]
    docs_hits: List[Dict[str, Any]]
    tools_to_call: List[ToolCall]
    current_tool_call: Optional[ToolCall]
    confirm_required: bool
    confirm_message: Optional[str]
    response: Optional[str]
    navigation: Optional[NavigationTarget]
    error: Optional[str]


def _default_state() -> AssistantState:
    return AssistantState(
        messages=[],
        context={},
        user={},
        validated_token="",
        session_id="",
        available_tools=[],
        intent=None,
        intent_confidence=None,
        entities={},
        docs_hits=[],
        tools_to_call=[],
        current_tool_call=None,
        confirm_required=False,
        confirm_message=None,
        response=None,
        navigation=None,
        error=None,
    )


STATE_SCHEMA_DOCS = "\n".join(
    f"- {k}: {v.__doc__.strip() if hasattr(v, '__doc__') and v.__doc__ else 'list[BaseMessage]'}"
    for k, v in _default_state().items()
)
