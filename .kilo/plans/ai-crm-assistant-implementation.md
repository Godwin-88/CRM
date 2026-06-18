# Implementation Plan: AI CRM Assistant (ML-Service Layer)

**Spec:** `docs/agent.md` (Section 4.14)  
**Goal:** Implement the comprehensive AI CRM Assistant as specified — an isolated, agentic Python service with tool-calling access into the Laravel CRM's agent tool API, chat popup frontend integration, RBAC-scoped execution, documentation grounding, proactive suggestions, and quality tracking.

---

## Current State Assessment (Updated 2026-06-18)

### ml-service/agents/
- FastAPI app with LangGraph-based CRM assistant orchestrator
- `/agents/crm/chat` routes through 6-node LangGraph StateGraph (classify_intent → retrieve_docs → resolve_tools → tool_execute → confirm_gate → compose_response)
- LLM-based tool selector with keyword fallback in `resolve_tools`
- Confirmation gate wired into graph for write-reversible and write-significant tools
- `handle_confirmed_action` node replays tool execution after user confirmation
- 28-tool registry in `tool_registry.py` covering Modules 4.1–4.12
- Redis-backed session manager with 60-min TTL
- Circuit-breaker HTTP client for Laravel tool API
- Route manifest for deep-link navigation
- Self-service orchestrator (`self_service_orchestrator.py`) for customer-facing instance with constrained tool allowlist

### Laravel
- 28-tool allowlist in `AgentToolController` with RBAC enforcement, destructive-action deny-list, and `actor_type: assistant` audit logging
- `AssistantTokenService` mints SHA-256 short-lived tokens (5-min TTL, 100-use) via `AgentInternalToken` model
- `ValidateAssistantToken` middleware resolves user per tool call
- `AssistantChatController` proxies chat to ml-service, handles `confirmed_actions`, graceful KB fallback on ml-service failure
- Proactive suggestions endpoint (`GET /api/v1/assistant/proactive`) via Redis
- Feedback endpoint (`POST /api/v1/assistant/feedback`) with scalar rating
- `AssistantConversation` and `AssistantToolCall` models + migrations present

### Frontend
- Vue 3 chat popup (`AssistantChatPopup.vue`) with slide-over panel, message list, ConfirmationCard, QuickReplies, navigation links
- Pinia store `assistant.ts` for conversation state
- `useAssistant.ts` composable wired to `/api/v1/assistant/chat`
- Proactive fetch on popup open
- Header toggle icon

### Critical gaps vs agent.md spec (updated)

| Feature | Status |
|---|---|
| Feature 1: Isolated service + agent tool API | ✅ Implemented — `/api/v1/assistant/*` namespace, Redis-backed short-lived tokens |
| Feature 2: Chat popup with route context, quick-replies | ✅ Implemented — Vue 3 slide-over with proactive fetch, quick replies, navigation buttons |
| Feature 3: Intent classification, retrieval grounding, clarifying questions | ✅ Implemented — LangGraph classify + KB retrieval + clarify branch |
| Feature 4: Pre-filled navigation links | ✅ Implemented — `navigation.py` route manifest with entity prefill |
| Feature 5: Tool-calling infrastructure (versioned, RBAC-scoped, audited) | ✅ Implemented — 28 tools, tiered, `actor_type: assistant` audit |
| Feature 6: Confirmation gating (read / write-reversible / write-significant) | ✅ Implemented — confirm_gate node + backend `precondition_required` on write-significant |
| Feature 7: Role/audience-scoped tool sets | ✅ Partial — `self_service_orchestrator.py` for customer instance exists; full RBAC filtering via `availableTools` |
| Feature 8: Proactive suggestions on popup open | ✅ Implemented — Redis-backed proactive endpoint + frontend fetch |
| Feature 9: Quality, eval, grounding maintenance, staging canary | ⚠️ Partial — eval tables exist; canary helper classes + `versions.json` added; LLM-based low-confidence flagging pending |

---

## Architecture Design

### 1. Service Placement & Communication

