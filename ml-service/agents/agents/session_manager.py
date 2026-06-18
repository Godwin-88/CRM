"""
Session manager: load / save conversation state in Redis with 60-minute TTL.

Wires into LangGraph checkpoints conceptually, but uses our own lightweight JSON
payload in Redis so the Laravel app can also read/write session context if needed.
"""

from __future__ import annotations

import json
import logging
import os
from dataclasses import dataclass, asdict
from datetime import datetime, timezone
from typing import Any, Dict, List, Optional

import redis.asyncio as aioredis

from .state import AssistantState

logger = logging.getLogger(__name__)

_REDIS_URL = os.getenv("REDIS_URL", "redis://redis:6379")
_KEY_PREFIX = "assistant:session:"
_TTL_SECONDS = 60 * 60  # 60 minutes per agent.md Feature 2


class SessionManager:
    def __init__(self, redis_url: str = _REDIS_URL) -> None:
        self._redis: aioredis.Redis | None = None
        self._redis_url = redis_url

    async def _get_redis(self) -> aioredis.Redis:
        if self._redis is None:
            self._redis = aioredis.from_url(
                self._redis_url,
                encoding="utf-8",
                decode_responses=True,
                socket_connect_timeout=5,
                socket_keepalive=True,
            )
        return self._redis

    def _key(self, session_id: str) -> str:
        return f"{_KEY_PREFIX}{session_id}"

    async def load(self, session_id: str) -> AssistantState:
        r = await self._get_redis()
        raw = await r.get(self._key(session_id))
        if not raw:
            state = AssistantState()
            state["session_id"] = session_id
            await self.save(state)
            return state

        try:
            data = json.loads(raw)
        except json.JSONDecodeError:
            logger.exception("Failed to decode session payload for %s", session_id)
            state = AssistantState()
            state["session_id"] = session_id
            return state

        restored = AssistantState(
            messages=data.get("messages", []),
            context=data.get("context", {}),
            user=data.get("user", {}),
            validated_token=data.get("validated_token", ""),
            session_id=session_id,
            available_tools=[],
            intent=data.get("intent"),
            intent_confidence=data.get("intent_confidence"),
            entities=data.get("entities", {}),
            docs_hits=data.get("docs_hits", []),
            tools_to_call=[],
            current_tool_call=None,
            confirm_required=bool(data.get("confirm_required", False)),
            confirm_message=data.get("confirm_message"),
            response=data.get("response"),
            navigation=data.get("navigation"),
            error=data.get("error"),
        )
        return restored

    async def save(self, state: AssistantState) -> None:
        r = await self._get_redis()
        payload = {
            "messages": state.get("messages", []),
            "context": state.get("context", {}),
            "user": state.get("user", {}),
            "validated_token": state.get("validated_token", ""),
            "session_id": state.get("session_id", ""),
            "intent": state.get("intent"),
            "intent_confidence": state.get("intent_confidence"),
            "entities": state.get("entities", {}),
            "docs_hits": state.get("docs_hits", []),
            "confirm_required": bool(state.get("confirm_required", False)),
            "confirm_message": state.get("confirm_message"),
            "response": state.get("response"),
            "navigation": state.get("navigation"),
            "error": state.get("error"),
            "updated_at": datetime.now(timezone.utc).isoformat(),
        }
        try:
            await r.set(self._key(state.get("session_id", "")), json.dumps(payload), ex=_TTL_SECONDS)
        except Exception:
            logger.exception("Failed to persist session state")

    async def refresh_ttl(self, session_id: str) -> None:
        r = await self._get_redis()
        await r.expire(self._key(session_id), _TTL_SECONDS)

    async def delete(self, session_id: str) -> None:
        r = await self._get_redis()
        await r.delete(self._key(session_id))
