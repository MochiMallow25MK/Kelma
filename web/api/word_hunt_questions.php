<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

// db.php may expose the shared PDO instance under a different legacy name.
if (!isset($pdo)) {
    if (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    }
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable'], JSON_UNESCAPED_UNICODE);
    exit;
}

$childId = (int)($_GET['child_id'] ?? $_SESSION['active_child_id'] ?? 0);
assert_child_access($pdo, $childId);

$level = max(1, min(3, (int)($_GET['level'] ?? 1)));
$questions = [];

for ($q = 0; $q < 5; $q++) {
    if ($level === 1) {
        // Level 1: Objects (same-category distractors)
        $cats = ['Animals', 'Fruits', 'Vegetables', 'Everyday Objects'];
        $cat = $cats[array_rand($cats)];
        $stmt = $pdo->prepare("SELECT id, word, emoji, english_meaning, phoneme, category, audio_path FROM words WHERE category = ? ORDER BY RAND() LIMIT 4");
        $stmt->execute([$cat]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $target = $options[0];
        shuffle($options);
        $questions[] = [
            'level'       => 1,
            'prompt_en'   => "Can you find the {$target['english_meaning']}?",
            'prompt_ar'   => "فين صورة: {$target['word']} ؟",
            'speak_text'  => $target['word'],
            'audio_path'  => $target['audio_path'],
            'target_id'   => (int)$target['id'],
            'target_word' => $target['word'],
            'target_en'   => $target['english_meaning'],
            'options'     => $options,
        ];
    } elseif ($level === 2) {
        // Level 2: Actions (Verbs)
        $stmt = $pdo->query("SELECT id, word, emoji, english_meaning, phoneme, category, audio_path FROM words WHERE category = 'Verbs' ORDER BY RAND() LIMIT 4");
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $target = $options[0];
        shuffle($options);
        $questions[] = [
            'level'       => 2,
            'prompt_en'   => "Find the action: {$target['english_meaning']}",
            'prompt_ar'   => "مين بيعمل الفعل ده: {$target['word']} ؟",
            'speak_text'  => $target['word'],
            'audio_path'  => $target['audio_path'],
            'target_id'   => (int)$target['id'],
            'target_word' => $target['word'],
            'target_en'   => $target['english_meaning'],
            'options'     => $options,
        ];
    } else {
        // Level 3: Categories
        $catLabels = [
            'Fruits'     => ['ar' => 'فواكه', 'en' => 'a Fruit'],
            'Vegetables' => ['ar' => 'خضروات', 'en' => 'a Vegetable'],
            'Animals'    => ['ar' => 'حيوانات', 'en' => 'an Animal'],
            'Colours'    => ['ar' => 'ألوان', 'en' => 'a Colour'],
        ];
        $targetCat = array_rand($catLabels);
        $tStmt = $pdo->prepare("SELECT id, word, emoji, english_meaning, phoneme, category, audio_path FROM words WHERE category = ? ORDER BY RAND() LIMIT 1");
        $tStmt->execute([$targetCat]);
        $target = $tStmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($target)) {
            continue;
        }

        $dStmt = $pdo->prepare("SELECT id, word, emoji, english_meaning, phoneme, category, audio_path FROM words WHERE category != ? AND category IN ('Fruits','Vegetables','Animals','Colours') ORDER BY RAND() LIMIT 3");
        $dStmt->execute([$targetCat]);
        $options = array_merge([$target], $dStmt->fetchAll(PDO::FETCH_ASSOC));
        shuffle($options);

        $questions[] = [
            'level'       => 3,
            'prompt_en'   => "Find {$catLabels[$targetCat]['en']}!",
            'prompt_ar'   => "اختار حاجة من الـ {$catLabels[$targetCat]['ar']}!",
            'speak_text'  => $target['word'],
            'audio_path'  => $target['audio_path'],
            'target_id'   => (int)$target['id'],
            'target_word' => $target['word'],
            'target_en'   => $target['english_meaning'],
            'options'     => $options,
        ];
    }
}

echo json_encode(['level' => $level, 'questions' => $questions], JSON_UNESCAPED_UNICODE);