```
┌────────────────────────────────────────────────────────────────┐
│                     Browser (Vue 3 Inertia)                      │
│  ┌──────────────┐  ┌─────────────────────────────────────────┐  │
│  │ Sidebar Nav  │  │  Chat Popup (slide-over)                  │  │
│  │              │  │  ├── Conversation messages                │  │
│  │              │  │  ├── Quick-reply chips                    │  │
│  │              │  │  ├── Confirm/Cancel action cards          │  │
│  │              │  │  └── Navigation link buttons              │  │
│  └──────────────┘  └─────────────────────────────────────────┘  │
└────────────┬─────────────────────────────────────────────────────┘
             │ WebSocket (Reverb) + HTTP
             ▼
┌────────────────────────────────────────────────────────────────┐
│              Laravel App (app container)                          │
│  1. Nginx ↔ Inertia/Vue routes (web.php)                        │
│  2. POST /api/v1/assistant/*  →  AssitantToolController        │
│       ├── auth:ssanctum + Bearer token                          │
│       ├── Mint short-lived internal token (JWT, 5min TTL)       │
│       │   → target_user_id embedded in payload,                 │
│       │   → signed with APP_KEY, stored in Redis                 │
│       ├── Route to tool handler (feature 5 registry)            │
│       ├── Enforce RBAC (Spatie) on tool action                  │
│       └── Log to audit_logs (actor_type: assistant)             │
│  3. POST /api/v1/assistant/chat  →  Proxy to ml-service         │
│       ├── Pass user context + short-lived token                  │
│       └── Stream SSE response back to popup                     │
└────────────┬─────────────────────────────────────────────────────┘
             │ Internal HTTP (Docker net)
             ▼
┌────────────────────────────────────────────────────────────────┐
│           ml-service/agents (FastAPI, port 8000)                 │
│  ┌─────────────────────────────────────────────────────┐       │
│  │  AgentOrchestrator (LangGraph StateGraph)           │       │
│  │  ┌───────────┐  ┌───────────┐  ┌────────────────┐  │       │
│  │  │ Node:     │  │ Node:     │  │ Node:         │  │       │
│  │  │ classify  │→ │ retrieve  │→ │ tool_execute   │  │       │
│  │  │ intent    │  │ docs      │  │ (tool call)    │  │       │
│  │  └───────────┘  └───────────┘  └───────┬────────┘  │       │
│  │       │              │              │                │       │
│  │       │              │              ▼                │       │
│  │       │              │   ┌──────────────────┐       │       │
│  │       │              │   │ Node:            │       │       │
│  │       │              │   │ confirm_gate    │       │       │
│  │       │              │   │ (read/write-    │       │       │
│  │       │              │   │  reversible/   │       │       │
│  │       │              │   │  significant)  │       │       │
│  │       │              │   └───────┬────────┘       │       │
│  │       │              │           │                 │       │
│  │       ▼              ▼           ▼                 │       │
│  │  ┌─────────────────────────────────────────────┐  │       │
│  │  │  Tool Registry (server-side allowlist per   │  │       │
│  │  │  role)  →  calls Laravel /api/v1/assistant/ │  │       │
│  │  │  tool/{name} with short-lived user token    │  │       │
│  │  └─────────────────────────────────────────────┘  │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                 │
│  Session State Server: Redis (conversation history per session,  │
│  60min TTL; state reloaded on each turn)                       │
│                                                                 │
│  LLM: configurable (Anthropic / OpenAI / Groq)                  │
└────────────────────────────────────────────────────────────────┘
             │
             │ tool call: POST /api/v1/assistant/tool/{name}
             ▼
┌────────────────────────────────────────────────────────────────┐
│         Laravel AgentToolController (new)                       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Tools mapped via registry (Feature 5):                   │  │
│  │  tool.contacts.search    → ContactController@index        │  │
│  │  tool.contacts.get       → ContactController@show         │  │
│  │  tool.deals.move_stage   → DealController@moveStage        │  │
│  │  tool.tickets.create     → TicketController@store          │  │
│  │  tool.kb.search          → KnowledgeBaseController@index   │  │
│  │  tool.reports.run        → ReportBuilderController@run     │  │
│  │  ... (full set from agent.md)                              │  │
│  │                                                            │  │
│  │  Auth:                                                      │  │
│  │  1. Validate short-lived internal token (Redis)             │  │
│  │  2. Resolve userId from token                               │  │
│  │  3. Apply Spatie permission gates on underlying action      │  │
│  │  4. Log tool call to audit_logs (actor_type: assistant)     │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────┘
```

