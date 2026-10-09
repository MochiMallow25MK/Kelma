# The Therapy Agent, built with CrewAI. The LLM decides WHICH tools to call;
# we then validate its answer so a wrong LLM output can never break the game.
import json
import os
import re

from crewai import LLM, Agent, Crew, Task
from crewai.tools import tool

import tools as T


@tool("get_child_progress")
def get_child_progress_tool(child_id: int) -> str:
    """Get the child's average pronunciation score (0-100) and number of attempts for each target sound."""
    return json.dumps(T.get_child_progress(int(child_id)))


@tool("select_activity")
def select_activity_tool(average_score: float) -> str:
    """Choose the best game for a child, given their average score on their weakest sound (use -1 if no history)."""
    return json.dumps(T.select_activity(None if average_score < 0 else average_score))


@tool("select_words")
def select_words_tool(phoneme: str, difficulty: int) -> str:
    """Look up practice words in the Word Bank for a sound (r, s or k) and a difficulty level (1 easy to 3 hard)."""
    return json.dumps(T.select_words(phoneme, difficulty))


def build_plan_with_agent(child_id):
    """Ask the LLM agent for a therapy plan. Returns a dict, or None if anything goes wrong."""
    try:
        llm = LLM(model=os.getenv("LLM_MODEL"), api_key=os.getenv("LLM_API_KEY"), temperature=0, timeout=30)
        agent = Agent(
            role="Speech therapy session planner for young children",
            goal="Choose the sound, difficulty and game that will help this child most.",
            backstory="You support a licensed speech therapist. You only use the tools and the data they return.",
            tools=[get_child_progress_tool, select_activity_tool, select_words_tool],
            llm=llm, verbose=False, max_iter=6,
        )
        task = Task(
            description=(
                f"Plan the next practice session for child_id {child_id}.\n"
                "1. Call get_child_progress. Focus on the sound with the LOWEST average score. "
                "If there is no history, use sound r.\n"
                "2. Pick difficulty: average below 50 -> 1, below 75 -> 2, otherwise 3.\n"
                "3. Call select_activity with that sound's average score (-1 if no history).\n"
                "4. Call select_words to confirm words exist for that sound and difficulty.\n"
                'Finish with ONLY this JSON: {"phoneme": "r", "difficulty": 1, "activity": "say_it_and_collect_it", '
                '"reason": "one short sentence for the parent"}'
            ),
            expected_output="A single JSON object with phoneme, difficulty, activity, reason.",
            agent=agent,
        )
        result = Crew(agents=[agent], tasks=[task], verbose=False).kickoff()
        return _validate(str(result.raw))
    except Exception as err:
        print("Agent failed, using fallback:", err)
        return None


def _validate(text):
    """Pull the JSON out of the LLM answer and make sure every value is safe."""
    match = re.search(r"\{.*\}", text, re.DOTALL)
    if not match:
        return None
    data = json.loads(match.group(0))
    known = {w["phoneme"] for w in T.query("SELECT DISTINCT phoneme FROM words")}
    phoneme = str(data.get("phoneme", "")).strip("/ ").lower()
    difficulty = int(data.get("difficulty", 0))
    activity = data.get("activity")
    if phoneme not in known or difficulty not in (1, 2, 3) or activity not in T.ACTIVITIES:
        return None
    return {"phoneme": phoneme, "difficulty": difficulty, "activity": activity,
            "reason": str(data.get("reason", "Chosen by the Therapy Agent."))[:250]}
