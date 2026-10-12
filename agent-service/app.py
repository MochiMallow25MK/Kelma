# Agent service. Run with:  uvicorn app:app --reload --port 8001
import os

from dotenv import find_dotenv, load_dotenv

load_dotenv(find_dotenv(usecwd=True))   # reads the .env file (keys stay out of Git)

from fastapi import FastAPI
from pydantic import BaseModel

import agent
import tools as T

app = FastAPI(title="Kelma Agent Service")


# ---- Optional: send every agent step to Langfuse (tool calls, latency, token cost) ----
langfuse = None
if os.getenv("LANGFUSE_PUBLIC_KEY"):
    try:
        from langfuse import get_client
        from openinference.instrumentation.crewai import CrewAIInstrumentor

        CrewAIInstrumentor().instrument(skip_dep_check=True)
        langfuse = get_client()
        print("Langfuse tracing:", "ON" if langfuse.auth_check() else "keys rejected")
    except Exception as err:
        print("Langfuse tracing disabled:", err)


class PlanRequest(BaseModel):
    child_id: int


class FailureRequest(BaseModel):
    word_id: int
    fails: int


@app.get("/health")
def health():
    return {"status": "ok", "llms": [llm.model for llm in agent.configured_llms()]}


@app.post("/plan")
def plan(req: PlanRequest):
    """Decide what the child practises next and return the words."""
    result = agent.build_plan_with_agent(req.child_id)   # Gemini, then Groq; None if neither works
    source = "agent"
    if result is None:                       # no key, LLM error, or rate limit
        result, source = T.rule_based_plan(req.child_id), "fallback"
    words = T.select_words(result["phoneme"], result["difficulty"])
    T.log_progress(req.child_id, result, source)
    if langfuse:
        langfuse.flush()
    return {**result, "source": source, "words": words}


@app.post("/on_failure")
def on_failure(req: FailureRequest):
    return T.on_failure(req.word_id, req.fails)
