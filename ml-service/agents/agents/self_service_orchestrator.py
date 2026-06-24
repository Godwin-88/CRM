"""
Self-service customer-facing orchestrator (Phase 5 Feature 7).

Constrained twin of the full internal assistant:
- Tool allowlist: read-only on own records + tickets/kb/loyalty
- No write-significant or destructive tools
- Different system prompt with customer-friendly language
"""

from __future__ import annotations

import json
import logging
from typing import Any

from langchain_core.prompts import ChatPromptTemplate
from langchain_core.messages import SystemMessage, HumanMessage
from langgraph.graph import StateGraph, END

from .state import AssistantState, ToolCall, ConfirmationTier, IntentType
from .tool_registry import REGISTERED_TOOLS, get
from .session_manager import SessionManager
from .navigation import pick_best_route
from . import utils as agent_utils

logger = logging.getLogger(__name__)

SELF_SERVICE_TOOLS = [
    get("tool.tickets.search"),
    get("tool.tickets.create"),
    get("tool.tickets.update_status"),
    get("tool.kb.search"),
    get("tool.loyalty.get_balance"),
    get("tool.contracts.search"),
    get("tool.invoices.search"),
    get("tool.contacts.get"),
    get("tool.contacts.search"),
    get("tool.notifications.get_unread"),
    get("tool.users.get_my_permissions"),
]
SELF_SERVICE_TOOLS = [t for t in SELF_SERVICE_TOOLS if t is not None]

SYSTEM_PROMPT = """\
You are the Self-Service Assistant for our customers. You help customers track their own tickets, \
find documentation, check loyalty balances, and review their invoices or contracts. You do not have \
access to internal analytics, other customers' data, or administrative settings.

## What I can do
- Show your support tickets and create new ones
- Search the knowledge base / FAQ
- Check your loyalty points balance
- Review your contracts and invoices

## Rules
- Be polite and concise.
- If something is outside your scope, say: "I'm a self-service assistant; for that you may need to \
contact support or your account manager."
- Never pretend to see other users' data.
- Always include relevant links when you have a record URL.
"""

CLASSIFIER_PROMPT = ChatPromptTemplate.from_messages(
    [
        ("system", "Classify the customer message into one intent: navigate | explain | execute | clarify.\n"
                   "Return JSON: {intent, confidence, entities, reason}"),
        ("human", "{message}"),
    ]
)


def _json_block(content: str) -> dict[str, Any] | None:
    try:
        start = content.index("{")
        end = content.rindex("}") + 1
        return json.loads(content[start:end])
    except (ValueError, json.JSONDecodeError):
        return None


def _model():
    return agent_utils.get_model()


async def classify_intent(state: AssistantState) -> dict[str, Any]:
    message = state.get("context", {}).get("message", "")
    if not message:
        return {"intent": IntentType.UNKNOWN, "intent_confidence": "unclear", "entities": {}}

    chain = CLASSIFIER_PROMPT | _model()
    raw = await chain.ainvoke({"message": message})
    content = raw.content if hasattr(raw, "content") else str(raw)
    parsed = _json_block(content) or {}

    intent_str = (parsed.get("intent") or "unknown").lower()
    try:
        intent = IntentType(intent_str)
    except ValueError:
        intent = IntentType.UNKNOWN

    return {
        "intent": intent,
        "intent_confidence": parsed.get("confidence", "unclear"),
        "entities": parsed.get("entities") or {},
    }


