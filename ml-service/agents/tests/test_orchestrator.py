"""Unit tests for the orchestrator module."""

import pytest
from unittest.mock import AsyncMock, MagicMock, patch

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from agents.orchestrator import build_graph, resolve_tools
from agents.state import IntentType, ConfirmationTier, AssistantState, ToolCall, ToolDefinition


@pytest.fixture
def read_tool():
    return ToolDefinition(
        name="tool.contacts.search",
        description="Search contacts",
        input_schema={"type": "object", "properties": {"query": {"type": "string"}}},
        output_schema={"type": "object"},
        tier=ConfirmationTier.READ,
    )


@pytest.fixture
def write_tool():
    return ToolDefinition(
        name="tool.tickets.create",
        description="Create a ticket",
        input_schema={
            "type": "object",
            "properties": {"subject": {"type": "string"}, "contact_id": {"type": "string"}},
            "required": ["subject", "contact_id"],
        },
        output_schema={"type": "object"},
        tier=ConfirmationTier.WRITE_REVERSIBLE,
    )


def test_build_graph_compiles():
    """Test that build_graph creates a valid compiled state graph."""
    graph = build_graph()
    assert graph is not None
    assert hasattr(graph, 'ainvoke')


@pytest.mark.asyncio
async def test_resolve_tools_returns_clarify_when_no_tools_match(read_tool):
    """Test that resolve_tools returns clarify intent when no tools match."""
    state = AssistantState({
        "intent": IntentType.EXECUTE,
        "context": {"message": "xyzzy plugh nonexistent"},
        "available_tools": [read_tool],
        "entities": {},
    })

    result = await resolve_tools(state)

    assert result.get("tools_to_call") == []
    assert result.get("intent") == IntentType.CLARIFY


@pytest.mark.asyncio
async def test_resolve_tools_returns_clarify_when_no_tools(read_tool):
    """Test that resolve_tools returns clarify when available_tools is empty."""
    state = AssistantState({
        "intent": IntentType.EXECUTE,
        "context": {"message": "search contacts"},
        "available_tools": [],
        "entities": {},
    })

    result = await resolve_tools(state)

    assert result.get("tools_to_call") == []
    assert result.get("intent") == IntentType.CLARIFY


@pytest.mark.asyncio
async def test_resolve_tools_sets_confirm_required_for_write_tool(write_tool):
    """Test that resolve_tools sets confirm_required for write-tier tools."""
    state = AssistantState({
        "intent": IntentType.EXECUTE,
        "context": {"message": "create ticket for contact 123"},
        "available_tools": [write_tool],
        "entities": {"contact_id": "123"},
    })

    with patch('agents.orchestrator._select_tools_with_llm', new_callable=AsyncMock) as mock_select:
        mock_select.return_value = [ToolCall(tool=write_tool, arguments={"contact_id": "123", "subject": "General inquiry"})]
        result = await resolve_tools(state)

    assert len(result.get("tools_to_call", [])) == 1
    assert result.get("confirm_required") is True
    assert result.get("confirm_message") is not None


@pytest.mark.asyncio
async def test_resolve_tools_navigate_intent(read_tool):
    """Test that resolve_tools returns empty tools for navigate intent."""
    state = AssistantState({
        "intent": IntentType.NAVIGATE,
        "context": {"message": "take me to contacts"},
        "available_tools": [read_tool],
        "entities": {},
    })

    result = await resolve_tools(state)

    assert result.get("tools_to_call") == []
    assert result.get("navigation") is not None


@pytest.mark.asyncio
async def test_resolve_tools_explain_intent(read_tool):
    """Test that resolve_tools returns empty tools for explain intent."""
    state = AssistantState({
        "intent": IntentType.EXPLAIN,
        "context": {"message": "how do I create a contact"},
        "available_tools": [read_tool],
        "entities": {},
    })

    result = await resolve_tools(state)

    assert result.get("tools_to_call") == []
    assert result.get("current_tool_call") is None