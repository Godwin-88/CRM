"""
Orchestrator: a LangGraph StateGraph that routes a user message end-to-end.

Nodes (executed in this order when applicable):
  1. classify_intent  – categorical routing (navigate / explain / execute / clarify)
  2. retrieve_docs     – fetch KB articles via Laravel tool.kb.search
  3. resolve_tools     – select tool calls from registry
  4. tool_execute      – call Laravel agent tool API
  5. confirm_gate      – inject confirmation card payload for write tiers
  6. compose_response  – synthesize user-facing reply + navigation links
"""

from __future__ import annotations

import httpx
import json
import logging
import os
from typing import Any

from langchain_core.prompts import ChatPromptTemplate
from langchain_core.messages import SystemMessage, HumanMessage, AIMessage, BaseMessage
from langgraph.graph import StateGraph, END
from langgraph.graph.state import CompiledStateGraph

from .state import (
    AssistantState,
    ToolCall,
    ConfirmationTier,
    IntentType,
    RouteConfidence,
    NavigationTarget,
)
from .tool_registry import REGISTERED_TOOLS
from .session_manager import SessionManager
from .navigation import pick_best_route
from . import utils as agent_utils

logger = logging.getLogger(__name__)

_LARAVEL_API_URL = os.getenv("LARAVEL_API_URL", "http://app/api/v1")

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
# Prompts
# ---------------------------------------------------------------------------
SYSTEM_PROMPT = """\
You are the AI CRM Assistant for an Enterprise CRM. You help users navigate the platform, \
answer questions grounded in the internal docs, and perform permitted CRM actions on their behalf.

## Your job

For each user message, classify the intent into one of: navigate | explain | execute | clarify.

### navigate
  - The user wants to go to a specific part of the CRM.
  - Respond with a clean `navigation` block pointing to the correct screen (deep-linked \
    with query params when you have enough entities).

### explain
  - The user is asking "what is X", "how do I Y", "where is Z".
  - Use the retrieved document chunks (provided in context['docs_hits']) to answer inline.
  - Include 2-3 sentence explanation and, where helpful, a specific navigation link.
  - If no docs were retrieved, say so honestly: "I don't have detailed documentation on that yet, \
    but based on what I know it relates to [module]. Here's the closest screen."
  - DO NOT guess at undocumented behaviors.

### execute
  - The user explicitly wants you to DO something (create X, move Y, update status).
  - Produce a tool_call with the correct CRM tool name and arguments.
  - Read-only tools require no confirmation; write-reversible need inline confirmation; \
    write-significant need an explicit summary-of-consequences confirmation card.

### clarify
  - The request is ambiguous, you lack enough entity info, or multiple tools match.
  - Ask ONE short clarifying question with 2-3 concrete options (buttons).
  - Do NOT guess; better to ask than to act.

## Constraints
- Every record reference must become a clickable navigation target, never plain text directions.
- User RBAC is enforced server-side; never pretend you can do more than they can via the UI.
- Cite documentation sections when explaining.
- Always respond in the user's language (English unless they have switched).
"""

CLASSIFIER_PROMPT = ChatPromptTemplate.from_messages(
    [
        ("system", "Classify the user message into one intent: navigate | explain | execute | clarify.\n"
                   "Also return confidence: confident | ambiguous | unclear.\n"
                   "Extract any CRM entities you detect (contact_id, deal_id, ticket_id, account_id, etc.).\n"
                   "Return JSON: {intent, confidence, entities, reason}"),
        ("human", "{message}"),
    ]
)

TOOL_SELECTOR_PROMPT = ChatPromptTemplate.from_messages(
    [
        ("system",
         "Given a user message and available tools, select ONE most appropriate tool to call. "
         "Return JSON: {\"tool_name\": \"<selected tool name>\", \"reason\": \"<why this tool>\"}"),
        ("human", "Message: {message}\nAvailable tools: {tool_names}\nEntities: {entities}"),
    ]
)

