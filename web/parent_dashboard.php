<?php
$title = 'Parent Dashboard';
require __DIR__ . '/includes/config.php';
require_role('parent');

$parent_id = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$child_id  = (int)($_GET['child_id'] ?? $_SESSION['child_id'] ?? $_SESSION['active_child_id'] ?? 0);

// Resolve child from DB if not in session yet
if ($child_id <= 0 && $parent_id > 0) {
    $cRes = $db->query("SELECT id, name, full_name FROM children WHERE parent_id = $parent_id ORDER BY id ASC LIMIT 1");
    if ($cRes && ($cRow = $cRes->fetch_assoc())) {
        $child_id = (int)$cRow['id'];
        $_SESSION['child_id'] = $child_id;
        $_SESSION['active_child_id'] = $child_id;
        $_SESSION['child_name'] = $cRow['full_name'] ?: $cRow['name'];
    }
}

$display_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Parent';
$child_name   = $_SESSION['child_name'] ?? 'Child';

if ($child_id > 0) {
    $cnRes = $db->query("SELECT name, full_name FROM children WHERE id = $child_id LIMIT 1");
    if ($cnRes && ($cnRow = $cnRes->fetch_assoc())) {
        $child_name = $cnRow['full_name'] ?: $cnRow['name'];
        $_SESSION['child_name'] = $child_name;
    }
}

// Simple progress numbers from the attempts table
$s = ['total' => 0, 'mastered' => 0, 'accuracy' => 0, 'stars' => 0];
if ($child_id > 0) {
    $q = $db->query("SELECT COUNT(*) AS total, COALESCE(SUM(passed),0) AS mastered,
                     COALESCE(ROUND(AVG(diagnostic_score)),0) AS accuracy,
                     COALESCE(SUM(motivational_stars),0) AS stars FROM attempts WHERE child_id = $child_id");
    if ($q) {
        $s = $q->fetch_assoc();
    }
}

$plan = null;
if ($child_id > 0) {
    $pq = $db->query("SELECT * FROM agent_logs WHERE child_id = $child_id ORDER BY id DESC LIMIT 1");
    if ($pq) {
        $plan = $pq->fetch_assoc();
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h1>Welcome, <?= e($display_name) ?> 👋</h1>
  <p>
    Child: <span class="badge"><?= e($child_name) ?></span>
    &nbsp;
    <a class="btn btn-yellow" href="/kelma/kelma/web/parent/child_profile.php?child_id=<?= $child_id ?>">👶 Child Profile</a>
    <a class="btn btn-mint" href="/kelma/kelma/web/games/hub.php?child_id=<?= $child_id ?>">🎮 Kids Hub</a>
  </p>

  <div class="grid-3">
    <div class="card stat t-blue"><div class="num"><?= (int)$s['total'] ?></div>Practice attempts</div>
    <div class="card stat t-mint"><div class="num"><?= (int)$s['mastered'] ?></div>Words said correctly</div>
    <div class="card stat t-yellow"><div class="num"><?= (int)$s['accuracy'] ?>%</div>Accuracy average</div>
  </div>
  <p>Motivational score earned: <b><?= (int)$s['stars'] ?> ⭐ stars</b></p>

  <div class="card t-coral">
    <h3>💡 Recommended by the AI Therapy Agent</h3>
    <?php if ($plan): ?>
      <p><?= e($plan['reason']) ?> (Target sound: /<?= e($plan['phoneme']) ?>/, level <?= (int)$plan['difficulty'] ?>)</p>
    <?php else: ?>
      <p>No plan yet. Start a game and the agent will choose the first words.</p>
    <?php endif; ?>
    <a class="btn btn-coral btn-big" href="/kelma/kelma/web/games/say-it.php?child_id=<?= $child_id ?>">Play: Say It &amp; Collect It</a>
    &nbsp;
    <a class="btn btn-mint btn-big" href="/kelma/kelma/web/games/word-hunt.php?child_id=<?= $child_id ?>">Play: Word Hunt</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>