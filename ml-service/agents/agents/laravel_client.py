"""
Laravel agent tool API client — single responsibly class for calling
/api/v1/assistant/tool/{name} with the short-lived internal token.

Reuses Laravel's HttpClientService circuit-breaker semantics (5 failures / 5min).
Also provides a generic REST helper for legacy endpoints migrated to Laravel HTTP.
"""

from __future__ import annotations

import json
import logging
import os
import time
from dataclasses import dataclass
from typing import Any, Dict, Literal, Optional

import httpx

from .state import ToolResult

logger = logging.getLogger(__name__)

_LARAVEL_API_URL = os.getenv("LARAVEL_API_URL", "http://app/api/v1")
_CIRCUIT_FAILURES = 5
_CIRCUIT_WINDOW = 300.0

_client = httpx.AsyncClient(
    base_url=_LARAVEL_API_URL,
    timeout=httpx.Timeout(connect=10.0, read=30.0, write=10.0, pool=5.0),
)

_failures = 0
_last_failure_ts: float | None = None
_circuit_open_until = 0.0

_RATE_LIMIT_WINDOW = 60.0
_RATE_LIMIT_MAX = int(os.getenv("ASSISTANT_RATE_LIMIT_PER_MIN", "60"))
_session_calls: dict[str, list[float]] = {}


def _is_circuit_open() -> bool:
    global _circuit_open_until
    if time.monotonic() < _circuit_open_until:
        return True
    if _last_failure_ts is not None and (time.monotonic() - _last_failure_ts) > _CIRCUIT_WINDOW:
        _reset_circuit()
    return False


def _record_failure() -> None:
    global _failures, _last_failure_ts, _circuit_open_until
    _failures += 1
    _last_failure_ts = time.monotonic()
    if _failures >= _CIRCUIT_FAILURES:
        _circuit_open_until = time.monotonic() + _CIRCUIT_WINDOW
        logger.warning("Circuit breaker OPEN for %s until %.2f", _LARAVEL_API_URL, _circuit_open_until)


def _reset_circuit() -> None:
    global _failures, _last_failure_ts, _circuit_open_until
    _failures = 0
    _last_failure_ts = None
    _circuit_open_until = 0.0


def _check_rate_limit(session_id: str) -> None:
    now = time.monotonic()
    window_start = now - _RATE_LIMIT_WINDOW
    calls = _session_calls.get(session_id, [])
    _session_calls[session_id] = [t for t in calls if t > window_start]
    if len(_session_calls[session_id]) >= _RATE_LIMIT_MAX:
        raise RateLimitExceeded(
            f"Rate limit exceeded for session {session_id}: "
            f"{_RATE_LIMIT_MAX} calls per {int(_RATE_LIMIT_WINDOW)}s window"
        )
    _session_calls[session_id].append(now)


class CircuitOpen(Exception):
    """Raised when the circuit breaker is open for a downstream service."""


class RateLimitExceeded(Exception):
    """Raised when the per-session rate limit is exceeded."""


async def call_tool(
    tool_name: str,
    arguments: Dict[str, Any],
    *,
    token: str,
    session_id: str,
) -> ToolResult:
    if _is_circuit_open():
        raise CircuitOpen(f"Circuit open for {_LARAVEL_API_URL}")

    _check_rate_limit(session_id)

    started = time.monotonic()
    try:
        resp = await _client.post(
            f"/assistant/tool/{tool_name}",
            json=arguments,
            headers={
                "X-Assistant-Token": token,
                "X-Assistant-Session": session_id,
                "Accept": "application/json",
            },
        )
        latency = int((time.monotonic() - started) * 1000)
        body = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {"raw": resp.text}

        _reset_circuit()
        return ToolResult(ok=resp.is_success, status=resp.status_code, body=body, latency_ms=latency)
    except (httpx.ConnectError, httpx.TimeoutException) as exc:
        _record_failure()
        latency = int((time.monotonic() - started) * 1000)
        return ToolResult(
            ok=False,
            status=503,
            body={},
            latency_ms=latency,
            error=f"Connection failed: {exc}",
        )
    except httpx.HTTPStatusError as exc:
        latency = int((time.monotonic() - started) * 1000)
        return ToolResult(
            ok=False,
            status=exc.response.status_code,
            body=exc.response.json() if exc.response.headers.get("content-type", "").startswith("application/json") else {"raw": exc.response.text},
            latency_ms=latency,
            error=exc.response.text,
        )


async def call_kb_search(query: str, per_page: int = 10, *, token: str, session_id: str) -> ToolResult:
    return await call_tool("kb.search", {"query": query, "per_page": per_page}, token=token, session_id=session_id)


async def call_docs_retrieve(query: str, *, token: str, session_id: str) -> ToolResult:
    url = f"{_LARAVEL_API_URL}/assistant/docs/retrieve"
    if _is_circuit_open():
        raise CircuitOpen(f"Circuit open for {url}")

    started = time.monotonic()
    try:
        resp = await _client.get(
            url,
            params={"q": query, "limit": 5},
            headers={
                "X-Assistant-Token": token,
                "X-Assistant-Session": session_id,
                "Accept": "application/json",
            },
        )
        latency = int((time.monotonic() - started) * 1000)
        body = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {"raw": resp.text}
        _reset_circuit()
        return ToolResult(ok=resp.is_success, status=resp.status_code, body=body, latency_ms=latency)
    except (httpx.ConnectError, httpx.TimeoutException) as exc:
        _record_failure()
        latency = int((time.monotonic() - started) * 1000)
        return ToolResult(
            ok=False,
            status=503,
            body={},
            latency_ms=latency,
            error=f"Connection failed: {exc}",
        )


async def call_rest(
    method: Literal["GET", "POST", "PUT", "PATCH", "DELETE"],
    path: str,
    *,
    params: Optional[Dict[str, Any]] = None,
    json: Optional[Dict[str, Any]] = None,
    service_api_key: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Generic Laravel REST helper (shares circuit breaker with tool API).

    Used by legacy ml-service endpoints migrated from direct-DB to Laravel HTTP.
    """
    if _is_circuit_open():
        raise CircuitOpen(f"Circuit open for {_LARAVEL_API_URL}")

    headers: Dict[str, str] = {"Accept": "application/json"}
    if service_api_key:
        headers["X-API-Key"] = service_api_key

    started = time.monotonic()
    try:
        resp = await _client.request(
            method,
            path,
            params=params,
            json=json,
            headers=headers,
        )
        latency = int((time.monotonic() - started) * 1000)

        if resp.is_success:
            _reset_circuit()
            body = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {"raw": resp.text}
            return body if isinstance(body, dict) else {"raw": body, "status": resp.status_code}

        _record_failure()
        error_body = resp.text
        logger.warning("Laravel REST %s %s failed (%s): %s", method, path, resp.status_code, error_body[:200])
        return {"error": error_body, "status": resp.status_code, "latency_ms": latency}
    except (httpx.ConnectError, httpx.TimeoutException) as exc:
        _record_failure()
        raise CircuitOpen(f"Laravel unreachable at {_LARAVEL_API_URL}: {exc}") from exc


async def close() -> None:
    await _client.aclose()
