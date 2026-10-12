<?php
// Receives the child's recording, scores it (see includes/scoring.php),
// saves BOTH scores (diagnostic + motivational) and returns the result.
require '../includes/config.php'; require '../includes/scoring.php';
if (!isset($_SESSION['child_id']) || !isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    json_out(['error' => 'Bad request'], 400);
}

$child_id = (int)$_SESSION['child_id'];
$word_id  = (int)($_POST['word_id'] ?? 0);
$fails    = (int)($_POST['fails'] ?? 0);

$stmt = $db->prepare("SELECT word FROM words WHERE id = ?");
$stmt->bind_param('i', $word_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) json_out(['error' => 'Unknown word'], 400);

$result = score_audio($_FILES['audio']['tmp_name'], $row['word']);
if (isset($result['error'])) json_out(['error' => $result['error']], 503);   // not the child's fault, so nothing is saved
$score = $result['score'];

// Two decoupled scores:
//  diagnostic   = the real accuracy score (the agent uses this)
//  motivational = generous stars so the child feels encouraged (never used by the agent)
$passed = $score >= PASS_SCORE ? 1 : 0;
$stars  = $score >= 80 ? 3 : ($score >= 60 ? 2 : ($score >= 30 ? 1 : 0));

$stmt = $db->prepare("INSERT INTO attempts (child_id, word_id, diagnostic_score, motivational_stars, passed, fail_count) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iidiii', $child_id, $word_id, $score, $stars, $passed, $fails);
$stmt->execute();

json_out(['score' => round($score), 'stars' => $stars, 'passed' => (bool)$passed,
          'method' => $result['method'], 'demo' => $result['method'] === 'demo', 'heard' => $result['heard']]);
