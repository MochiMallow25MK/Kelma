import glob
import os
import re
import sys
import pandas as pd
import pymysql
from dotenv import load_dotenv

load_dotenv()

EMOJI_MAP = {
    # Verbs
    "drank": "🥤", "saw": "👀", "walked": "🚶", "went out": "🚪", "entered": "🏠",
    "cooked": "🍳", "washed": "🧼", "dyed / colored": "🎨", "woke up": "⏰", "prayed": "🤲",
    "looked": "🧐", "cut (with scissors)": "✂️", "laughed": "😂", "hit": "🥊", "bit": "😬",
    "flew": "🦅", "went up": "⬆️", "cried": "😢", "put": "📥", "jumped": "🦘",
    "cleaned": "🧹", "shouted": "🗣️", "played": "⚽", "heard": "👂", "fell": "🤕",
    "opened": "🔓", "wiped": "🧽", "swam": "🏊", "hugged": "🤗", "closed": "🔒",
    "wrote": "✍️", "rode": "🚴", "held": "✊", "ran": "🏃", "brought": "🎁",
    "drew": "🖌️", "broke": "💔", "slept": "😴", "colored": "🖍️", "asked": "❓",
    "wore": "👕", "kissed": "😘", "carried": "📦", "threw": "🤾", "swam / floated": "🏊‍♂️",
    "ate": "🍽️", "took": "🫳", "pushed": "🫸", "sat": "🪑", "danced": "💃",
    # Adjectives
    "small": "🐜", "cheap": "🏷️", "weak": "🥀", "clean": "✨", "wide": "↔️",
    "tall / long": "🦒", "soft": "🧸", "slow": "🐢", "happy": "😊", "bad / ugly": "👎",
    "dry": "🏜️", "thirsty": "🥵", "light (weight)": "🪶", "rough": "🪨", "thick / fat": "🐘",
    "hot": "🔥", "dirty": "💩", "expensive": "💎", "busy": "📅", "clever / good": "🌟",
    "full (not hungry)": "😋", "fast": "⚡", "cold": "🧊", "thin": "📏", "sweet / nice": "🍬",
    "spicy": "🌶️", "open": "📖", "sick": "🤒", "big": "🐋", "lazy": "🦥",
    "sweet / sugary": "🍭", "sad / upset": "😞", "sour / tart": "🍋", "new": "🆕", "hungry": "🤤",
    "old": "📜", "strong": "💪", "short": "🐭", "near": "📍", "far": "🔭",
    "tired": "😫", "sleepy": "🥱", "full": "🥛", "smooth": "🧈", "heavy": "🏋️",
    "high": "🏔️", "closed": "📕", "scared": "😨", "many / a lot": "🔢", "empty": "🫙",
    # Colours
    "red": "🔴", "blue": "🔵", "green": "🟢", "yellow": "🟡", "black": "⚫",
    "white": "⚪", "orange": "🟠", "purple": "🟣", "brown": "🟤", "gray": "🩶",
    "slate gray": "🪨", "pink / rose": "🌸", "pink (colloquial)": "🎀", "light blue": "🩵",
    "sky blue": "☁️", "navy blue": "🌌", "olive green": "🫒", "gold": "🥇", "silver": "🥈",
    "honey brown": "🍯", "coffee brown": "☕", "burgundy": "🍷", "pistachio green": "🥜",
    "turquoise": "🦚", "mauve": "🪻", "fuchsia": "🌺", "lemon yellow": "🍋",
    "brick red": "🧱", "cream": "🍦", "wheat-toned": "🌾",
    # Animals
    "cat": "🐱", "dog": "🐶", "lion": "🦁", "tiger": "🐯", "elephant": "🐘",
    "giraffe": "🦒", "monkey": "🐒", "horse": "🐴", "donkey": "🫏", "cow": "🐮",
    "buffalo": "🐃", "sheep": "🐑", "goat": "🐐", "camel": "🐪", "rabbit": "🐰",
    "mouse": "🐭", "duck": "🦆", "goose": "🪿", "chicken": "🐔", "rooster": "🐓",
    "sparrow / bird": "🐦", "pigeon": "🕊️", "owl": "🦉", "eagle": "🦅", "parrot": "🦜",
    "fish": "🐟", "whale": "🐳", "dolphin": "🐬", "shark": "🦈", "crocodile": "🐊",
    "turtle": "🐢", "frog": "🐸", "snake": "🐍", "bear": "🐻", "wolf": "🐺",
    "fox": "🦊", "gazelle": "🦌", "pig": "🐷", "bee": "🐝", "fly": "🪰",
    "ant": "🐜", "butterfly": "🦋", "spider": "🕷️", "cockroach": "🪳", "worm": "🪱",
    "penguin": "🐧", "kangaroo": "🦘", "bat": "🦇", "bat (colloquial)": "🧛", "hedgehog": "🦔",
    # Fruits
    "apple": "🍎", "banana": "🍌", "tangerine": "🍊", "strawberry": "🍓",
    "grapes": "🍇", "watermelon": "🍉", "sweet melon": "🍈", "cantaloupe": "🍈", "mango": "🥭",
    "peach": "🍑", "apricot": "🟠", "plum": "🫐", "pear": "🍐", "guava": "🍐",
    "pomegranate": "🍎", "fig": "🫒", "fresh dates": "🌴", "dried dates": "🤎", "kiwi": "🥝",
    "pineapple": "🍍", "cherry": "🍒", "mulberry / berry": "🫐", "persimmon": "🍅",
    "custard apple": "🍏", "cape gooseberry": "🟡", "avocado": "🥑", "doum fruit": "🥥",
    "jujube": "🍒", "raisins": "🍇", "sycamore fig": "🍈", "pomelo": "🍋",
    "papaya": "🥭", "prickly pear": "🌵", "carob": "🍫",
    # Vegetables
    "tomatoes": "🍅", "cucumber": "🥒", "potatoes": "🥔", "sweet potato": "🍠", "carrots": "🥕",
    "onions": "🧅", "garlic": "🧄", "pepper": "🫑", "chili pepper": "🌶️", "eggplant": "🍆",
    "zucchini": "🥒", "okra": "🥬", "molokhia": "🌿", "spinach": "🥬", "arugula": "🌱",
    "lettuce": "🥬", "parsley": "🌿", "coriander": "🌿", "dill": "🌾", "cabbage": "🥬",
    "cauliflower": "🥦", "broccoli": "🥦", "peas": "🫛", "green beans": "🫛",
    "black-eyed peas": "🫘", "fava beans": "🫘", "corn": "🌽", "radish": "🥕", "turnip": "🥔",
    "beetroot": "🟣", "taro": "🥔", "mushroom": "🍄", "celery": "🥬", "lemon": "🍋", "olives": "🫒",
    # Everyday Objects
    "door": "🚪", "window": "🪟", "chair": "🪑", "table": "🛋️", "bed": "🛏️",
    "wardrobe": "🗄️", "pillow": "🛌", "blanket": "🧣", "TV": "📺", "phone": "📱",
    "clock / watch": "⏰", "lamp": "💡", "fan": "🌀", "air conditioner": "❄️",
    "refrigerator": "🧊", "stove": "🔥", "washing machine": "🧺", "plate": "🍽️",
    "cup / glass": "🥤", "spoon": "🥄", "fork": "🍴", "knife": "🔪", "cooking pot": "🍲",
    "tray": "🍱", "bottle": "🧴", "book": "📚", "notebook": "📓", "pen / pencil": "✏️",
    "eraser": "🧽", "sharpener": "🪒", "ruler": "📏", "scissors": "✂️", "bag": "🎒",
    "shoes": "👟", "socks": "🧦", "t-shirt": "👕", "pants": "👖", "dress": "👗",
    "hat / cap": "🧢", "glasses": "👓", "key": "🔑", "mirror": "🪞", "towel": "🧻",
    "soap": "🧼", "brush": "🪥", "toothpaste": "🪥", "comb": "💇", "ball": "⚽",
    "car": "🚗", "bicycle": "🚲",
}

