"""Unit tests for the Redis session manager module."""

import pytest
from unittest.mock import AsyncMock

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from agents.session_manager import SessionManager
from agents.state import AssistantState


@pytest.fixture
def redis_client():
    """Create a mock Redis client with async methods."""
    client = AsyncMock()
    client.get = AsyncMock()
    client.set = AsyncMock()
    client.expire = AsyncMock()
    client.delete = AsyncMock()
    return client


@pytest.fixture
def manager(redis_client):
    """Create a SessionManager using the mock Redis client."""
    session_manager = SessionManager("redis://mock:6379")
    session_manager._redis = redis_client
    return session_manager


@pytest.mark.asyncio
async def test_save_and_load(manager, redis_client):
    """Test that a saved session state can be loaded back."""
    state = AssistantState(
        session_id="session-123",
        context={"foo": "bar"},
        entities={"contact_id": "contact-1"},
    )
    saved_payload = None

    async def capture_set(key, value, ex):
        nonlocal saved_payload
        saved_payload = value
        return None

    redis_client.set.side_effect = capture_set

    await manager.save(state)
    redis_client.get.return_value = saved_payload
    loaded = await manager.load("session-123")

    redis_client.set.assert_awaited_once()
    assert redis_client.get.assert_called_once_with("assistant:session:session-123")
    assert loaded.session_id == "session-123"
    assert loaded.context == {"foo": "bar"}
    assert loaded.entities == {"contact_id": "contact-1"}


@pytest.mark.asyncio
async def test_refresh_ttl_calls_redis_expire(manager, redis_client):
    """Test that refresh_ttl extends the Redis key TTL."""
    await manager.refresh_ttl("session-123")

    redis_client.expire.assert_awaited_once_with("assistant:session:session-123", 3600)


@pytest.mark.asyncio
async def test_delete_calls_redis_delete(manager, redis_client):
    """Test that delete removes the Redis session key."""
    await manager.delete("session-123")

    redis_client.delete.assert_awaited_once_with("assistant:session:session-123")
