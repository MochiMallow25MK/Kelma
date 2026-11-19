import json
import math
from dotenv import load_dotenv
from db import _connect
from ingest_knowledge import get_embedding, local_hash_embedding

load_dotenv()

def get_db():
    return _connect()


def cosine_sim(a: list[float], b: list[float]) -> float:
    if not a or not b or len(a) != len(b):
        return 0.0
    dot = sum(x * y for x, y in zip(a, b))
    na = math.sqrt(sum(x * x for x in a)) or 1.0
    nb = math.sqrt(sum(y * y for y in b)) or 1.0
    return dot / (na * nb)


def get_child_history(child_id: int) -> dict:
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT id, age_band, language FROM children WHERE id = %s", (child_id,))
            child = cur.fetchone() or {"id": child_id, "age_band": "4-6", "language": "Egyptian Arabic"}

            cur.execute(
                """
                SELECT w.phoneme, COUNT(*) AS attempts,
                       ROUND(AVG(a.diagnostic_score), 1) AS avg_score,
                       SUM(CASE WHEN a.passed = 1 THEN 1 ELSE 0 END) AS passed_count
                FROM attempts a
                JOIN words w ON w.id = a.word_id
                WHERE a.child_id = %s AND a.attempt_type = 'speech'
                GROUP BY w.phoneme
                ORDER BY avg_score ASC
                """,
                (child_id,),
            )
            phoneme_stats = cur.fetchall()

            cur.execute(
                """
                SELECT a.word_id, w.word, w.phoneme, a.diagnostic_score, a.heard_text, a.activity
                FROM attempts a
                JOIN words w ON w.id = a.word_id
                WHERE a.child_id = %s
                ORDER BY a.created_at DESC
                LIMIT 10
                """,
                (child_id,),
            )
            recent = cur.fetchall()
            return {"child": child, "phoneme_stats": phoneme_stats, "recent_attempts": recent}
    finally:
        conn.close()


def retrieve_guidance(sound: str, age_band: str = "4-8", query_text: str = "", k: int = 3) -> list[dict]:
    """Retrieve top-k vetted knowledge cards without exposing any child PII."""
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, title, topic, sound, age_range, text, source_ref, embedding, embedding_model FROM knowledge_cards"
            )
            cards = cur.fetchall()

        if not cards:
            return []

        q_str = f"sound {sound} age {age_band} {query_text}".strip()
        q_emb_api, _ = get_embedding(q_str)
        q_emb_local = local_hash_embedding(q_str)

        scored = []
        for c in cards:
            if c["sound"] not in (sound, "ALL"):
                continue
            card_emb = json.loads(c["embedding"]) if c["embedding"] else []
            q_vec = q_emb_api if len(card_emb) == len(q_emb_api) else q_emb_local
            sim = cosine_sim(q_vec, card_emb)
            if c["sound"] == sound:
                sim += 0.25
            scored.append({
                "id": c["id"],
                "title": c["title"],
                "topic": c["topic"],
                "sound": c["sound"],
                "text": c["text"],
                "source_ref": c["source_ref"],
                "score": round(min(sim, 1.0), 4),
            })

        scored.sort(key=lambda x: x["score"], reverse=True)
        return scored[:k]
    finally:
        conn.close()


def select_words(sound: str, difficulty: int = 1, count: int = 5, category: str | None = None) -> list[dict]:
    """Query Egyptian Arabic Word Bank with emoji, category, cue, and audio."""
    conn = get_db()
    try:
        with conn.cursor() as cur:
            params = [sound, difficulty]
            cat_sql = ""
            if category:
                cat_sql = " AND category = %s"
                params.append(category)
            params.append(count)

            cur.execute(
                f"""
                SELECT id, word, emoji, english_meaning, phoneme, position, difficulty, category, cue, audio_path
                FROM words
                WHERE phoneme = %s AND difficulty = %s {cat_sql}
                ORDER BY RAND()
                LIMIT %s
                """,
                tuple(params),
            )
            rows = cur.fetchall()

            if len(rows) < count:
                needed = count - len(rows)
                existing_ids = [r["id"] for r in rows] or [0]
                placeholders = ",".join(["%s"] * len(existing_ids))
                cur.execute(
                    f"""
                    SELECT id, word, emoji, english_meaning, phoneme, position, difficulty, category, cue, audio_path
                    FROM words
                    WHERE id NOT IN ({placeholders})
                    ORDER BY (phoneme = %s) DESC, ABS(difficulty - %s) ASC, RAND()
                    LIMIT %s
                    """,
                    tuple(existing_ids + [sound, difficulty, needed]),
                )
                rows.extend(cur.fetchall())
            return rows
    finally:
        conn.close()


def handle_failure(word_id: int, fails: int) -> dict:
    """Three-tier failure handling (1: retry, 2: cue, 3+: easier word same sound)."""
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM words WHERE id = %s", (word_id,))
            w = cur.fetchone()
            if not w:
                return {"action": "retry", "message": "حاول مرة تانية! أنت بطل 🌟"}

            if fails <= 1:
                return {
                    "action": "retry",
                    "message": f"قربت جداً! اسمع الكلمة وحاول تقول ({w['word']}) مرة تانية 🎤",
                }
            if fails == 2:
                cue = w.get("cue") or f"ركز على صوت ({w['phoneme']}) في بداية أو وسط الكلمة"
                return {
                    "action": "cue",
                    "cue": cue,
                    "message": f"تلميح: {cue} 💡",
                }

            cur.execute(
                """
                SELECT id, word, emoji, english_meaning, phoneme, position, difficulty, category, cue, audio_path
                FROM words
                WHERE phoneme = %s AND id != %s
                ORDER BY difficulty ASC, RAND()
                LIMIT 1
                """,
                (w["phoneme"], word_id),
            )
            easier = cur.fetchone()
            if easier:
                return {
                    "action": "substitute",
                    "message": f"يلا نجرب كلمة أسهل بنفس الصوت ({w['phoneme']}): {easier['word']} {easier['emoji']}",
                    "word": easier,
                }
            return {"action": "retry", "message": "أحسنت المحاولة! يلا نكمل للكلمة اللي بعدها ⭐"}
    finally:
        conn.close()