#!/usr/bin/env python3
"""
FastAPI application for LangGraph-based AI agents.
"""

import os
import logging
import json
import re
import httpx
from contextlib import asynccontextmanager
from typing import Any, Dict, Optional, List
from datetime import datetime

from fastapi import FastAPI, HTTPException, Depends, Header, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

# Configuration
from dotenv import load_dotenv

load_dotenv()

# Configure logging
LOG_LEVEL_STR = os.getenv("LOG_LEVEL", "INFO").upper()
LOG_LEVEL = getattr(logging, LOG_LEVEL_STR, logging.INFO)

logging.basicConfig(
    level=LOG_LEVEL,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)

logger = logging.getLogger(__name__)

# Environment variables
AGENT_API_KEY = os.getenv("AGENT_API_KEY", "change_me")
DATABASE_URL = os.getenv("DATABASE_URL", "postgresql://localhost/marketing_hub")
REDIS_URL = os.getenv("REDIS_URL", "redis://localhost:6379")
ENVIRONMENT = os.getenv("ENVIRONMENT", "development")

# ================================================================
# Database and Agent Initialization
# ================================================================

# NOTE: Legacy marketing agents and direct DB routes have been removed
# per docs/agent.md §4.14 Feature 1. The ml-service must not query the
# database directly. All data access routes through the Laravel agent tool API
# (/api/v1/assistant/tool/*) only.
#
# Legacy imports (lead_scorer_agent, content_brief_agent, whatsapp_agent, etc.)
# are intentionally not loaded here; re-enable only after they are refactored
# to use the agent tool API instead of tools.db_queries direct SQL.

# New CRM Assistant Orchestrator
from agents.orchestrator import run_orchestrator
from agents.self_service_orchestrator import run_self_service_orchestrator as run_self_service

# ================================================================
# Request/Response Models
# ================================================================

class AgentResponse(BaseModel):
    crm_response: Dict[str, Any]

class CRMBuildContactRequest(BaseModel):
    lead_id: str
    lead_details: Dict[str, Any]
    existing_contacts: List[Dict[str, Any]] = Field(default_factory=list)

class CRMQualifyOppRequest(BaseModel):
    contact_id: str
    contact_profile: Dict[str, Any]
    history: List[Dict[str, Any]] = Field(default_factory=list)
    firmographics: Dict[str, Any] = Field(default_factory=dict)

class CRMDraftProposalRequest(BaseModel):
    opportunity_id: str
    opportunity_details: Dict[str, Any]
    pain_points: List[str] = Field(default_factory=list)
    products: List[Dict[str, Any]] = Field(default_factory=list)

class CRMNBARequest(BaseModel):
    contact_id: str
    contact_profile: Dict[str, Any]
    last_interaction: Dict[str, Any] = Field(default_factory=dict)
    opportunities: List[Dict[str, Any]] = Field(default_factory=list)

class CRMRetentionRequest(BaseModel):
    contact_id: str
    activity_metrics: Dict[str, Any]
    sentiment_summary: Dict[str, Any]
    rfm_segment: str

class HealthResponse(BaseModel):
    status: str = "healthy"
    service: str = "Digital Marketing Hub - Agents"
    environment: str = ENVIRONMENT
    version: str = "0.1.0"

class LeadScoringRequest(BaseModel):
    lead_id: str = Field(..., description="UUID of the lead to score")
    email: str = Field(..., description="Lead email")
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    source_channel: str = Field(..., description="Social platform lead came from")
    interaction_history: List[str] = Field(default_factory=list)
    content_interests: List[str] = Field(default_factory=list)

class LeadScoringResponse(BaseModel):
    lead_id: str
    score: float = Field(..., ge=0, le=100, description="Lead score (0-100)")
    stage: str = Field(..., description="Lead stage classification")
    reasoning: str
    recommended_action: str
    confidence: float = Field(..., ge=0, le=1)

class ContentBriefRequest(BaseModel):
    lookback_days: int = Field(default=30, ge=7, le=90)
    target_channels: List[str] = Field(default_factory=lambda: ["facebook", "instagram", "tiktok"])
    force_topic: Optional[str] = None

class ContentBriefResponse(BaseModel):
    topic: str
    hook: str
    content_format: str
    target_channels: List[str]
    call_to_action: str
    estimated_performance_band: str
    reasoning: str
    generated_at: str

class WhatsAppMessageRequest(BaseModel):
    thread_id: str = Field(..., description="WhatsApp conversation thread ID")
    message: str = Field(..., description="Incoming message text")
    sender_phone: str
    lead_id: Optional[str] = None

