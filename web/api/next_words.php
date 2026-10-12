<?php
// Asks the Therapy Agent which words the child should practise next.
// If the agent service is off or slow, we fall back to 5 easy words.
require '../includes/config.php'; require '../includes/agent.php';
if (!isset($_SESSION['child_id'])) json_out(['error' => 'Not logged in'], 401);
$child_id = (int)$_SESSION['child_id'];

$plan = call_agent('/plan', ['child_id' => $child_id], 45);

if (!$plan || empty($plan['words'])) {
    $plan = ['source' => 'fallback', 'activity' => 'say_it_and_collect_it', 'phoneme' => 'mixed', 'difficulty' => 1,
             'reason' => 'Agent unavailable, so easy practice words were chosen.', 'words' => fallback_words($db)];
    $stmt = $db->prepare("INSERT INTO agent_logs (child_id, activity, phoneme, difficulty, reason, source) VALUES (?, ?, 'mixed', 1, ?, 'fallback')");
    $stmt->bind_param('iss', $child_id, $plan['activity'], $plan['reason']);
    $stmt->execute();
}
json_out($plan);
