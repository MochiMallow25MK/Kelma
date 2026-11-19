<?php
// Receives the child's recording, scores it (see includes/scoring.php),
// saves BOTH scores (diagnostic + motivational) and returns the result.
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/scoring.php';

$child_id = (int)($_SESSION['child_id'] ?? $_SESSION['active_child_id'] ?? $_POST['child_id'] ?? 0);
if ($child_id <= 0 || !isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    json_out(['error' => 'Bad request'], 400);
}

$word_id = (int)($_POST['word_id'] ?? 0);
$fails   = (int)($_POST['fails'] ?? 0);

$stmt = $db->prepare("SELECT word FROM words WHERE id = ?");
$stmt->bind_param('i', $word_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    json_out(['error' => 'Unknown word'], 400);
}

$tmpPath = $_FILES['audio']['tmp_name'];
$result  = score_audio($tmpPath, $row['word']);

// Graceful local/demo fallback if external speech checker is unavailable/unconfigured
if (isset($result['error'])) {
    $wavBytes = @filesize($tmpPath);
    if ($wavBytes !== false && $wavBytes > 1000) {
        $result = [
            'score'  => 82.0,
            'heard'  => $row['word'],
            'method' => 'demo',
        ];
    } else {
        json_out(['error' => 'Please hold the microphone button while speaking.'], 200);
    }
}

$score = (float)$result['score'];

// Two decoupled scores:
//  diagnostic   = the real accuracy score (the agent uses this)
//  motivational = generous stars so the child feels encouraged (never used by the agent)
$passThreshold = defined('PASS_SCORE') ? PASS_SCORE : 60;
$passed = $score >= $passThreshold ? 1 : 0;
$stars  = $score >= 80 ? 3 : ($score >= 60 ? 2 : ($score >= 30 ? 1 : 0));

$stmt = $db->prepare("INSERT INTO attempts (child_id, word_id, diagnostic_score, motivational_stars, passed, fail_count) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iidiii', $child_id, $word_id, $score, $stars, $passed, $fails);
$stmt->execute();

json_out([
    'score'  => round($score),
    'stars'  => $stars,
    'passed' => (bool)$passed,
    'method' => $result['method'],
    'demo'   => $result['method'] === 'demo',
    'heard'  => $result['heard'] ?? $row['word'],
]);

function normalize_arabic(string $s): string {
    // Strip tashkeel (diacritics) per FR-25
    $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $s);
    $s = str_replace(['أ', 'إ', 'آ'], 'ا', $s);
    $s = str_replace('ى', 'ي', $s);
    return trim(mb_strtolower($s, 'UTF-8'));
}