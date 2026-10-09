<?php
// Talks to the Python agent service. Returns null if the service is down or slow,
// so the game keeps working with the simple PHP fallbacks below.
function call_agent($path, $payload, $timeout = 30) {
    $ch = curl_init(AGENT_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $response = curl_exec($ch);
    $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    return ($response && $ok) ? json_decode($response, true) : null;
}

// Fallback word choice: 5 easy words (used when the agent is unavailable)
function fallback_words($db) {
    $rows = $db->query("SELECT id, word, image, phoneme FROM words WHERE difficulty <= 2 ORDER BY RAND() LIMIT 5");
    return $rows->fetch_all(MYSQLI_ASSOC);
}

// Fallback 3-tier failure handling: 1 = retry, 2 = cue, 3 = easier word
function fallback_on_failure($db, $word_id, $fails) {
    if ($fails <= 1) return ['action' => 'retry'];
    if ($fails == 2) return ['action' => 'cue'];
    $stmt = $db->prepare("SELECT phoneme, difficulty FROM words WHERE id = ?");
    $stmt->bind_param('i', $word_id);
    $stmt->execute();
    $w = $stmt->get_result()->fetch_assoc();
    $stmt = $db->prepare("SELECT id, word, image, phoneme FROM words WHERE phoneme = ? AND difficulty <= ? AND id <> ? ORDER BY difficulty, RAND() LIMIT 1");
    $stmt->bind_param('sii', $w['phoneme'], $w['difficulty'], $word_id);
    $stmt->execute();
    $easy = $stmt->get_result()->fetch_assoc();
    return $easy ? ['action' => 'substitute', 'word' => $easy] : ['action' => 'retry'];
}
