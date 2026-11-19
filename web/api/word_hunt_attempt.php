<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_guard.php';

global $pdo;

header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$childId = (int)($data['child_id'] ?? 0);
$wordId  = (int)($data['word_id'] ?? 0);
$correct = !empty($data['correct']) ? 1 : 0;

assert_child_access($pdo, $childId);

$diagScore = $correct ? 100.0 : 0.0;
$stars     = $correct ? 2 : 0; // +1 bonus star if child says the word aloud (FR-23)

$ins = $pdo->prepare("
    INSERT INTO attempts (child_id, word_id, activity, attempt_type, diagnostic_score, stars, passed, fail_count)
    VALUES (?, ?, 'word_hunt', 'tap', ?, ?, ?, ?)
");
$ins->execute([$childId, $wordId, $diagScore, $stars, $correct, $correct ? 0 : 1]);

$tot = $pdo->prepare('SELECT COALESCE(SUM(stars), 0) FROM attempts WHERE child_id = ?');
$tot->execute([$childId]);

echo json_encode([
    'ok'          => true,
    'passed'      => (bool)$correct,
    'stars'       => $stars,
    'total_stars' => (int)$tot->fetchColumn(),
]);