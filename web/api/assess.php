<?php
// Receives the child's recording, scores it with Azure Pronunciation Assessment,
// saves BOTH scores (diagnostic + motivational) and returns the result.
require '../includes/config.php';
if (!isset($_SESSION['child_id']) || !isset($_FILES['audio'])) json_out(['error' => 'Bad request'], 400);

$child_id = (int)$_SESSION['child_id'];
$word_id  = (int)($_POST['word_id'] ?? 0);
$fails    = (int)($_POST['fails'] ?? 0);

$stmt = $db->prepare("SELECT word FROM words WHERE id = ?");
$stmt->bind_param('i', $word_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) json_out(['error' => 'Unknown word'], 400);

$demo = false;
if (AZURE_KEY === '') {
    // DEMO mode: no Azure key yet, so make up a score (useful for testing the game flow)
    $demo = true;
    $score = rand(40, 100);
} else {
    $params = base64_encode(json_encode([
        'ReferenceText' => $row['word'], 'GradingSystem' => 'HundredMark',
        'Granularity' => 'Word', 'Dimension' => 'Comprehensive',
    ]));
    $url = 'https://' . AZURE_REGION . '.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language=en-US&format=detailed';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($_FILES['audio']['tmp_name']),
        CURLOPT_HTTPHEADER => [
            'Ocp-Apim-Subscription-Key: ' . AZURE_KEY,
            'Content-Type: audio/wav; codecs=audio/pcm; samplerate=16000',
            'Accept: application/json',
            'Pronunciation-Assessment: ' . $params,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $reply = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // No speech detected or Azure error → score 0 (the child just tries again)
    $score = 0;
    if (($reply['RecognitionStatus'] ?? '') === 'Success') {
        $best = $reply['NBest'][0] ?? [];
        $score = $best['PronunciationAssessment']['AccuracyScore'] ?? ($best['AccuracyScore'] ?? 0);
    }
}

// Two decoupled scores:
//  diagnostic  = the real accuracy score (the agent uses this)
//  motivational = generous stars so the child feels encouraged (never used by the agent)
$passed = $score >= PASS_SCORE ? 1 : 0;
$stars  = $score >= 80 ? 3 : ($score >= 60 ? 2 : ($score >= 30 ? 1 : 0));

$stmt = $db->prepare("INSERT INTO attempts (child_id, word_id, diagnostic_score, motivational_stars, passed, fail_count) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iidiii', $child_id, $word_id, $score, $stars, $passed, $fails);
$stmt->execute();

json_out(['score' => round($score), 'stars' => $stars, 'passed' => (bool)$passed, 'demo' => $demo]);
