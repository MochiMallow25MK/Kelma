<?php
$title = 'Therapist Dashboard'; require 'includes/config.php'; require_role('therapist');

$attempts = $db->query("SELECT c.name AS child, w.word, w.phoneme, a.diagnostic_score, a.passed, a.created_at
                        FROM attempts a JOIN children c ON c.id = a.child_id JOIN words w ON w.id = a.word_id
                        ORDER BY a.id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$plans = $db->query("SELECT c.name AS child, g.activity, g.phoneme, g.difficulty, g.reason, g.source
                     FROM agent_logs g JOIN children c ON c.id = g.child_id ORDER BY g.id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
require 'includes/header.php';
?>
<div class="container">
  <h1>Therapist Clinical Portal 🧑‍⚕️</h1>
  <p>Welcome, <?= e($_SESSION['name']) ?>.</p>

  <h2>Recent AI Agent Decisions</h2>
  <table>
    <tr><th>Child</th><th>Activity</th><th>Sound</th><th>Level</th><th>Reason</th><th>Source</th></tr>
    <?php foreach ($plans as $p): ?>
      <tr><td><?= e($p['child']) ?></td><td><?= e($p['activity']) ?></td><td>/<?= e($p['phoneme']) ?>/</td>
          <td><?= (int)$p['difficulty'] ?></td><td><?= e($p['reason']) ?></td><td><?= e($p['source']) ?></td></tr>
    <?php endforeach; if (!$plans) echo '<tr><td colspan="6">No agent decisions yet.</td></tr>'; ?>
  </table>
  <p><i>Approve / Modify / Reject controls arrive in a later milestone.</i></p>

  <h2>Recent Attempts</h2>
  <table>
    <tr><th>Child</th><th>Word</th><th>Sound</th><th>Diagnostic score</th><th>Correct?</th><th>When</th></tr>
    <?php foreach ($attempts as $a): ?>
      <tr><td><?= e($a['child']) ?></td><td><?= e($a['word']) ?></td><td>/<?= e($a['phoneme']) ?>/</td>
          <td><?= e($a['diagnostic_score']) ?></td><td><?= $a['passed'] ? '✅' : '❌' ?></td><td><?= e($a['created_at']) ?></td></tr>
    <?php endforeach; if (!$attempts) echo '<tr><td colspan="6">No attempts yet.</td></tr>'; ?>
  </table>
</div>
<?php require 'includes/footer.php'; ?>
