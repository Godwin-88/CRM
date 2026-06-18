import os
import logging
import time
from typing import Any, Awaitable, Callable, TypeVar

from langchain_openai import ChatOpenAI
from langchain_anthropic import ChatAnthropic

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Shared circuit-breaker for all outbound LLM provider calls.
# Mirrors Laravel's HttpClientService pattern (5 failures / 5 min window).
# ---------------------------------------------------------------------------
_LLM_CB_FAILURES = 5
_LLM_CB_WINDOW = 300.0
_llm_cb_failures = 0
_llm_cb_last_failure_ts = None
_llm_cb_open_until = 0.0


class LLMProviderUnavailable(Exception):
    """Raised when the LLM provider circuit breaker is open."""


def _llm_cb_is_open() -> bool:
    global _llm_cb_open_until
    if time.monotonic() < _llm_cb_open_until:
        return True
    if _llm_cb_last_failure_ts is not None and (time.monotonic() - _llm_cb_last_failure_ts) > _LLM_CB_WINDOW:
        _llm_cb_reset()
    return False


def _llm_cb_record_failure() -> None:
    global _llm_cb_failures, _llm_cb_last_failure_ts, _llm_cb_open_until
    _llm_cb_failures += 1
    _llm_cb_last_failure_ts = time.monotonic()
    if _llm_cb_failures >= _LLM_CB_FAILURES:
        _llm_cb_open_until = time.monotonic() + _LLM_CB_WINDOW
        logger.warning(
            "LLM provider circuit OPEN for %s until %.2f",
            os.getenv("LLM_PROVIDER", "openai"),
            _llm_cb_open_until,
        )


def _llm_cb_reset() -> None:
    global _llm_cb_failures, _llm_cb_last_failure_ts, _llm_cb_open_until
    _llm_cb_failures = 0
    _llm_cb_last_failure_ts = None
    _llm_cb_open_until = 0.0


T = TypeVar("T")


async def _llm_ainvoke_wrapped(model: Any, *args: Any, **kwargs: Any) -> Any:
    """Wraps model.ainvoke() with circuit-breaker semantics."""
    if _llm_cb_is_open():
        raise LLMProviderUnavailable(
            "LLM provider circuit is open after repeated failures. "
            "Please try again in a few minutes."
        )
    started = time.monotonic()
    try:
        result = await model.ainvoke(*args, **kwargs)
        _llm_cb_reset()
        return result
    except Exception as exc:
        _llm_cb_record_failure()
        logger.warning("LLM provider call failed (latency %.0fms): %s", (time.monotonic() - started) * 1000, exc)
        raise


def _llm_invoke_wrapped(model: Any, *args: Any, **kwargs: Any) -> Any:
    """Wraps sync model.invoke() with the same circuit-breaker."""
    if _llm_cb_is_open():
        raise LLMProviderUnavailable(
            "LLM provider circuit is open after repeated failures. "
            "Please try again in a few minutes."
        )
    started = time.monotonic()
    try:
        result = model.invoke(*args, **kwargs)
        _llm_cb_reset()
        return result
    except Exception as exc:
        _llm_cb_record_failure()
        logger.warning("LLM provider sync call failed (latency %.0fms): %s", (time.monotonic() - started) * 1000, exc)
        raise


async def _wrap_model(model: Any) -> Any:
    """Monkey-patch achat model's ainvoke/invoke with circuit-breaker wrappers."""
    original_ainvoke = model.ainvoke

    async def _cb_ainvoke(*a: Any, **kw: Any) -> Any:
        return await _llm_ainvoke_wrapped(model, *a, **kw)

    model.ainvoke = _cb_ainvoke

    if hasattr(model, "invoke"):
        original_invoke = model.invoke

        def _cb_invoke(*a: Any, **kw: Any) -> Any:
            return _llm_invoke_wrapped(model, *a, **kw)

        model.invoke = _cb_invoke

    return model


# ---------------------------------------------------------------------------
# Model factory
# ---------------------------------------------------------------------------
def get_model(model_name: str = None):
    """
    Get an LLM model based on environment configuration.
    Supports OpenAI, Anthropic, and Groq (via OpenAI-compatible API).

    All outbound LLM calls are wrapped with a circuit breaker that mirrors
    Laravel's HttpClientService pattern (5 failures / 5 min window),
    fulfilling docs/agent.md §4.14 Feature 1 and §4.11 Feature 10.
    """
    provider = os.getenv("LLM_PROVIDER", "openai").lower()

    if provider == "groq":
        logger.info("Using Groq (OpenAI-compatible) provider")
        model = ChatOpenAI(
            model=model_name or "llama-3.3-70b-versatile",
            openai_api_key=os.getenv("GROQ_API_KEY"),
            openai_api_base=os.getenv("GROQ_BASE_URL", "https://api.groq.com/openai/v1"),
            temperature=0,
        )
    elif provider == "anthropic":
        logger.info("Using Anthropic provider")
        model = ChatAnthropic(
            model=model_name or "claude-3-5-sonnet-20240620",
            temperature=0,
        )
    else:
        logger.info("Using OpenAI provider")
        model = ChatOpenAI(
            model=model_name or "gpt-4o-mini",
            temperature=0,
        )

    return _wrap_model(model)


def load_prompt(filename: str) -> str:
    """Load a prompt from the prompts directory."""
    prompt_path = os.path.join(os.path.dirname(__file__), "..", "prompts", filename)
    try:
        with open(prompt_path, "r") as f:
            return f.read()
    except Exception as e:
        logger.error(f"Error loading prompt {filename}: {e}")
        return ""