### 2. Internal Token Mechanism (Feature 1)

**New:** `AgentInternalToken` model + `MintAssistantToken` command/endpoint.

```php
// app/Models/AgentInternalToken.php
id (uuid), user_id (FK), token_hash, abilities (JSON), 
expires_at, used_count, last_used_at, created_at
```

- Endpoint: `POST /api/v1/assistant/token` (auth:sanctum, scoped to generating own token only)
- TTL: 5 minutes, single-use per tool call (or multi-use within TTL for a session)
- Stored token hash in Redis with TTL; validated on each tool call
- Never expose plain token in audit logs; only log `user_id` + `session_id`

---

## Implementation Phases

### Phase 0 — Foundation: Agent Tool API & Internal Token (Week 1)

**Goal:** Create the secure bridge between ml-service and Laravel before building the agent logic on top of it.

#### Backend (Laravel)

1. **Database migration:** `create_agent_internal_tokens_table`
   - Fields: `id (uuid PK)`, `user_id (FK)`, `token_hash`, `abilities JSON[]`, `expires_at`, `used_count`, `last_used_at`, `created_at`
   - Index on `token_hash`, `user_id`, `expires_at`

2. **Model + Service:**
   - `app/Models/AgentInternalToken.php`
   - `app/Services/AssistantTokenService.php`
     - `mintToken(User $user, array $abilities): AgentInternalToken`
     - `validateToken(string $rawToken, string $toolName): ?User`
     - `revokeToken(string $tokenHash)`

3. **Controller + Routes:**
   - `app/Http/Controllers/Api/V1/AssistantTokenController.php`
     - `POST /api/v1/assistant/token` → mint (returns raw token once)
     - `DELETE /api/v1/assistant/token` → revoke session
   - `app/Http/Controllers/Api/V1/AgentToolController.php` (new, Feature 1 & 5)
     - Base controller for all tool endpoints
     - Middleware: `auth:sanctum` + `ValidateAssistantToken`
     - Each method: resolve tool → invoke underlying controller action with user context → log to audit

   ```php
   // routes/api.php additions
   Route::prefix('assistant')->middleware('auth:sanctum')->group(function () {
       Route::post('token', [AssistantTokenController::class, 'mint']);
       Route::delete('token', [AssistantTokenController::class, 'revoke']);
       
       // Tool API — each route proxies a specific CRM capability
       Route::post('tool/contacts/search', [AgentToolController::class, 'contactsSearch']);
       Route::post('tool/contacts/get', [AgentToolController::class, 'contactsGet']);
       Route::post('tool/deals/search', [AgentToolController::class, 'dealsSearch']);
       Route::post('tool/deals/move_stage', [AgentToolController::class, 'dealsMoveStage']);
       Route::post('tool/tickets/search', [AgentToolController::class, 'ticketsSearch']);
       Route::post('tool/tickets/create', [AgentToolController::class, 'ticketsCreate']);
       Route::post('tool/kb/search', [AgentToolController::class, 'kbSearch']);
       Route::post('tool/activities/create', [AgentToolController::class, 'activitiesCreate']);
       Route::post('tool/segments/preview', [AgentToolController::class, 'segmentsPreview']);
       Route::post('tool/dashboards/summary', [AgentToolController::class, 'dashboardsSummary']);
       Route::post('tool/analytics/metric', [AgentToolController::class, 'analyticsMetric']);
       Route::post('tool/contracts/search', [AgentToolController::class, 'contractsSearch']);
       Route::post('tool/loyalty/get_balance', [AgentToolController::class, 'loyaltyGetBalance']);
       Route::post('tool/clv/get_score', [AgentToolController::lass, 'clvGetScore']);
       Route::post('tool/users/my_permissions', [AgentToolController::class, 'usersGetMyPermissions']);
       Route::post('tool/integrations/status', [AgentToolController::class, 'integrationsStatus']);
       // ... extend as needed per agent.md Feature 5 list
   });
   ```

4. **Middleware:**
   - `app/Http/Middleware/ValidateAssistantToken.php`
     - Reads `X-Assistant-Token` header
     - Validates against Redis + DB
     - Sets `request->attributes->set('assistant_user', $user)` + `request->attributes->set('assistant_session_id', ...)`
     - Increments `used_count`; revoked if over rate-limit tier

