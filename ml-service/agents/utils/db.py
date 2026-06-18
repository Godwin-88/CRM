"""
Legacy DB shim removed per docs/agent.md §4.14 Feature 1:
"communicating with the Laravel application via a dedicated internal API namespace
/api/v1/assistant/*, never directly querying the database."

All SQLAlchemy engine creation and DB access have been stripped.
This module exists only to avoid breaking any stale import paths;
it raises immediately if anything attempts to use it.
"""

import logging

logger = logging.getLogger(__name__)


class DatabaseAccessRemovedError(RuntimeError):
    """Raised when code attempts to use the removed direct-DB layer."""


def get_db(*args, **kwargs):
    raise DatabaseAccessRemovedError(
        "Direct database queries from the ml-service are prohibited by docs/agent.md §4.14 Feature 1. "
        "Migrate to the agent tool API (/api/v1/assistant/tool/*) via laravel_client.call_tool() instead."
    )


def __getattr__(name):
    raise DatabaseAccessRemovedError(
        f"'{name}' is no longer available in utils.db. "
        "Direct database access from the ml-service has been removed."
    )
