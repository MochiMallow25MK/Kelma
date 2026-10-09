<?php
$title = 'Parent Dashboard'; require 'includes/config.php'; require_role('parent');
$child_id = (int)($_SESSION['child_id'] ?? 0);

// Simple progress numbers from the attempts table
$s = $db->query("SELECT COUNT(*) AS total, COALESCE(SUM(passed),0) AS mastered,
                 COALESCE(ROUND(AVG(diagnostic_score)),0) AS accuracy,
                 COALESCE(SUM(motivational_stars),0) AS stars FROM attempts WHERE child_id = $child_id")->fetch_assoc();
$plan = $db->query("SELECT * FROM agent_logs WHERE child_id = $child_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
require 'includes/header.php';
?>
<div class="container">
  <h1>Welcome, <?= e($_SESSION['name']) ?> 👋</h1>
  <p>Child: <span class="badge"><?= e($_SESSION['child_name']) ?></span></p>

  <div class="grid-3">
    <div class="card stat t-blue"><div class="num"><?= $s['total'] ?></div>Practice attempts</div>
    <div class="card stat t-mint"><div class="num"><?= $s['mastered'] ?></div>Words said correctly</div>
    <div class="card stat t-yellow"><div class="num"><?= $s['accuracy'] ?>%</div>Accuracy average (diagnostic)</div>
  </div>
  <p>Motivational score earned: <b><?= $s['stars'] ?> ⭐ stars</b> (kept separate from the accuracy score)</p>

  <div class="card t-coral">
    <h3>💡 Recommended by the AI Therapy Agent</h3>
    <?php if ($plan): ?>
      <p><?= e($plan['reason']) ?> (Target sound: /<?= e($plan['phoneme']) ?>/, level <?= (int)$plan['difficulty'] ?>)</p>
    <?php else: ?>
      <p>No plan yet. Start a game and the agent will choose the first words.</p>
    <?php endif; ?>
    <a class="btn btn-coral btn-big" href="/kelma/web/games/say-it.php">▶ Start Play: Say It &amp; Collect It</a>
  </div>
  <p><i>More games, charts, and activity suggestions arrive in later milestones.</i></p>
</div>
<?php require 'includes/footer.php'; ?>