5. **Audit integration:**
   - Ensure all tool calls flow through `Spatie\Activitylog` with `causedBy($assistantUser)` and `withProperties(['actor_type' => 'assistant', 'session_id' => ...])`

6. **Rate limiting:**
   - Per-token tier in Redis: `assistant:ratelimit:{session_id}` (e.g. 30 tool calls/session/5min)

#### ml-service changes

7. **Stop direct DB access.** Redirect all tool results through Laravel API calls:
   - Remove `tools/db_queries.py` raw SQL usage; replace with HTTP client calls to `/api/v1/assistant/tool/*`
   - Remove `utils/db.py` SQLAlchemy session builder (defer to Phase 1 cleanup if unused elsewhere)

8. **New: `ml-service/agents/tools/http_client.py`**
    - Uses `httpx.AsyncClient` with circuit breaker (matches Laravel's `HttpClientService` pattern)
    - Base URL from env (`LARAVEL_API_URL`); bearer token = short-lived internal token
    - Structured request/response logging

---

### Phase 1 — Agent Core: Orchestrator, Tool Registry, State (Week 2) ✅

**Goal:** Convert the single-pass chat node into a proper LangGraph state machine with memory, tool-calling loop, and intent classification.

#### New/modified files

9. **`agents/agents/orchestrator.py`** (completed)
   - ✅ LangGraph `StateGraph(AgentState)` with nodes wired:
     1. `classify_intent` — uses LLM to classify into {navigate, explain, execute, clarify}
     2. `retrieve_docs` — calls Laravel `tool.kb.search` (which hits Meilisearch/Scout) for grounding
     3. `resolve_tools` — uses `_select_tools_with_llm()` with fallback to `_match_tools()`
     4. `tool_execute` — sequential tool-calling loop
     5. `confirm_gate` — wired with conditional edge from `tool_execute`; handles confirmation flow
     6. `handle_confirmed_action` — replays tool_execute when confirmation arrives
     7. `compose_response` — synthesizes final reply with navigation links / action cards
   - ✅ Added `TOOL_SELECTOR_PROMPT` and `_select_tools_with_llm()` (async, returns ToolCall list)
   - ✅ Added `_fill_arguments_with_llm()` using LLM for argument population
   - ✅ `_match_tools` kept as fallback when LLM selector fails

10. **`agents/agents/tool_registry.py`** (completed)
     - Declarative registry of all tools (mirrors Laravel route list)
     - Each tool entry: `name`, `description`, `input_schema`, `output_schema`, `tier` (read/write-reversible/write-significant), `version`
     - Role-filtered at session init (Feature 7) — assemble allowed list server-side based on incoming user's RBAC

11. **`agents/agents/session_manager.py`** (new)
     - Redis-backed conversation state: `assistant:session:{session_id}` → JSON blob (messages, context, turn count)
     - Saves to Redis with 60min TTL on each turn
     - Recreates `AgentState` from Redis on each request

12. **`agents/agents/state.py`** update
     - Extend `AgentState` TypedDict to: `messages`, `context`, `user`, `validated_token`, `session_id`, `intent`, `tools_to_call`, `tool_results`, `confirm_required`, `response`

---

### Phase 2 — Feature 3–4: Intent, Retrieval, Navigation (Week 3)

**Goal:** Make the assistant actually understand users and send them to the right place with pre-filled context.

13. **`agents/prompts/assistant_system.md`** (new)
     - Structured feature index from agent.md (all 4.1–4.12 mapped to module names, URLs, tool names)
     - Clarification protocol: ambiguous → ask 2–3 concrete options with buttons
     - Category taxonomy: respond with `kind: navigate|explain|execute`
     - Citation rules: every record reference gets a link; never plain text directions

14. **Intent & entity extraction prompt** (inline in orchestrator or separate prompt file)
     - Output: JSON with `{intent: categorize, entities: {contact_id?, deal_id?, ...}, clarity: confident|ambiguous|unclear}`

15. **Navigation link builder**
     - `agents/tools/navigation.py` (new)
     - Maps CRM screens to URL patterns with query params: e.g. ticket list with `?account_id=X&overdue=1`
     - Reads route definitions from a JSON manifest (matching Inertia routes) kept in ml-service config
     - Note: Deep-linking into multi-step flows depends on Inertia page URL state conventions — coordinate with frontend in Phase 3

16. **Retrieval grounding**
     - Vector search on Meilisearch (already used for KB via Scout)
     - Add RAG retrieval endpoint: `POST /api/v1/assistant/docs/retrieve` in Laravel
     - Returns top-3 article chunks for given query + feature_ref filter
     - Assistant calls this before answering "how do I / what is" questions (Feature 3 acceptance)

---

### Phase 3 — Frontend: Chat Popup & Conseil (Week 4) ✅
     - Extend `AgentState` TypedDict to: `messages`, `context`, `user`, `validated_token`, `session_id`, `intent`, `tools_to_call`, `tool_results`, `confirm_required`, `response`

---

### Phase 3 — Frontend: Chat Popup & Conseil (Week 4) ✅

**Goal:** Vue 3 component that integrates into the existing Inertia shell.

17. **New Vue files:**
     ```
     resources/js/Components/CRM/AssistantChatPopup.vue    (slide-over panel)
     resources/js/Components/CRM/AssistantIcon.vue          (header icon button)
     resources/js/stores/assistant.ts  (Pinia store)
     resources/js/composables/useAssistant.ts
     ```

18. **Component behaviors:**
     - ✅ Header button toggles slide-over panel
     - Panel mounted/unmounted preserves conversation in Pinia store (rehydrated from localStorage or re-fetched on open)
     - ✅ Route context: parent layout emits `$page` (Inertia shared prop) → captured and sent with every user message
     - Message types: user text | assistant text | navigation button | confirmation card | quick-reply chips | system error/fallback
     - ✅ SSE/streaming: `streamChat()` function added in useAssistant.ts using ReadableStream
     - ✅ Proactive fetch on popup open: `fetchProactive()` calls GET `/api/v1/assistant/proactive`

19. **Laravel controller:**
     - `app/Http/Controllers/Api/V1/AssistantChatController.php`
       - ✅ `POST /api/v1/assistant/chat` (auth:sanctum)
       - Validates SSRF/XSS on route context
       - Proxies request to ml-service, returns SSE stream
       - ✅ Handles graceful degradation: if ml-service unreachable → returns static doc-search results from Scout
       - ✅ `GET /api/v1/assistant/proactive` — fetches pending proactive items from Redis
       - ✅ `POST /api/v1/assistant/feedback` — stores feedback in assistant_conversations table

20. **Props in Inertia shared state (if needed):**
     - Add `assistantEnabled`, `userPermissions` to `HandleInertiaRequests::share` so popup knows feature access before opening

---

### Phase 4 — Feature 6: Confirmation Gating & Write Execution (Week 5)

**Goal:** Safe write actions with explicit user confirmation.

21. **Frontend confirmation cards:**
    - Vue component `ConfirmationCard.vue` rendered per tool call
    - 3 tiers:
      - `read`: silent, no UI
      - `write-reversible`: compact card with Confirm/Cancel
      - `write-significant`: expanded card listing downstream consequences + Confirm/Cancel
    - User response in next message turn → frontend tags `assistant_confirmation: {tool, args, confirmed: true|false}` → ml-service picks up state and executes or aborts

22. **Backend enforcement:**
    - `AgentToolController::requireConfirmation()` middleware/check point for `write-significant` tools (server-side double-check — never trust frontend alone)
    - Write-reversible tools execute immediately on tool call (after token validation + RBAC)
    - Write-significant tools execute only on explicit confirmation payload from ml-service (which received from frontend)

23. **Destructive action exclusion:**
    - Hard-coded deny list in `AgentToolController`: purge, terminate contracts, disconnect integrations, bulk-delete contacts
    - Returns error response to ml-service: "This action is not permitted via assistant; navigate to screen X"

24. **Post-execution link:**
    - Every successful write returns `affected_record_url` in tool result
    - Assistant composes reply with inline button linking to the record detail page

---

### Phase 5 — Feature 7: Role/Audience Scoping & Self-Service Instance (Week 6)

**Goal:** Internal assistant (agent/manager/admin) and constrained customer instance.

25. **Tool registry filtering:**
    - At session init, ml-service calls `/api/v1/assistant/tools/available` (new endpoint) to retrieve the user's allowed tool set
    - Laravel endpoint: `AgentToolController@availableTools()` — reads user's Spatie permissions, maps to CRM modules, returns filtererd tool list

26. **Customer-facing (self-service) instance:**
    - Separate `agents/agents/self_service_orchestrator.py` (or param-driven variant)
    - Allowed tools: `tool.tickets.search|create|update_status`, `tool.kb.search`, `tool.loyalty.get_balance`, `tool.contracts.search` (own, read-only)
    - Different system prompt: no internal terminology, docs filtered to `audience: customer`
    - Deployed as separate `ml-service` FastAPI route: `POST /agents/self-service/chat`

27. **Prompt scoping:**
    - Manager tier adds team analytics tools; Admin tier adds config navigation tools
    - Enforced at tool-API level: even if prompt says "list all users", only tools user has `viewAny` on are in registry

---

### Phase 6 — Feature 8: Proactive Suggestions & Feature 9: Quality (Week 7)

**Goal:** Context-aware opening messages and ongoing quality monitoring.

28. **Proactive triggers:**
    - Laravel event listener on `TicketAssigned`, `SlaBreachWarning`, etc. stores in Redis: `assistant:proactive:{user_id}` (TTL 60min, expires after read)
    - Frontend `onMounted` of popup fetches `GET /api/v1/assistant/proactive` (auth:sanctum)
    - If non-empty, opening assistant message includes 1 urgent item with inline action button

29. **Feedback mechanism:**
    - Vue thumbs-up/down on conversation close → `POST /api/v1/assistant/feedback`
    - Stores in new table `assistant_conversations` (see below)
    - Negative feedback triggers logging of transcript (PII-masked) for maintainer review

30. **Evaluation tables (new):**
    ```sql
    CREATE TABLE assistant_conversations (
        id UUID PRIMARY KEY,
        user_id UUID REFERENCES users(id),
        session_id UUID,
        model_provider VARCHAR(50),
        model_name VARCHAR(100),
        started_at TIMESTAMPTZ,
        ended_at TIMESTAMPTZ,
        tool_calls_count INT,
        write_significant_confirmed INT DEFAULT 0,
        write_significant_cancelled INT DEFAULT 0,
        feedback_positive INT DEFAULT 0,
        feedback_negative INT DEFAULT 0,
        feedback_comment TEXT
    );

    CREATE TABLE assistant_tool_calls (
        id UUID PRIMARY KEY,
        conversation_id UUID REFERENCES assistant_conversations(id),
        tool_name VARCHAR(100),
        input_json JSONB,
        output_json JSONB,
        tier VARCHAR(50),
        success BOOLEAN,
        error_message TEXT,
        latency_ms INT,
        created_at TIMESTAMPTZ
    );

    CREATE TABLE assistant_low_confidence_routes (
        id UUID PRIMARY KEY,
        session_id UUID,
        user_query TEXT,
        resolved_intent VARCHAR(100),
        confidence_score FLOAT,
        flagged BOOLEAN DEFAULT true,
        created_at TIMESTAMPTZ
    );
    ```

31. **Staging canary:**
    - Env-var gated: `ASSISTANT_MODEL_VERSION`, `ASSISTANT_PROMPT_VERSION`
    - A `POST /api/v1/assistant/admin/prompts` endpoint (admin only) to preview/publish new prompts
    - Canary percentage field on prompt versions; release to 10% → monitor → 100%
    - Rollback button in admin docs dashboard

32. **Observability:**
    - OpenTelemetry tracing in ml-service (already in requirements.txt)
    - Metric: `assistant.tool_call.latency`, `assistant.session.duration`, `assistant.confirmation_rate`
    - Aggregated via existing Laravel analytics dashboard (new widget)

---

### Phase 7 — Integration, Docker, Deployment (Week 8)

**Goal:** Wire everything together, ensure the existing `docker-compose.yml` and Dockerfile are correct.

33. **Docker Compose updates** (`docker-compose.yml`):
   - `ml-agents` service already exists — extends with new env var: `LARAVEL_API_URL=http://app/api/v1`
   - Add `REDIS_URL` pointing to shared Redis (already present)
   - Add `MEILISEARCH_HOST` env for docs retrieval client

34. **Nginx config** (`ml-service/infrastructure/nginx/nginx.conf`):
    - ✅ Config already has `upstream agents_backend` at line 43-46
    - ✅ Added `location /api/v1/assistant/` block proxying to agents backend

35. **Laravel `HttpClientService` integration:**
    - ✅ Nginx proxies `/api/v1/assistant/` to agents:8000 for frontend calls

---

### Phase 8 — Testing, Hardening, QA (Week 9)

36. **Unit tests (Python/pytest):**
    - ✅ `tests/test_orchestrator.py`: created with tests for build_graph, resolve_tools clarify branch
    - `tests/test_tool_registry.py`: role-filtered tool lists
    - `tests/test_session_manager.py`: Redis TTL, state serialization
    - `tests/test_http_client.py`: circuit breaker, retry, error surfacing

37. **Feature tests (Pest/Laravel):**
   - `tests/Feature/AssistantToolApiTest.php`: POST each tool endpoint with valid/invalid internal token; assert RBAC enforcement, audit logging, and tool result shape
   - `tests/Feature/AssistantChatTest.php`: POST `/api/v1/assistant/chat` with auth + SSE stream; assert response includes expected action
   - `tests/Feature/AssistantTokenTest.php`: mint/validate/revoke flow

38. **E2E / Dusk:**
   - Agent logs in, opens chat popup, asks "show me John's deals" → receives disambiguation prompt
   - Agent confirms, gets navigation link → clicks → lands on pre-filtered deal list
   - Agent attempts destructive action via chat → receives permission error
   - Self-service contact logs in, opens popup, asks about their ticket → gets constrained help only

39. **Load test:**
   - Run tool-call storm: simulate 25 concurrent chat sessions hitting tool API; assert Horizon/Redis queue health

40. **Security review:**
   - Pen-test tool registry: attempt SSRF to arbitrary Laravel routes via crafted `tool_name` (must fail)
   - Pen-test token: replay after TTL (must fail), swap user context in payload (must fail)
   - Verify PII masking in feedback transcripts (agent.md section 4.10 rules)

---

## Open Questions / Tradeoffs

1. **Route context mapping depth:** The spec says the assistant should know "user is viewing Deal #4521 on the Kanban board." The Laravel app's `HandleInertiaRequests::share` currently doesn't pass route params by default. We'll need to extend it to pass `currentRoute`, `currentRecordId`, `currentModule`. Should I implement this as part of Phase 3, or do you want it now?

2. **Tool API design:** Two options:
   - **A) Thin proxy:** `AgentToolController` receives payload from ml-service, sets user context, calls the existing controller method (e.g. `DealController@moveStage`). Pro: reuses validation/authorization. Con: tight coupling to controller internals.
   - **B) Thin proxy with explicit query builder:** Rewrite tool handlers to build queries directly in `AgentToolController` for read-only tools (search, preview, get). Pro: cleaner separation, easier to shape agent-specific response formats. Con: duplicate query logic.
   
   I'm recommending **A** for writes (same validation/events/audit) and **B** for reads (search/preview where response shaping matters).