ARGUMENTS_FILLER_PROMPT = ChatPromptTemplate.from_messages(
    [
        ("system",
         "Fill the tool arguments from the schema using entity values and message context. "
         "Only include values that can be reasonably inferred. Return JSON: {\"arguments\": {...}}"),
        ("human", "Tool: {tool_name}\nSchema properties: {schema}\nEntities: {entities}\nMessage: {message}"),
    ]
)


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def _json_block(content: str) -> dict[str, Any] | None:
    try:
        start = content.index("{")
        end = content.rindex("}") + 1
        return json.loads(content[start:end])
    except (ValueError, json.JSONDecodeError):
        return None


def _model():
    return agent_utils.get_model()


async def _flag_low_confidence(*, session_id: str, user_query: str, resolved_intent: str, confidence_score: str) -> None:
    """Persist a low-confidence route flag to the Laravel API."""
    url = f"{_LARAVEL_API_URL}/assistant/internal/low-confidence"
    try:
        async with httpx.AsyncClient(timeout=httpx.Timeout(connect=5.0, write=5.0, pool=2.0)) as client:
            await client.post(
                url,
                json={
                    "session_id": session_id,
                    "user_query": user_query,
                    "resolved_intent": resolved_intent,
                    "confidence_score": confidence_score,
                },
                headers={"Accept": "application/json"},
            )
    except Exception as exc:
        logger.warning("Failed to flag low-confidence route: %s", exc)


# ---------------------------------------------------------------------------
# LLM-based tool selection (async)
# ---------------------------------------------------------------------------
async def _select_tools_with_llm(message: str, entities: dict[str, Any], tools: list) -> list[ToolCall]:
    """Use LLM to pick one tool from the allowed list. Returns list of ToolCall objects."""
    if not tools:
        return []

    try:
        tool_names = ", ".join(t.name for t in tools)
        chain = TOOL_SELECTOR_PROMPT | _model()
        raw = await chain.ainvoke({
            "message": message,
            "tool_names": tool_names,
            "entities": json.dumps(entities),
        })
        content = raw.content if hasattr(raw, "content") else str(raw)
        parsed = _json_block(content) or {}
        tool_name = parsed.get("tool_name")
        if not tool_name:
            logger.warning("LLM tool selector returned no tool_name, falling back to keyword match")
            return _match_tools(message, entities, tools)

        tool = next((t for t in tools if t.name == tool_name), None)
        if not tool:
            logger.warning("LLM selected unknown tool '%s', falling back to keyword match", tool_name)
            return _match_tools(message, entities, tools)

        return [ToolCall(tool=tool)]
    except Exception as exc:
        logger.warning("LLM tool selector failed: %s, using fallback", exc)
        return _match_tools(message, entities, tools)


def _fill_arguments_with_llm(tc: ToolCall, entities: dict[str, Any], message: str) -> dict[str, Any]:
    """Use LLM to populate tool args from the schema. Falls back to simple entity mapping."""
    try:
        schema_props = json.dumps(tc.tool.input_schema.get("properties", {}))
        chain = ARGUMENTS_FILLER_PROMPT | _model()
        raw = chain.invoke({
            "tool_name": tc.tool.name,
            "schema": schema_props,
            "entities": json.dumps(entities),
            "message": message,
        })
        content = raw.content if hasattr(raw, "content") else str(raw)
        parsed = _json_block(content) or {}
        args = parsed.get("arguments", {})
        return args if isinstance(args, dict) else _build_arguments(tc, entities)
    except Exception as exc:
        logger.warning("LLM argument filler failed: %s, using fallback", exc)
        return _build_arguments(tc, entities)


