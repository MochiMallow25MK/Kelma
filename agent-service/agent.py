import json
import os
import requests
from dotenv import load_dotenv
from tools import get_child_history, get_db, retrieve_guidance, select_words

load_dotenv()

VALID_ACTIVITIES = {"say_it", "word_hunt"}

def validate_plan(plan: dict, valid_card_ids: set[int]) -> tuple[bool, str]:
    """FR-14: Validate sound, level 1..3, activity, and cited knowledge card IDs."""
    if not isinstance(plan, dict):
        return False, "Plan is not a dict"
    if plan.get("activity") not in VALID_ACTIVITIES:
        return False, "Invalid activity"
    diff = int(plan.get("difficulty", 1))
    if diff not in (1, 2, 3):
        return False, "Invalid difficulty"
    if not plan.get("phoneme"):
        return False, "Missing phoneme"
    cited = plan.get("source_ids", [])
    if valid_card_ids and not any(int(cid) in valid_card_ids for cid in cited if str(cid).isdigit()):
        return False, "No valid retrieved source ID cited"
    return True, "OK"

def call_gemini_rag(prompt: str) -> dict | None:
    key = os.getenv("GEMINI_API_KEY", "").strip()
    if not key:
        return None
    url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={key}"
    resp = requests.post(
        url,
        json={
            "contents": [{"parts": [{"text": prompt}]}],
            "generationConfig": {"responseMimeType": "application/json", "temperature": 0.2},
        },
        timeout=18,
    )
    if resp.status_code != 200:
        return None
    txt = resp.json()["candidates"][0]["content"]["parts"][0]["text"]
    return json.loads(txt)

def call_groq_rag(prompt: str) -> dict | None:
    key = os.getenv("GROQ_API_KEY", "").strip()
    if not key:
        return None
    resp = requests.post(
        "https://api.groq.com/openai/v1/chat/completions",
        headers={"Authorization": f"Bearer {key}", "Content-Type": "application/json"},
        json={
            "model": "llama-3.3-70b-versatile",
            "messages": [{"role": "user", "content": prompt}],
            "response_format": {"type": "json_object"},
            "temperature": 0.2,
        },
        timeout=18,
    )
    if resp.status_code != 200:
        return None
    txt = resp.json()["choices"][0]["message"]["content"]
    return json.loads(txt)

def build_rule_based_plan(history: dict, cards: list[dict]) -> dict:
    stats = history.get("phoneme_stats", [])
    if stats:
        weakest = stats[0]
        phoneme = weakest["phoneme"]
        avg = float(weakest["avg_score"] or 0)
        difficulty = 1 if avg < 65 else (2 if avg < 85 else 3)
        activity = "word_hunt" if avg < 50 else "say_it"
        reason = f"التركيز على صوت ({phoneme}) لأن متوسط الدقة {avg}%، مع التدرج في المستوى {difficulty}."
    else:
        phoneme = "ر"
        difficulty = 1
        activity = "say_it"
        reason = "جلسة تأسيسية لصوت (ر) باستخدام كلمات مصرية مألوفة ومصورة."

    return {
        "activity": activity,
        "phoneme": phoneme,
        "difficulty": difficulty,
        "reason": reason,
        "source_ids": [c["id"] for c in cards[:2]],
    }

def generate_session_plan(child_id: int) -> dict:
    # 1. OBSERVE (No PII sent to LLMs)
    history = get_child_history(child_id)
    stats = history.get("phoneme_stats", [])
    target_sound = stats[0]["phoneme"] if stats else "ر"
    age_band = history["child"].get("age_band", "4-6")

    # 2. RETRIEVE grounded knowledge cards
    cards = retrieve_guidance(sound=target_sound, age_band=age_band, query_text="Egyptian Arabic speech therapy", k=3)
    valid_card_ids = {c["id"] for c in cards}

    cards_context = "\n".join(
        [f"[Card ID={c['id']}] {c['title']} ({c['source_ref']}): {c['text']}" for c in cards]
    ) or "No cards available."

    prompt = f"""
You are Kelma's pediatric Egyptian Arabic speech-therapy planner (Agentic RAG).
Child Age Band: {age_band}
Phoneme Performance Stats (anonymized): {json.dumps(stats, ensure_ascii=False)}
Retrieved Clinical Knowledge Cards:
{cards_context}

Choose the next therapy session plan as strict JSON with keys:
- "activity": "say_it" or "word_hunt"
- "phoneme": target Arabic phoneme (e.g. "{target_sound}")
- "difficulty": integer 1, 2, or 3
- "reason": clear explanation in Arabic for parents/therapists citing the retrieved card guidance
- "source_ids": list of integer Card IDs used from the retrieved cards above
"""

    plan = None
    source_type = "agent"
    llm_used = "rule-based"

    # 3. REASON & 4. VALIDATE with Fallback Chain (Gemini -> Groq -> Rule-based)
    for model_name, fn in [("gemini-2.0-flash", call_gemini_rag), ("groq-llama-3.3-70b", call_groq_rag)]:
        try:
            candidate = fn(prompt)
            ok, _ = validate_plan(candidate, valid_card_ids)
            if ok:
                plan = candidate
                llm_used = model_name
                source_type = "agent"
                break
        except Exception:
            continue

    if not plan:
        plan = build_rule_based_plan(history, cards)
        source_type = "fallback"
        llm_used = "rule-based"

    # 5. ACT: Select words & log decision + cited sources
    words = select_words(sound=plan["phoneme"], difficulty=int(plan["difficulty"]), count=5)
    cited_cards = [c for c in cards if c["id"] in [int(x) for x in plan.get("source_ids", []) if str(x).isdigit()]]

    conn = get_db()
    log_id = None
    try:
        with conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO agent_logs (child_id, activity, sound, difficulty, reason, source, status)
                VALUES (%s, %s, %s, %s, %s, %s, 'pending')
                """,
                (child_id, plan["activity"], plan["phoneme"], int(plan["difficulty"]), plan["reason"], source_type),
            )
            log_id = cur.lastrowid
            for c in cited_cards:
                cur.execute(
                    "INSERT IGNORE INTO plan_sources (log_id, card_id, similarity_score) VALUES (%s, %s, %s)",
                    (log_id, c["id"], c["score"]),
                )
        conn.commit()
    finally:
        conn.close()

    return {
        "log_id": log_id,
        "source": source_type,
        "llm": llm_used,
        "activity": plan["activity"],
        "phoneme": plan["phoneme"],
        "difficulty": int(plan["difficulty"]),
        "reason": plan["reason"],
        "sources": [{"id": c["id"], "title": c["title"], "source_ref": c["source_ref"], "score": c["score"]} for c in cited_cards],
        "words": words,
    }