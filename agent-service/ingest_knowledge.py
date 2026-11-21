import hashlib
import json
import math
import os
import requests
from dotenv import load_dotenv
from db import _connect

load_dotenv()

# FR10: Manage knowledge cards 

SEED_CARDS = [
    {
        "title": "علاج صوت الراء (/ر/) للأطفال في اللهجة المصرية",
        "topic": "Articulation - Alveolar Trill/Tap",
        "sound": "ر",
        "age_range": "4-8",
        "text": "لتحفيز صوت الراء (ر)، اطلب من الطفل رفع طرف اللسان خلف الأسنان العلوية مع اهتزاز خفيف. ابدأ بكلمات مألوفة مثل رُمَّان وجَزَر وخَرُوف، واستخدم التلقين البصري والسمعي قبل الانتقال إلى جمل قصيرة.",
        "source_ref": "ASHA Practice Portal: Speech Sound Disorders - Articulation & Phonology (Adapted for Egyptian Arabic)",
    },
    {
        "title": "التمييز بين السين (س) والصاد (ص) المفخمة",
        "topic": "Phonological Contrast - Emphatics",
        "sound": "ص",
        "age_range": "4-8",
        "text": "الأصوات المفخمة مثل (ص) تحتاج إلى تقعير مؤخرة اللسان مقارنة بالسين (س). درّب الطفل على التمييز السمعي أولاً عبر لعبة البحث عن الصور (Word Hunt) باستخدام كلمات مثل أَصْفَر وعَصْفُور وبَصَل.",
        "source_ref": "Egyptian Arabic Phonological Acquisition Guidelines (Amayreh & Dyson, 1998; adapted)",
    },
    {
        "title": "نطق الأصوات الاحتكاكية الخلفية: الخاء (خ) والغين (غ)",
        "topic": "Articulation - Velar/Uvular Fricatives",
        "sound": "خ",
        "age_range": "4-8",
        "text": "يُنتج صوت الخاء (خ) بتقريب مؤخرة اللسان من الحنك الرخو مع مرور الهواء. ابدأ بكلمات محببة للأطفال مثل خِيَار وخُوخ وخَرُوف في المستوى السهل قبل الأفعال مثل خَرَج وطَبَخ.",
        "source_ref": "Clinical Phonetics for Arabic Speech Therapy (McLeod, 2007)",
    },
    {
        "title": "تدريب صوت الشين (ش) في بداية ووسط الكلمة",
        "topic": "Articulation - Postalveolar Fricative",
        "sound": "ش",
        "age_range": "4-8",
        "text": "لتدريب صوت الشين (ش)، شجّع الطفل على تدوير الشفتين قليلاً مع إخراج تيار هواء مستمر. استخدم كلمات بصرية واضحة مثل شُبَّاك وفَرَاشَة وشَمَّام، ثم أفعالاً مثل شِرِب ومِشِي.",
        "source_ref": "ASHA Speech Sound Disorders Clinical Protocol",
    },
    {
        "title": "الأصوات الانفجارية المفخمة: الطاء (ط) والضاد (ض)",
        "topic": "Articulation - Emphatic Stops",
        "sound": "ط",
        "age_range": "5-8",
        "text": "ابدأ بتدريب صوت الطاء (ط) في كلمات مألوفة للطفل مثل قُطَّة وبَطَّة وطَمَاطِم وطَبَق. إذا أخطأ الطفل مرتين، قدّم تلميحاً حركياً لمكان اللسان، وإذا استمر الخطأ انتقل لكلمة أسهل من نفس الصوت.",
        "source_ref": "Hierarchical Cueing & Errorless Learning in Pediatric SSD (Rvachew & Brosseau-Lapré)",
    },
    {
        "title": "تنمية المفردات والتصنيف الدلالي عبر الألعاب التفاعلية",
        "topic": "Semantic Categorization & Receptive Vocabulary",
        "sound": "ALL",
        "age_range": "4-8",
        "text": "التدرج من تسمية الأشياء اليومية والحيوانات (المستوى 1) إلى الأفعال (المستوى 2) ثم التصنيف الدلالي للفواكه والخضروات والألوان (المستوى 3) يعزز الاستيعاب اللغوي قبل الإنتاج النطقي.",
        "source_ref": "Pediatric Language Intervention Framework (Paul & Norbury)",
    },
]


def local_hash_embedding(text: str, dim: int = 64) -> list[float]:
    vec = [0.0] * dim
    tokens = text.split()
    for tok in tokens:
        h = int(hashlib.sha256(tok.encode("utf-8")).hexdigest(), 16)
        for i in range(dim):
            bit = (h >> (i % 64)) & 1
            vec[i] += 1.0 if bit else -1.0
    norm = math.sqrt(sum(v * v for v in vec)) or 1.0
    return [round(v / norm, 6) for v in vec]


def get_embedding(text: str) -> tuple[list[float], str]:
    api_key = os.getenv("GEMINI_API_KEY", "").strip()
    if api_key:
        try:
            url = f"https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={api_key}"
            resp = requests.post(
                url,
                json={"model": "models/text-embedding-004", "content": {"parts": [{"text": text}]}},
                timeout=10,
            )
            if resp.status_code == 200:
                vals = resp.json()["embedding"]["values"]
                return vals, "gemini-text-embedding-004"
        except Exception:
            pass
    return local_hash_embedding(text), "local-hash-64d"

# FR11: Embed and index knowledge cards

def ingest():
    conn = _connect()
    try:
        with conn.cursor() as cur:
            for c in SEED_CARDS:
                emb, model_name = get_embedding(f"{c['title']} {c['sound']} {c['text']}")
                cur.execute("SELECT id FROM knowledge_cards WHERE title = %s", (c["title"],))
                existing = cur.fetchone()
                if existing:
                    cur.execute(
                        """
                        UPDATE knowledge_cards
                        SET topic=%s, sound=%s, age_range=%s, text=%s, source_ref=%s, embedding=%s, embedding_model=%s
                        WHERE id=%s
                        """,
                        (
                            c["topic"],
                            c["sound"],
                            c["age_range"],
                            c["text"],
                            c["source_ref"],
                            json.dumps(emb),
                            model_name,
                            existing["id"],
                        ),
                    )
                else:
                    cur.execute(
                        """
                        INSERT INTO knowledge_cards
                        (title, topic, sound, age_range, text, source_ref, embedding, embedding_model)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                        """,
                        (
                            c["title"],
                            c["topic"],
                            c["sound"],
                            c["age_range"],
                            c["text"],
                            c["source_ref"],
                            json.dumps(emb),
                            model_name,
                        ),
                    )
        conn.commit()
        print(f"Ingested {len(SEED_CARDS)} knowledge cards successfully.")
    finally:
        conn.close()


if __name__ == "__main__":
    ingest()