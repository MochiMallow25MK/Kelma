# The four agent tools as plain Python functions (easy to read and test).
# agent.py wraps three of them as CrewAI tools for the LLM to call.
from db import query, execute

ACTIVITIES = ["say_it_and_collect_it", "word_hunt", "speech_rocket"]


def get_child_progress(child_id):
    """Average diagnostic score and number of attempts for each target sound."""
    return query(
        """SELECT w.phoneme, ROUND(AVG(a.diagnostic_score)) AS avg_score, COUNT(*) AS attempts
           FROM attempts a JOIN words w ON w.id = a.word_id
           WHERE a.child_id = %s GROUP BY w.phoneme""",
        (child_id,),
    )


def select_activity(avg_score=None):
    """TOOL 1 – pick the game that fits the child's level on their weakest sound."""
    if avg_score is None:
        return {"activity": "say_it_and_collect_it", "why": "first session, so start with guided articulation"}
    if avg_score < 60:
        return {"activity": "say_it_and_collect_it", "why": "low accuracy, so use images, audio and cues"}
    return {"activity": "speech_rocket", "why": "good accuracy, so use repetition to build consistency"}


def select_words(phoneme, difficulty, count=5):
    """TOOL 2 – pull target words from the Word Bank (closest difficulty first)."""
    return query(
        """SELECT id, word, image, phoneme, difficulty FROM words WHERE phoneme = %s
           ORDER BY ABS(difficulty - %s), RAND() LIMIT %s""",
        (phoneme, int(difficulty), int(count)),
    )


def on_failure(word_id, fails):
    """TOOL 3 – 3-tier escalation: retry, then simplified cue, then easier word."""
    if fails <= 1:
        return {"action": "retry"}
    if fails == 2:
        return {"action": "cue"}
    rows = query("SELECT phoneme, difficulty FROM words WHERE id = %s", (word_id,))
    if not rows:
        return {"action": "retry"}
    w = rows[0]
    easier = query(
        """SELECT id, word, image, phoneme FROM words
           WHERE phoneme = %s AND difficulty <= %s AND id <> %s ORDER BY difficulty, RAND() LIMIT 1""",
        (w["phoneme"], w["difficulty"], word_id),
    )
    return {"action": "substitute", "word": easier[0]} if easier else {"action": "retry"}


def log_progress(child_id, plan, source):
    """TOOL 4 – record the agent's decision so the therapist can review it."""
    execute(
        """INSERT INTO agent_logs (child_id, activity, phoneme, difficulty, reason, source)
           VALUES (%s, %s, %s, %s, %s, %s)""",
        (child_id, plan["activity"], plan["phoneme"], plan["difficulty"], plan["reason"][:250], source),
    )


def rule_based_plan(child_id):
    """Backup plan without any LLM: practise the weakest sound at a matching level."""
    progress = get_child_progress(child_id)
    if not progress:
        activity = select_activity(None)
        return {"phoneme": "r", "difficulty": 1, "activity": activity["activity"],
                "reason": "New child: starting with the /r/ sound at an easy level."}
    weakest = min(progress, key=lambda p: p["avg_score"])
    avg = weakest["avg_score"]
    difficulty = 1 if avg < 50 else (2 if avg < 75 else 3)
    activity = select_activity(avg)
    return {"phoneme": weakest["phoneme"], "difficulty": difficulty, "activity": activity["activity"],
            "reason": f"Sound /{weakest['phoneme']}/ is the weakest (average {avg:.0f}%), so practise it at level {difficulty}."}