class WhatsAppMessageResponse(BaseModel):
    thread_id: str
    response_message: Optional[str] = None
    intent_classification: str
    sentiment: str
    requires_human_handoff: bool
    handoff_reason: Optional[str] = None
    confidence: float

class AnomalyDiagnosticsRequest(BaseModel):
    channel_id: str
    anomaly_type: str
    metric_name: str
    expected_value: float
    actual_value: float
    lookback_days: int = Field(default=30)

class AnomalyDiagnosticsResponse(BaseModel):
    channel_id: str
    anomaly_type: str
    diagnostic_summary: str
    root_cause_hypothesis: str
    recommended_actions: List[str]
    severity: str
    confidence: float

class DealScoringRequest(BaseModel):
    deal_id: str
    stage: str
    value: float
    expected_close_date: str
    days_in_stage: int
    interactions_last_14_days: int
    demo_trial_completed: bool
    contact_engagement_score: Optional[float] = None

class DealScoringResponse(BaseModel):
    deal_id: str
    score: float
    label: str
    signals: Dict[str, Any]

# ================================================================
# Authentication
# ================================================================

def verify_api_key(x_api_key: str = Header(...)) -> str:
    """Verify incoming API key matches configured key."""
    if x_api_key != AGENT_API_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid API key",
        )
    return x_api_key

