"""Unit tests for the Laravel HTTP client module."""

import httpx
import pytest
from unittest.mock import AsyncMock

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

import agents.laravel_client as laravel_client
from agents.laravel_client import call_docs_retrieve, call_kb_search, call_tool


@pytest.fixture(autouse=True)
def reset_circuit():
    """Reset the module-level circuit breaker before each test."""
    laravel_client._reset_circuit()


@pytest.fixture
def json_response():
    """Create an async JSON response fixture."""
    response = AsyncMock()
    response.status_code = 200
    response.is_success = True
    response.headers = {"content-type": "application/json"}
    response.json.return_value = {"ok": True, "data": {}}
    response.text = ""
    return response


@pytest.fixture
def mock_client(json_response):
    """Create a mock httpx.AsyncClient."""
    client = AsyncMock(spec=httpx.AsyncClient)
    client.post = AsyncMock(return_value=json_response)
    client.get = AsyncMock(return_value=json_response)
    client.aclose = AsyncMock()
    return client


@pytest.mark.asyncio
async def test_call_tool_success(mock_client):
    """Test that call_tool returns ok=True for a successful response."""
    laravel_client._client = mock_client

    result = await call_tool("tickets.create", {"subject": "Help"}, token="token-1", session_id="session-1")

    assert result.ok is True
    assert result.status == 200
    assert result.body == {"ok": True, "data": {}}
    mock_client.post.assert_awaited_once_with(
        "/assistant/tool/tickets.create",
        json={"subject": "Help"},
        headers={
            "X-Assistant-Token": "token-1",
            "X-Assistant-Session": "session-1",
            "Accept": "application/json",
        },
    )


@pytest.mark.asyncio
async def test_call_tool_failure(mock_client):
    """Test that call_tool returns ok=False for HTTPStatusError responses."""
    request = httpx.Request("POST", "http://app/api/v1/assistant/tool/tickets.create")
    response = httpx.Response(500, request=request, json={"error": "boom"})
    error = httpx.HTTPStatusError("server error", request=request, response=response)
    mock_client.post.side_effect = error
    laravel_client._client = mock_client

    result = await call_tool("tickets.create", {"subject": "Help"}, token="token-1", session_id="session-1")

    assert result.ok is False
    assert result.status == 500
    assert result.body == {"error": "boom"}
    assert result.error == response.text


@pytest.mark.asyncio
async def test_circuit_breaker_opens_after_failures(mock_client):
    """Test that the circuit breaker opens after five connection failures."""
    mock_client.post.side_effect = httpx.ConnectError("connection failed")
    laravel_client._client = mock_client

    for _ in range(5):
        await call_tool("tickets.create", {"subject": "Help"}, token="token-1", session_id="session-1")

    assert laravel_client._is_circuit_open() is True


@pytest.mark.asyncio
async def test_call_kb_search_wrapper(mock_client):
    """Test that call_kb_search calls the tool endpoint with the expected payload."""
    laravel_client._client = mock_client

    await call_kb_search("refund policy", per_page=5, token="token-1", session_id="session-1")

    mock_client.post.assert_awaited_once_with(
        "/assistant/tool/kb.search",
        json={"query": "refund policy", "per_page": 5},
        headers={
            "X-Assistant-Token": "token-1",
            "X-Assistant-Session": "session-1",
            "Accept": "application/json",
        },
    )


@pytest.mark.asyncio
async def test_call_docs_retrieve(mock_client):
    """Test that call_docs_retrieve calls the docs retrieve endpoint."""
    laravel_client._client = mock_client

    await call_docs_retrieve("reset password", token="token-1", session_id="session-1")

    mock_client.get.assert_awaited_once_with(
        "http://app/api/v1/assistant/docs/retrieve",
        params={"q": "reset password", "limit": 5},
        headers={
            "X-Assistant-Token": "token-1",
            "X-Assistant-Session": "session-1",
            "Accept": "application/json",
        },
    )
