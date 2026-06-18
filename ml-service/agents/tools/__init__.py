"""Tool registry re-export (legacy db_queries removed per docs/agent.md §4.14 Feature 1)."""
from agents.agents.tool_registry import (
    REGISTERED_TOOLS,
    get,
    list_tools,
    to_openapi_fragment,
)

__all__ = [
    "REGISTERED_TOOLS",
    "get",
    "list_tools",
    "to_openapi_fragment",
]