# ================================================================
# FastAPI Application Setup
# ================================================================

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup and shutdown events."""
    logger.info(f"Starting Digital Marketing Hub Agents Service (env: {ENVIRONMENT})")
    yield
    await engine.dispose()
    logger.info("Shutting down agents service")

app = FastAPI(
    title="Digital Marketing Hub - AI Agents",
    description="LangGraph-based agents for marketing automation and intelligence",
    version="0.1.0",
    lifespan=lifespan,
)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"] if ENVIRONMENT == "development" else ["https://app.example.com"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ================================================================
# N8N Proxy Configuration
# ================================================================

# Mapping workflow IDs to their N8n webhook URLs
N8N_WEBHOOK_MAP = {
    "linkedin-ingestion": "http://n8n:5678/webhook/ingest-linkedin-nightly",
    "whatsapp-opt-in": "http://n8n:5678/webhook/whatsapp-opt-in-flow",
    "publish-meta": "http://n8n:5678/webhook/publish-to-meta",
    "publish-linkedin": "http://n8n:5678/webhook/publish-to-linkedin",
    # Add more mappings here
}

class CreatePostRequest(BaseModel):
    channel_id: str
    content: str
    scheduled_at: str

# ================================================================
# Legacy endpoint stubs — direct-DB calls replaced with HTTP calls to
# Laravel REST API per docs/agent.md §4.14 Feature 1.
# N8N workflows depend on these endpoints; do not remove without
# updating the consuming workflows.
# ================================================================

from agents.laravel_client import call_rest


@app.post("/agents/content/create-post", tags=["content"])
async def create_post(request: CreatePostRequest, api_key: str = Depends(verify_api_key)):
    result = await call_rest(
        "POST",
        "/api/v1/social-posts",
        json=request.dict(),
        service_api_key=api_key,
    )
    return result


@app.post("/agents/content/approve-post", tags=["content"])
async def approve_post(request: Dict[str, Any], api_key: str = Depends(verify_api_key)):
    post_id = request.get("post_id")
    status = request.get("status")
    if status not in ["approved", "rejected"]:
        raise HTTPException(status_code=400, detail="Invalid status")
    result = await call_rest(
        "POST",
        f"/api/v1/social-posts/{post_id}/approve",
        json={"status": status},
        service_api_key=api_key,
    )
    return result
# @app.post("/agents/score-lead", ...) — REMOVED
# @app.post("/agents/generate-brief", ...) — REMOVED
# @app.post("/agents/whatsapp-message", ...) — REMOVED


@app.get("/health", tags=["health"])
async def health_check() -> HealthResponse:
    return HealthResponse(environment=ENVIRONMENT)

@app.get("/status", tags=["health"])
async def status_check(api_key: str = Depends(verify_api_key)) -> Dict[str, Any]:
    return {
        "status": "operational",
        "service": "Digital Marketing Hub - Agents",
        "environment": ENVIRONMENT,
        "agents_available": ["lead_scorer", "content_brief_gen", "whatsapp_conversational", "anomaly_diagnostics", "crm_agents"],
    }

@app.post("/agents/crm/build-contact", response_model=AgentResponse, tags=["crm"])
async def build_contact(request: CRMBuildContactRequest, api_key: str = Depends(verify_api_key)):
    return AgentResponse(crm_response={
        "response": "This legacy marketing endpoint is deprecated. The build-contact flow is being migrated to the CRM assistant orchestrated tool API. Contact your platform administrator.",
        "deprecated": True,
    })

@app.post("/agents/crm/qualify-opportunity", response_model=AgentResponse, tags=["crm"])
async def qualify_opp(request: CRMQualifyOppRequest, api_key: str = Depends(verify_api_key)):
    return AgentResponse(crm_response={
        "response": "This endpoint has been removed. Use the CRM assistant chat at /agents/crm/chat for opportunity qualification via the tool API.",
        "deprecated": True,
    })

@app.post("/agents/crm/draft-proposal", response_model=AgentResponse, tags=["crm"])
async def draft_proposal(request: CRMDraftProposalRequest, api_key: str = Depends(verify_api_key)):
    return AgentResponse(crm_response={
        "response": "This endpoint has been removed. Use the CRM assistant chat at /agents/crm/chat for proposal drafting via the tool API.",
        "deprecated": True,
    })

@app.post("/agents/crm/next-best-action", response_model=AgentResponse, tags=["crm"])
async def get_nba(request: CRMNBARequest, api_key: str = Depends(verify_api_key)):
    return AgentResponse(crm_response={
        "response": "This endpoint has been removed. Use the CRM assistant chat at /agents/crm/chat for next-best-action via the tool API.",
        "deprecated": True,
    })

@app.post("/agents/crm/retention-check", response_model=AgentResponse, tags=["crm"])
async def retention_check(request: CRMRetentionRequest, api_key: str = Depends(verify_api_key)):
    return AgentResponse(crm_response={
        "response": "This endpoint has been removed. Use the CRM assistant chat at /agents/crm/chat for retention analysis via the tool API.",
        "deprecated": True,
    })

@app.post("/agents/score-lead", response_model=LeadScoringResponse, tags=["marketing"])
async def score_lead(request: LeadScoringRequest, api_key: str = Depends(verify_api_key)):
    return LeadScoringResponse(
        lead_id=request.lead_id,
        score=0,
        stage="deprecated",
        reasoning="This endpoint is deprecated. Use the CRM assistant chat for lead scoring via the tool API.",
        recommended_action="manual_review",
        confidence=0,
    )

@app.post("/agents/generate-brief", response_model=ContentBriefResponse, tags=["marketing"])
async def generate_content_brief(request: ContentBriefRequest, api_key: str = Depends(verify_api_key)):
    return ContentBriefResponse(
        topic="",
        hook="",
        content_format="",
        target_channels=[],
        call_to_action="",
        estimated_performance_band="",
        reasoning="This endpoint is deprecated. Use the CRM assistant chat for content briefs via the tool API.",
        generated_at=datetime.utcnow().isoformat(),
    )

@app.post("/agents/whatsapp-message", response_model=WhatsAppMessageResponse, tags=["marketing"])
async def process_whatsapp_message(request: WhatsAppMessageRequest, api_key: str = Depends(verify_api_key)):
    return WhatsAppMessageResponse(
        thread_id=request.thread_id,
        response_message=None,
        intent_classification="deprecated",
        sentiment="neutral",
        requires_human_handoff=False,
        handoff_reason="This endpoint is deprecated. Use the CRM assistant chat for WhatsApp via the tool API.",
        confidence=0,
    )

@app.post("/agents/diagnose-anomaly", response_model=AnomalyDiagnosticsResponse, tags=["marketing"])
async def diagnose_anomaly(request: AnomalyDiagnosticsRequest, api_key: str = Depends(verify_api_key)):
    return AnomalyDiagnosticsResponse(
        channel_id=request.channel_id,
        anomaly_type=request.anomaly_type,
        diagnostic_summary="This endpoint is deprecated. Use the CRM assistant chat for diagnostics via the tool API.",
        root_cause_hypothesis="",
        recommended_actions=[],
        severity="info",
        confidence=0,
    )

@app.exception_handler(HTTPException)
async def http_exception_handler(request, exc):
    return JSONResponse(status_code=exc.status_code, content={"error": exc.detail})

# ================================================================
# AI CRM Assistant Chat (Section 4.14)
# ================================================================

class CRMRequest(BaseModel):
    user: Dict[str, Any]
    message: str
    session_id: Optional[str] = None
    context: Dict[str, Any] = Field(default_factory=dict)
    confirmed_actions: List[Dict[str, Any]] = Field(default_factory=list)
    tool_results: List[Dict[str, Any]] = Field(default_factory=list)
    audience: Optional[str] = Field(default="internal", description="internal | self-service | manager | admin")


@app.post("/agents/crm/chat", response_model=AgentResponse, tags=["crm"])
async def chat_with_agent(request: CRMRequest, x_api_key: str = Header(..., alias="X-API-Key")):
    """CRM Assistant chat endpoint - LangGraph orchestrator with tool calling."""
    try:
        if request.audience == "self-service":
            result = await run_self_service(
                user=request.user,
                message=request.message,
                token=x_api_key,
                session_id=request.session_id or "",
                context={
                    **request.context,
                    "confirmed_actions": request.confirmed_actions,
                    "tool_results": request.tool_results,
                },
            )
        else:
            result = await run_orchestrator(
                user=request.user,
                message=request.message,
                token=x_api_key,
                session_id=request.session_id or "",
                context={
                    **request.context,
                    "confirmed_actions": request.confirmed_actions,
                    "tool_results": request.tool_results,
                },
            )
        return AgentResponse(crm_response=result)
    except Exception as exc:
        logger.exception("CRM orchestrator failed: %s", exc)
        docs_fallback = await _try_docs_fallback(
            query=request.message,
            token=x_api_key,
            session_id=request.session_id or "",
        )
        if docs_fallback:
            return AgentResponse(crm_response=docs_fallback)
        return AgentResponse(crm_response={
            "response": "I'm currently experiencing technical difficulties. Please try again shortly.",
            "error": str(exc),
            "session_id": request.session_id,
        })


async def _try_docs_fallback(query: str, token: str, session_id: str) -> dict | None:
    """
    When the LLM provider is unavailable (circuit open or persistent failures),
    degrade gracefully to a documentation search via the agent tool API,
    matching the static documentation search fallback in AssistantChatController.
    """
    try:
        from agents.laravel_client import call_tool
    except ImportError:
        return None

    try:
        result = await call_tool(
            "kb.search",
            {"query": query, "per_page": 5},
            token=token,
            session_id=session_id,
        )
        if not result.ok:
            return None
        hits = result.body.get("results", result.body.get("data", []))
        if not isinstance(hits, list) or not hits:
            return {
                "response": (
                    "I'm having trouble connecting to my AI brain right now, "
                    "and I couldn't find any matching articles automatically. "
                    "Please try again in a moment."
                ),
                "fallback": True,
                "error_code": "llm_provider_unavailable_no_docs",
            }
        articles = []
        for doc in hits[:3]:
            title = doc.get("title") or doc.get("subject") or "Help article"
            body = (doc.get("body") or doc.get("content") or doc.get("excerpt") or "")[:200]
            url = doc.get("url") or doc.get("slug") or ""
            articles.append({"title": title, "snippet": body, "url": url})
        article_lines = "\n\n".join(
            f"**{a['title']}**\n> {a['snippet']}"
            + (f"\n[Read more]({a['url']})" if a["url"] else "")
            for a in articles
        )
        return {
            "response": "I'm having trouble connecting to my AI brain right now. "
            "Here are the most relevant documentation articles while I recover:\n\n"
            + article_lines,
            "fallback": True,
            "error_code": "llm_provider_unavailable",
            "articles": articles,
        }
    except Exception:
        logger.warning("Docs fallback search also failed", exc_info=True)
        return None

@app.post("/agents/score-deal", response_model=DealScoringResponse, tags=["analytics"])
async def score_deal(request: DealScoringRequest, api_key: str = Depends(verify_api_key)):
    signals = {
        "days_in_stage": request.days_in_stage,
        "interactions": request.interactions_last_14_days,
        "demo_trial": 1 if request.demo_trial_completed else 0,
        "value": request.value,
    }
    
    elapsedDays = 0
    try:
        from datetime import datetime
        elapsedDays = (datetime.utcnow() - datetime.fromisoformat(request.expected_close_date.replace('Z', '+00:00'))).days
    except:
        pass
    
    score = min(100, max(0, (
        signals["interactions"] * 5 +
        signals["demo_trial"] * 25 +
        min(signals["value"] / 1000, 25) +
        max(0, 30 - signals["days_in_stage"])
    )))
    
    label = "cold" if score <= 25 else ("warm" if score <= 50 else ("hot" if score <= 75 else "very_hot"))
    
    return DealScoringResponse(
        deal_id=request.deal_id,
        score=score,
        label=label,
        signals=signals
    )