# ---------------------------------------------------------------------------
# Nodes
# ---------------------------------------------------------------------------
async def classify_intent(state: AssistantState) -> dict[str, Any]:
    message: str = state.get("context", {}).get("message", "")
    if not message:
        return {"intent": IntentType.UNKNOWN, "intent_confidence": RouteConfidence.UNCLEAR, "entities": {}, "low_confidence_flagged": True}

    chain = CLASSIFIER_PROMPT | _model()
    raw = await chain.ainvoke({"message": message})
    content = raw.content if hasattr(raw, "content") else str(raw)
    parsed = _json_block(content) or {}

    intent_str = (parsed.get("intent") or "unknown").lower()
    try:
        intent = IntentType(intent_str)
    except ValueError:
        intent = IntentType.UNKNOWN

    conf_str = (parsed.get("confidence") or "unclear").lower()
    try:
        confidence = RouteConfidence(conf_str)
    except ValueError:
        confidence = RouteConfidence.UNCLEAR

    entities = parsed.get("entities") or {}

    if confidence == RouteConfidence.AMBIGUOUS:
        intent = IntentType.CLARIFY

    low_confidence_flagged = confidence in (RouteConfidence.UNCLEAR, RouteConfidence.AMBIGUOUS)
    if low_confidence_flagged:
        logger.info(
            "Low-confidence route flagged: intent=%s confidence=%s query=%s",
            intent.value,
            confidence.value,
            message,
        )

    return {
        "intent": intent,
        "intent_confidence": confidence,
        "entities": entities,
        "low_confidence_flagged": low_confidence_flagged,
    }