3. **Session storage:** The spec mandates Redis for conversation state. The existing ml-service has `REDIS_URL` in `.env.example` but no Redis client usage in the Python code yet (it just stores in memory per-request). We need to add `redis>=5.2.0` usage. Confirm acceptable.

4. **Frontend streaming:** Laravel can stream SSE, but the simplest MVP path is: user sends message → full LLM round-trip → response returned at once. Streaming can be a follow-up v2. Which path do you prefer?

5. **DB schema extension location:** The spec says `docs/agent.md` is part of the enterprise-crm docset. For the new tables (`assistant_conversations`, etc.), should the migrations live in `app/database/migrations/` (Laravel) or in `ml-service/infrastructure/db/` (Python side)? I'm recommending Laravel since these tables reference `users` and are queried by the main app's analytics layer.

---

## File Inventory Summary

### New Laravel files (backend)
```
app/Models/AgentInternalToken.php
app/Services/AssistantTokenService.php
app/Services/AgentToolService.php
app/Http/Controllers/Api/V1/AssistantTokenController.php
app/Http/Controllers/Api/V1/AgentToolController.php
app/Http/Controllers/Api/V1/AssistantChatController.php
app/Http/Middleware/ValidateAssistantToken.php
app/Http/Requests/MintAssistantTokenRequest.php
database/migrations/yyyy_mm_dd_hhmmss_create_agent_internal_tokens_table.php
database/migrations/yyyy_mm_dd_hhmmss_create_assistant_tables.php
routes/api.php (extend with /assistant/* prefix)
app/Http/Middleware/HandleInertiaRequests.php (extend shared props)
app/Services/HttpClientService.php (add ml-agents host)
```