async def retrieve_docs(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    if intent not in (IntentType.EXPLAIN, IntentType.EXECUTE, IntentType.CLARIFY):
        return {"docs_hits": []}

    message = state.get("context", {}).get("message", "")
    kb = next((t for t in SELF_SERVICE_TOOLS if t.name == "tool.kb.search"), None)
    if not kb:
        return {"docs_hits": []}

    try:
        from .laravel_client import call_tool
        result = await call_tool(
            "kb.search",
            {"query": message, "per_page": 5},
            token=state.get("validated_token", ""),
            session_id=state.get("session_id", ""),
        )
        hits = result.body.get("results", result.body.get("data", []))
        return {"docs_hits": hits[:3] if isinstance(hits, list) else []}
    except Exception as exc:
        logger.warning("KB search failed: %s", exc)
        return {"docs_hits": []}


async def resolve_tools(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    message = state.get("context", {}).get("message", "")
    entities = state.get("entities", {})

    if intent == IntentType.NAVIGATE:
        return {"tools_to_call": [], "navigation": pick_best_route(message, entities)}

    if intent in (IntentType.EXPLAIN, IntentType.CLARIFY):
        return {"tools_to_call": [], "current_tool_call": None, "confirm_required": False}

    tools = state.get("available_tools", SELF_SERVICE_TOOLS)
    if not tools:
        return {"tools_to_call": [], "current_tool_call": None, "confirm_required": False}

    selected = await _select_tools_with_llm(message, entities, tools)
    if not selected:
        return {"tools_to_call": [], "current_tool_call": None, "intent": IntentType.CLARIFY}

    tc = selected[0]
    tc.arguments = _fill_arguments_with_llm(tc, entities, message)

    if tc.tool.tier in (ConfirmationTier.WRITE_REVERSIBLE, ConfirmationTier.WRITE_SIGNIFICANT):
        return {
            "tools_to_call": [tc],
            "current_tool_call": tc,
            "confirm_required": True,
            "confirm_message": "This will make a change to your account. Confirm?",
        }

    return {
        "tools_to_call": [tc],
        "current_tool_call": tc,
        "confirm_required": False,
        "confirm_message": None,
    }


async def _select_tools_with_llm(message: str, entities: dict[str, Any], tools: list) -> list[ToolCall] | None:
    try:
        prompt = ChatPromptTemplate.from_messages(
            [
                ("system", "Choose the best tool for this customer request from the list.\nTools:\n{tools}\nReturn JSON: {{tool_name, reason}}."),
                ("human", "{message}\nEntities: {entities}"),
            ]
        )
        chain = prompt | _model()
        raw = await chain.ainvoke({
            "message": message,
            "entities": json.dumps(entities),
            "tools": "\n".join(f"- {t.name}: {t.description}" for t in tools),
        })
        content = raw.content if hasattr(raw, "content") else str(raw)
        parsed = _json_block(content) or {}
        tool_name = parsed.get("tool_name")
        if not tool_name:
            return None
        match = next((t for t in tools if t.name == tool_name), None)
        return [ToolCall(tool=match)] if match else None
    except Exception as exc:
        logger.warning("LLM tool selection failed: %s", exc)
        return None


async def _fill_arguments_with_llm(tc: ToolCall, entities: dict[str, Any], message: str) -> dict[str, Any]:
    try:
        schema = tc.tool.input_schema.get("properties", {})
        allowed = [k for k in schema if k != "type"]
        prompt = ChatPromptTemplate.from_messages(
            [
                ("system", "Fill arguments for tool '{tool_name}'. Allowed keys: {keys}. Return JSON: {{arguments: {{...}}}}."),
                ("human", "Message: {message}\nEntities: {entities}"),
            ]
        )
        chain = prompt | _model()
        raw = await chain.ainvoke({
            "tool_name": tc.tool.name,
            "keys": ", ".join(allowed),
            "message": message,
            "entities": json.dumps(entities),
        })
        content = raw.content if hasattr(raw, "content") else str(raw)
        parsed = _json_block(content) or {}
        args = parsed.get("arguments") or {}
        return {k: v for k, v in args.items() if k in allowed}
    except Exception as exc:
        logger.debug("LLM arg fill failed: %s", exc)
        return {k: v for k, v in entities.items() if k in tc.tool.input_schema.get("properties", {})}


async def tool_execute(state: AssistantState) -> dict[str, Any]:
    tool_calls = state.get("tools_to_call", [])
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

    return {"current_tool_call": tc, "tools_to_call": tool_calls}


async def confirm_gate(state: AssistantState) -> dict[str, Any]:
    return {
        "confirm_required": state.get("confirm_required", False),
        "confirm_message": state.get("confirm_message"),
        "current_tool_call": state.get("current_tool_call"),
    }


async def handle_confirmed_action(state: AssistantState) -> dict[str, Any]:
    confirmed_action = state.get("context", {}).get("confirmed_tool")
    confirmed_args = state.get("context", {}).get("confirmed_arguments")
    if confirmed_action and confirmed_args:
        tool = next((t for t in SELF_SERVICE_TOOLS if t.name == confirmed_action), None)
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
            return {"current_tool_call": tc, "tools_to_call": [tc]}
    return {"tools_to_call": []}


async def compose_response(state: AssistantState) -> dict[str, Any]:
    intent = state.get("intent")
    docs = state.get("docs_hits", [])
    nav = state.get("navigation")
    current_tool_call = state.get("current_tool_call")
    error = state.get("error")
    message = state.get("context", {}).get("message", "")

    if error:
        return {"response": f"Something went wrong: {error}"}

    if intent == IntentType.CLARIFY:
        return {
            "response": "Could you give me a bit more detail so I can help? For example, your ticket number or the topic you need help with.",
            "intent": IntentType.CLARIFY,
        }

    if intent == IntentType.EXPLAIN:
        answer = f"Here's what I found about **{message or 'that'}**:\n\n"
        for i, doc in enumerate(docs[:3], 1):
            title = doc.get("title") or doc.get("subject") or f"Result {i}"
            snippet = (doc.get("body") or doc.get("content") or doc.get("excerpt") or "")[:180]
            answer += f"**{title}**\n> {snippet}\n\n"
        if not docs:
            answer += "I couldn't find a specific match. I can create a support ticket for you instead."
        return {"response": answer}

    if intent == IntentType.NAVIGATE:
        n = nav or pick_best_route(message, {})
        if n:
            return {"response": f"Here you go: **{n.label}**.\n\n navigation: {n.route}"}
        return {"response": "Try telling me which page you'd like to visit."}

    if intent == IntentType.EXECUTE:
        if current_tool_call and current_tool_call.error:
            return {"response": f"That didn't work: {current_tool_call.error}"}
        if current_tool_call and current_tool_call.result:
            record_url = (current_tool_call.result or {}).get("record_url", "")
            txt = "Done." + (f' Here\'s the link: {record_url}' if record_url else "")
            return {"response": txt}
        return {"response": "I'm not sure how to help with that. Could you rephrase?"}

    return {"response": "Hi! I can help you with tickets, invoices, contracts, and documentation. What do you need?"}


def build_graph():
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
        lambda state: "resolve_tools" if state.get("intent") in (IntentType.EXECUTE, IntentType.CLARIFY) else "compose_response",
        {"resolve_tools": "resolve_tools", "compose_response": "compose_response"},
    )

    g.add_conditional_edges(
        "resolve_tools",
        lambda state: "compose_response" if not state.get("tools_to_call") else "tool_execute",
        {"tool_execute": "tool_execute", "compose_response": "compose_response"},
    )

    g.add_conditional_edges(
        "tool_execute",
        lambda state: (
            "confirm_gate"
            if state.get("tools_to_call") and state.get("confirm_required", False)
            else "compose_response"
        ),
        {"confirm_gate": "confirm_gate", "compose_response": "compose_response"},
    )

    g.add_conditional_edges(
        "confirm_gate",
        lambda state: "handle_confirmed_action" if state.get("context", {}).get("confirmed_tool") else "compose_response",
        {"handle_confirmed_action": "handle_confirmed_action", "compose_response": "compose_response"},
    )

    g.add_edge("handle_confirmed_action", "compose_response")
    g.add_edge("compose_response", END)

    return g.compile()


self_service_graph = build_graph()


async def run_self_service_orchestrator(*, user: dict, message: str, token: str, session_id: str, context: dict) -> dict[str, Any]:
    session = SessionManager()
    state = await session.load(session_id)
    state["user"] = user
    state["validated_token"] = token
    state["session_id"] = session_id
    state["context"] = {**state.get("context", {}), **context, "message": message}
    state["available_tools"] = list(SELF_SERVICE_TOOLS)

    final = await self_service_graph.ainvoke(state)
    if isinstance(final, dict):
        final.setdefault("session_id", session_id)

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
