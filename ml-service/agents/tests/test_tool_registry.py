"""Unit tests for the tool registry module."""

import pytest

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from agents.tool_registry import REGISTERED_TOOLS, get, list_tools
from agents.state import ConfirmationTier


def test_registered_tools_not_empty():
    """Test that the registry contains tools."""
    assert len(REGISTERED_TOOLS) > 0


def test_all_tools_have_required_fields():
    """Test that every registered tool has the required contract fields."""
    for tool in REGISTERED_TOOLS:
        assert tool.name
        assert tool.description
        assert tool.tier
        assert tool.input_schema


def test_get_by_name():
    """Test that get returns the requested tool by exact name."""
    tool = get("tool.contacts.search")

    assert tool is not None
    assert tool.name == "tool.contacts.search"
    assert tool.tier == ConfirmationTier.READ


def test_get_unknown_returns_none():
    """Test that get returns None for unknown tool names."""
    assert get("tool.nonexistent") is None


def test_list_tools_returns_list():
    """Test that list_tools returns a list of ToolDefinition objects."""
    tools = list_tools()

    assert isinstance(tools, list)
    assert tools


def test_tier_enum_values():
    """Test that all tool tiers are valid ConfirmationTier enum values."""
    valid_tiers = set(ConfirmationTier)

    for tool in REGISTERED_TOOLS:
        assert tool.tier in valid_tiers