### New/Created ml-service files (Python)
```
agents/agents/orchestrator.py          # replaces/rewrites chat_agent.py
agents/agents/tool_registry.py          # new
agents/agents/session_manager.py        # new
agents/agents/self_service_orchestrator.py  # new (Phase 5)
agents/agents/navigation.py             # new (Phase 2)
agents/agents/http_client.py            # new
agents/tools/laravel_client.py          # new (structured API calls)
agents/prompts/assistant_system.md      # new
agents/prompts/intent_classifier.md     # new
agents/prompts/customer_instance.md     # new
agents/services/conversation_store.py   # new (Redis)
agents/models/requests.py               # extend request/response models
agents/models/tool_schemas.py           # new
agents/__main__.py                      # refactor main.py entry
agents/Dockerfile                       # unchanged, already uses uvicorn
agents/main.py                          # update to use orchestrator
```

### New frontend files
```
resources/js/Components/CRM/AssistantChatPopup.vue
resources/js/Components/CRM/AssistantIcon.vue
resources/js/Components/CRM/ConfirmationCard.vue
resources/js/Components/CRM/HttpGetButton.vue
resources/js/stores/assistant.ts
resources/js/composables/useAssistant.ts
resources/js/Components/Layout/AppHeader.vue (insert AssistantIcon)
resources/js/Pages/AssistantChat.vue (optional: dedicated page)
```

