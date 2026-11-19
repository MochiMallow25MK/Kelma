from fastapi import FastAPI
from pydantic import BaseModel
from agent import generate_session_plan
from tools import handle_failure, retrieve_guidance

app = FastAPI(title="Kelma Agentic RAG Service")

class PlanReq(BaseModel):
    child_id: int

class FailReq(BaseModel):
    word_id: int
    fails: int

class RetrieveReq(BaseModel):
    query: str = ""
    sound: str = "ر"
    age_band: str = "4-8"
    k: int = 3

@app.get("/health")
def health():
    return {"status": "ok", "mode": "agentic-rag", "llms": ["gemini-2.0-flash", "groq-llama-3.3-70b", "rule-based"]}

@app.post("/plan")
def plan(req: PlanReq):
    return generate_session_plan(req.child_id)

@app.post("/on_failure")
def on_failure(req: FailReq):
    return handle_failure(req.word_id, req.fails)

@app.post("/retrieve")
def retrieve(req: RetrieveReq):
    return {"cards": retrieve_guidance(sound=req.sound, age_band=req.age_band, query_text=req.query, k=req.k)}