TASHKEEL_RE = re.compile(r"[\u064B-\u065F\u0670]")

def strip_tashkeel(text: str) -> str:
    return TASHKEEL_RE.sub("", text).strip()

def detect_position(word: str, phoneme: str) -> str:
    bare_word = strip_tashkeel(word)
    bare_ph = strip_tashkeel(phoneme.split()[0])
    if not bare_word or not bare_ph:
        return "initial"
    if bare_word.startswith(bare_ph):
        return "initial"
    if bare_word.endswith(bare_ph):
        return "final"
    return "medial"

def detect_difficulty(word: str, category: str) -> int:
    bare_len = len(strip_tashkeel(word))
    if category in ("Animals", "Fruits", "Vegetables", "Colours") and bare_len <= 4:
        return 1
    if category in ("Verbs", "Everyday Objects") or bare_len == 5:
        return 2
    return 3

def get_conn():
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASS", ""),
        database=os.getenv("DB_NAME", "kelma"),
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )

def import_folder(folder_path: str):
    files = sorted(glob.glob(os.path.join(folder_path, "*.xlsx")))
    if not files:
        print(f"No .xlsx files found in {folder_path}")
        sys.exit(1)

    loaded = skipped = rejected = 0
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            for fp in files:
                fname = os.path.basename(fp)
                m = re.match(r"^[A-G]\.\s*(.+?)\s*—", fname)
                category = m.group(1) if m else "Everyday Objects"
                xls = pd.ExcelFile(fp)
                df = pd.read_excel(fp, sheet_name=xls.sheet_names[0])

                for _, row in df.iterrows():
                    ar_word = str(row.get("Egyptian Arabic", "")).strip()
                    en_word = str(row.get("English", "")).strip()
                    phoneme = str(row.get("Target Phoneme", "")).strip()

                    if not ar_word or ar_word == "nan" or not phoneme or phoneme == "nan":
                        rejected += 1
                        continue

                    cur.execute("SELECT id FROM words WHERE word = %s AND category = %s", (ar_word, category))
                    if cur.fetchone():
                        skipped += 1
                        continue

                    emoji = EMOJI_MAP.get(en_word, "🌟")
                    pos = detect_position(ar_word, phoneme)
                    diff = detect_difficulty(ar_word, category)
                    cue = f"انطق صوت ({phoneme}) بوضوح في كلمة {ar_word}"
                    audio_path = "/kelma/web/assets/audio/batteekh.wav" if strip_tashkeel(ar_word) == "بطيخ" else None

                    cur.execute(
                        """
                        INSERT INTO words
                        (word, emoji, english_meaning, phoneme, position, difficulty, category, cue, audio_path, language, source_file)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 'Egyptian Arabic', %s)
                        """,
                        (ar_word, emoji, en_word, phoneme, pos, diff, category, cue, audio_path, fname),
                    )
                    loaded += 1

            # Also add sample training word مهلبية (from sample audio) if not present
            cur.execute("SELECT id FROM words WHERE word = %s", ("مَهَلَّبِيَّة",))
            if not cur.fetchone():
                cur.execute(
                    """
                    INSERT INTO words
                    (word, emoji, english_meaning, phoneme, position, difficulty, category, cue, audio_path, language, source_file)
                    VALUES (%s, '🍮', 'mahalabeya', 'هـ', 'medial', 2, 'Everyday Objects', %s, %s, 'Egyptian Arabic', 'Sample Audio')
                    """,
                    ("مَهَلَّبِيَّة", "انطق صوت (هـ) في وسط كلمة مَهَلَّبِيَّة", "/kelma/web/assets/audio/mahalabeya.wav"),
                )
                loaded += 1

        conn.commit()
    finally:
        conn.close()

    print(f"Import Complete -> Loaded: {loaded} | Skipped (duplicates): {skipped} | Rejected: {rejected}")

if __name__ == "__main__":
    target = sys.argv[1] if len(sys.argv) > 1 else "../datasets"
    import_folder(target)