---

## Execution Order Recommendation

Execute phases sequentially 0 → 8. Each phase produces a shippable, independently testable increment:
- Phase 0 unlocks Phase 1 (no secure tool API = unsafe agent)
- Phase 1 unlocks Phase 2–5 (no orchestrator/tools = no meaningful chat)
- Phase 3 can start in parallel with Phase 4 (frontend is independent of confirmation gating)
- Phase 6 (quality) requires the full loop to exist

---

## Estimated Effort

| Phase | Description | Est. Days |
|---|---|---|
| 0 | Agent Tool API + Token | 3 |
| 1 | Orchestrator + Registry + State | 3 |
| 2 | Intent, Retrieval, Navigation | 2 |
| 3 | Vue Chat Popup | 2 |
| 4 | Confirmation Gating | 2 |
| 5 | Role/Audience Scoping | 2 |
| 6 | Proactive + Quality + Canary | 2 |
| 7 | Docker + Deployment | 1 |
| 8 | Testing + Hardening | 2 |

**Total: ~19 days (≈ 4 weeks sprint-paced, or 3–4 weeks realistic with QA)**

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Tight coupling of AgentToolController to existing controllers | Thin proxy pattern (Option A) for writes; wrap with try/catch to isolate assistant failures |
| LangGraph state serialization across turns | Pinia→Laravel→Redis pipeline; JSON-serializable state only; no raw Python objects |
| LLM prompt injection via user input | Strict output schema enforcement (Pydantic); tool calls require explicit JSON schema; never concatenate user input into tool args unvalidated |
| PII exposure in conversation logs | Apply same `encrypted:` casts and masking middleware as existing audit trail |
| Canary complexity | Start with env-var rollouts (no UI canary tooling) →add UI canary once rollout is stable |
| Frontend-tool mismatch | Tool API versioning per agent.md Feature 5 (version in URL or header: `X-Tool-Version: 1`) |

---

## Success Criteria

- Chat popup opens from any CRM screen with route context pre-attached
- User can ask "show me John's deals" → assistant disambiguates → user selects correct John → navigates to pre-filtered deal list
- Write actions (move stage, create ticket) only execute after confirm card
- Self-service assistant is constrained to own records; internal assistant can see internal analytics
- All assistant actions appear in audit log with `actor_type: assistant`
- Negative feedback logged; low-confidence routes flagged
- Staging canary by prompt version works before full prod rollout
- If ml-service is unreachable, popup falls back to static documentation search (Scout KB)