async def retrieve_docs(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    if intent not in (IntentType.EXPLAIN, IntentType.EXECUTE, IntentType.CLARIFY):
        return {"docs_hits": []}

    from .laravel_client import call_tool

    message = state.get("context", {}).get("message", "")
    tool = state.get("available_tools", [None])[0] if state.get("available_tools") else None

    kb_search_tool = next((t for t in REGISTERED_TOOLS if t.name == "tool.kb.search"), None)
    if not kb_search_tool:
        return {"docs_hits": []}

    try:
        result = await call_tool(
            "kb.search",
            {"query": message, "per_page": 5},
            token=state.get("validated_token", ""),
            session_id=state.get("session_id", ""),
        )
        hits = result.body.get("results", result.body.get("data", []))
        if not isinstance(hits, list):
            hits = []
        return {"docs_hits": hits[:3]}
    except Exception as exc:
        logger.warning("KB search failed during retrieval: %s", exc)
        return {"docs_hits": []}


async def resolve_tools(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    message: str = state.get("context", {}).get("message", "")
    entities: dict[str, Any] = state.get("entities", {})

    if intent == IntentType.NAVIGATE:
        return {"tools_to_call": [], "navigation": pick_best_route(message, entities)}

    if intent in (IntentType.EXPLAIN, IntentType.CLARIFY):
        return {"tools_to_call": [], "current_tool_call": None, "confirm_required": False}

    tools = state.get("available_tools", [])
    if not tools:
        return {"tools_to_call": [], "current_tool_call": None, "confirm_required": False, "intent": IntentType.CLARIFY, "low_confidence_flagged": True}

    selected = await _select_tools_with_llm(message, entities, tools)
    if not selected:
        return {"tools_to_call": [], "current_tool_call": None, "intent": IntentType.CLARIFY, "low_confidence_flagged": True}

    tc = selected[0]
    tc.arguments = _fill_arguments_with_llm(tc, entities, message)

    if tc.tool.tier in (ConfirmationTier.WRITE_REVERSIBLE, ConfirmationTier.WRITE_SIGNIFICANT):
        return {
            "tools_to_call": [tc],
            "current_tool_call": tc,
            "confirm_required": True,
            "confirm_message": _confirm_message(tc),
        }

    return {
        "tools_to_call": [tc],
        "current_tool_call": tc,
        "confirm_required": False,
        "confirm_message": None,
    }


def _match_tools(message: str, entities: dict[str, Any], tools: list) -> list[ToolCall]:
    """Very simple keyword-to-tool matcher. Used as fallback when LLM selector fails."""
    lowered = message.lower()
    matches: list[ToolCall] = []

    def candidate(name_hint: str) -> Any | None:
        for t in tools:
            if name_hint in t.name:
                return t
        return None

    if any(k in lowered for k in ["contact", "people", "person"]):
        if any(k in lowered for k in ["create", "new", "add"]):
            matches.append(ToolCall(tool=candidate("contacts.create")))
        else:
            matches.append(ToolCall(tool=candidate("contacts.search")))

    if any(k in lowered for k in ["deal", "opportunity", "pipeline"]):
        if any(k in lowered for k in ["move", "stage", "progress"]):
            matches.append(ToolCall(tool=candidate("deals.move_stage")))
        else:
            matches.append(ToolCall(tool=candidate("deals.search")))

    if any(k in lowered for k in ["ticket", "support", "issue"]):
        if any(k in lowered for k in ["create", "open", "new"]):
            matches.append(ToolCall(tool=candidate("tickets.create")))
        else:
            matches.append(ToolCall(tool=candidate("tickets.search")))

    if any(k in lowered for k in ["task", "follow up", "followup", "todo", "to-do"]):
        matches.append(ToolCall(tool=candidate("activities.create")))

    if any(k in lowered for k in ["campaign", "email send", "blast"]):
        matches.append(ToolCall(tool=candidate("campaigns.get_status")))

    if any(k in lowered for k in ["contract", "agreement", "e-sign", "esign"]):
        matches.append(ToolCall(tool=candidate("contracts.get_status")))

    if any(k in lowered for k in ["invoice", "billing", "payment"]):
        matches.append(ToolCall(tool=candidate("invoices.search")))

    if any(k in lowered for k in ["who am i", "my permissions", "what can i do"]):
        matches.append(ToolCall(tool=candidate("users.get_my_permissions")))

    if any(k in lowered for k in ["notification", "unread", "alert"]):
        matches.append(ToolCall(tool=candidate("notifications.get_unread")))

    if any(k in lowered for k in ["calendar", "meeting", "upcoming"]):
        matches.append(ToolCall(tool=candidate("calendar.getcoming")))

    if any(k in lowered for k in ["help", "what is", "how do", "where is", "docs"]) and entities.get("document_hit"):
        return []

    return matches


def _build_arguments(tc: ToolCall, entities: dict[str, Any]) -> dict[str, Any]:
    schema = tc.tool.input_schema.get("properties", {})
    args: dict[str, Any] = {}
    for key in schema:
        if key in ("type",):
            continue
        if key in entities:
            args[key] = entities[key]
    return args


def _confirm_message(tc: ToolCall) -> str:
    action = "perform this action"
    if tc.tool.name.endswith("move_stage"):
        deal_id = tc.arguments.get("deal_id", "this deal")
        action = f"move deal **{deal_id}** to stage **{tc.arguments.get('stage', '?')}**"
    elif tc.tool.name.endswith("tickets.create"):
        action = f"create a ticket with subject **{tc.arguments.get('subject', '?')}**"
    elif tc.tool.name.endswith("activities.create"):
        action = f"create a follow-up task **{tc.arguments.get('subject', '?')}**"
    elif tc.tool.name.endswith("comments.post"):
        action = f"post a comment on **{tc.arguments.get('entity_type', 'record')}**"

    if tc.tool.tier == ConfirmationTier.WRITE_SIGNIFICANT:
        return f"This is a significant action: {action}. It may trigger downstream automations. Confirm?"
    return f"{action}. Confirm?"


# ---------------------------------------------------------------------------
# Tool execution node
# ---------------------------------------------------------------------------
async def tool_execute(state: AssistantState) -> dict[str, Any]:
    tool_calls: list[ToolCall] = state.get("tools_to_call", [])
    if not tool_calls:
        return {"tools_to_call": []}

    tc = tool_calls[0]
    try:
        from .laravel_client import call_tool

        raw = await call_tool(
            tc.tool.name,
            tc.arguments,
            token=state.get("validated_token", ""),
            session_id=state.get("session_id", ""),
        )
        if raw.error:
            tc.error = raw.error
            tc.result = None
        else:
            tc.result = raw.body
            tc.confirmed = True
    except Exception as exc:
        logger.exception("Tool execution failed for %s", tc.tool.name)
        tc.error = str(exc)
        tc.result = None

    return {
        "current_tool_call": tc,
        "tools_to_call": tool_calls,
    }


# ---------------------------------------------------------------------------
# Confirmation gate and handler
# ---------------------------------------------------------------------------
async def confirm_gate(state: AssistantState) -> dict[str, Any]:
    confirmed_action = state.get("context", {}).get("confirmed_tool")
    confirmed_args = state.get("context", {}).get("confirmed_arguments")

    if confirmed_action and confirmed_args:
        return {
            "confirm_required": False,
            "confirmed_tool": confirmed_action,
            "confirmed_arguments": confirmed_args,
            "current_tool_call": state.get("current_tool_call"),
        }

    return {
        "confirm_required": state.get("confirm_required", False),
        "confirm_message": state.get("confirm_message"),
        "current_tool_call": state.get("current_tool_call"),
    }


async def handle_confirmed_action(state: AssistantState) -> dict[str, Any]:
    """Replay tool_execute when confirmation arrives."""
    confirmed_action = state.get("context", {}).get("confirmed_tool")
    confirmed_args = state.get("context", {}).get("confirmed_arguments")

    if confirmed_action and confirmed_args:
        tool = next((t for t in REGISTERED_TOOLS if t.name == confirmed_action), None)
        if tool:
            tc = ToolCall(tool=tool, arguments=confirmed_args)
            try:
                from .laravel_client import call_tool
                raw = await call_tool(
                    confirmed_action,
                    confirmed_args,
                    token=state.get("validated_token", ""),
                    session_id=state.get("session_id", ""),
                )
                if raw.error:
                    tc.error = raw.error
                    tc.result = None
                else:
                    tc.result = raw.body
                    tc.confirmed = True
            except Exception as exc:
                logger.exception("Confirmed tool execution failed for %s", confirmed_action)
                tc.error = str(exc)
                tc.result = None
            return {
                "current_tool_call": tc,
                "tools_to_call": [tc],
            }

    return {"tools_to_call": []}


# ---------------------------------------------------------------------------
# Response composer
# ---------------------------------------------------------------------------
async def compose_response(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    docs = state.get("docs_hits", [])
    nav: NavigationTarget | None = state.get("navigation")
    current_tool_call: ToolCall | None = state.get("current_tool_call")
    error = state.get("error")
    message: str = state.get("context", {}).get("message", "")

    if error:
        return {"response": f"Something went wrong: {error}"}

    if intent == IntentType.CLARIFY:
        entities = state.get("entities", {})
        return {
            "response": (
                "I want to make sure I understand correctly. Could you tell me which of these "
                "you're referring to, or give me a bit more detail?"
            ),
            "intent": IntentType.CLARIFY,
        }

    if intent == IntentType.EXPLAIN:
        tx = _explain_block(message, docs, state.get("entities", {}))
    elif intent == IntentType.NAVIGATE:
        tx = _navigate_block(nav or _best_fallback_route(message))
    elif intent == IntentType.EXECUTE:
        if current_tool_call and current_tool_call.error:
            tx = f"I tried to run that action but hit an issue: {current_tool_call.error}"
        elif current_tool_call and current_tool_call.result:
            tx = _executed_block(current_tool_call)
        else:
            tx = "I'm not sure which tool to use for that. Could you rephrase, or confirm what you want?"
    else:
        tx = "I'm here to help with contacts, deals, tickets, campaigns, and more. What would you like to do?"

    return {"response": tx}


def _explain_block(query: str, docs: list, entities: dict) -> str:
    answer = f"Here's what I found about **{query or 'that'}**:\n\n"
    if docs:
        for i, doc in enumerate(docs[:3], 1):
            title = doc.get("title") or doc.get("subject") or f"Result {i}"
            snippet = (doc.get("body") or doc.get("content") or doc.get("excerpt") or "")[:180]
            answer += f"**{title}**\n> {snippet}\n\n"
    else:
        answer += "I couldn't find a specific documentation match for that. "
        answer += "It looks like it's related to the CRM's core modules. "
        answer += "Trying [best-match screen-based routing]() may help."

    if entities:
        answer += "\n\nWant me to navigate to the relevant screen with any filters applied?"
    return answer


def _navigate_block(nav: NavigationTarget | None) -> str:
    if not nav:
        return "I'm not sure where to send you. Try describing the screen or record you want to open."
    return f"Opening **{nav.label}** for you now. I've pre-filled the filters where I could.\n\n navigation: {nav.route}"


def _executed_block(tc: ToolCall) -> str:
    if tc.tool.tier.value.startswith("write"):
        record_url = (tc.result or {}).get("record_url", "")
        success = "Done. Here's the affected record: " + record_url if record_url else "Done."
        return success
    return f"Here's what I found: {json.dumps(tc.result)[:500]}"


def _best_fallback_route(query: str) -> NavigationTarget | None:
    return pick_best_route(query, {})


# ---------------------------------------------------------------------------
# Graph assembly
# ---------------------------------------------------------------------------
def build_graph() -> CompiledStateGraph:
    g = StateGraph(AssistantState)

    g.add_node("classify_intent", classify_intent)
    g.add_node("retrieve_docs", retrieve_docs)
    g.add_node("resolve_tools", resolve_tools)
    g.add_node("tool_execute", tool_execute)
    g.add_node("confirm_gate", confirm_gate)
    g.add_node("handle_confirmed_action", handle_confirmed_action)
    g.add_node("compose_response", compose_response)

    g.set_entry_point("classify_intent")
    g.add_edge("classify_intent", "retrieve_docs")

    g.add_conditional_edges(
        "retrieve_docs",
        lambda state: (
            "resolve_tools"
            if state.get("intent") in (IntentType.EXECUTE, IntentType.CLARIFY)
            else "compose_response"
        ),
        {
            "resolve_tools": "resolve_tools",
            "compose_response": "compose_response",
        },
    )

    g.add_conditional_edges(
        "resolve_tools",
        lambda state: "compose_response" if not state.get("tools_to_call") else "tool_execute",
        {
            "tool_execute": "tool_execute",
            "compose_response": "compose_response",
        },
    )

    g.add_conditional_edges(
        "tool_execute",
        lambda state: (
            "confirm_gate"
            if len(state.get("tools_to_call", [])) >= 1 and state.get("confirm_required", False)
            else "compose_response"
            if len(state.get("tools_to_call", [])) <= 1
            else "tool_execute"
        ),
        {
            "tool_execute": "tool_execute",
            "confirm_gate": "confirm_gate",
            "compose_response": "compose_response",
        },
    )

    g.add_conditional_edges(
        "confirm_gate",
        lambda state: "handle_confirmed_action" if state.get("context", {}).get("confirmed_tool") else "compose_response",
        {
            "handle_confirmed_action": "handle_confirmed_action",
            "compose_response": "compose_response",
        },
    )

    g.add_edge("handle_confirmed_action", "compose_response")
    g.add_edge("compose_response", END)

    return g.compile()


orchestrator_graph = build_graph()


async def run_orchestrator(*, user: dict, message: str, token: str, session_id: str, context: dict) -> dict[str, Any]:
    session = SessionManager()
    state = await session.load(session_id)

    state["user"] = user
    state["validated_token"] = token
    state["session_id"] = session_id
    state["context"] = {**state.get("context", {}), **context, "message": message}

    state["available_tools"] = list(REGISTERED_TOOLS)

    final: AssistantState | dict = await orchestrator_graph.ainvoke(state)

    if isinstance(final, dict):
        final.setdefault("session_id", session_id)

    intent_confidence = final.get("intent_confidence")
    if isinstance(intent_confidence, RouteConfidence):
        confidence_str = intent_confidence.value
    else:
        confidence_str = str(intent_confidence)

    if confidence_str in ("unclear", "ambiguous"):
        await _flag_low_confidence(
            session_id=session_id,
            user_query=message,
            resolved_intent=str(final.get("intent", "unknown")),
            confidence_score=confidence_str,
        )

    await session.save(final if isinstance(final, dict) else dict(final))

    return {
        "response": final.get("response"),
        "intent": final.get("intent"),
        "intent_confidence": final.get("intent_confidence"),
        "tools_to_call": [
            {
                "tool": tc.tool.name,
                "tier": tc.tool.tier.value,
                "arguments": tc.arguments,
                "required_confirmation": bool(final.get("confirm_required")),
                "confirmation_message": final.get("confirm_message"),
                "result": tc.result,
                "error": tc.error,
            }
            for tc in final.get("tools_to_call", [])
        ],
        "navigation": (
            {
                "route": final.get("navigation").route,
                "label": final.get("navigation").label,
                "query": final.get("navigation").query,
            }
            if final.get("navigation")
            else None
        ),
        "session_id": session_id,
        "confidence": final.get("intent_confidence"),
    }