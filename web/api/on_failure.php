<?php
// 3-tier failure handling: 1st = retry, 2nd = simplified cue, 3rd = easier word.
require '../includes/config.php'; require '../includes/agent.php';
if (!isset($_SESSION['child_id'])) json_out(['error' => 'Not logged in'], 401);

$word_id = (int)($_POST['word_id'] ?? 0);
$fails   = (int)($_POST['fails'] ?? 1);

$result = call_agent('/on_failure', ['word_id' => $word_id, 'fails' => $fails], 5)
          ?? fallback_on_failure($db, $word_id, $fails);
json_out($result);
