<?php
require_once __DIR__ . '/../includes/config.php';
require_role('parent');

$parentId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$childId  = (int)($_GET['child_id'] ?? $_SESSION['child_id'] ?? $_SESSION['active_child_id'] ?? 0);

// Verify child belongs to parent (or fall back to parent's first child)
$child = null;
if ($childId > 0) {
    $stmt = $db->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
    $stmt->bind_param('ii', $childId, $parentId);
    $stmt->execute();
    $child = $stmt->get_result()->fetch_assoc();
}
if (!$child && $parentId > 0) {
    $stmt = $db->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $child = $stmt->get_result()->fetch_assoc();
}
if (!$child) {
    header('Location: /kelma/kelma/web/parent/child_profile.php');
    exit;
}

$childId = (int)$child['id'];
$_SESSION['child_id'] = $childId;
$_SESSION['active_child_id'] = $childId;
$_SESSION['child_name'] = $child['full_name'] ?? $child['name'];

// Total stars earned across all games (FR-27: stars only, no coins)
$starStmt = $db->prepare('SELECT COALESCE(SUM(motivational_stars), 0) AS total_stars FROM attempts WHERE child_id = ?');
$starStmt->bind_param('i', $childId);
$starStmt->execute();
$totalStars = (int)($starStmt->get_result()->fetch_assoc()['total_stars'] ?? 0);

// Latest Agentic RAG recommendation
$planStmt = $db->prepare('SELECT * FROM agent_logs WHERE child_id = ? ORDER BY id DESC LIMIT 1');
$planStmt->bind_param('i', $childId);
$planStmt->execute();
$latestPlan = $planStmt->get_result()->fetch_assoc();

$recActivity = $latestPlan['activity'] ?? 'say_it_and_collect_it';
$recSound    = $latestPlan['phoneme'] ?? 'ر';
$recReason   = $latestPlan['reason'] ?? "يلا نتدرب على صوت ({$recSound}) بكلمات مصرية ممتعة!";
$recUrl      = ($recActivity === 'word_hunt')
    ? "/kelma/kelma/web/games/word-hunt.php?child_id={$childId}"
    : "/kelma/kelma/web/games/say-it.php?child_id={$childId}";
$recGameName = ($recActivity === 'word_hunt') ? 'Word Hunt' : 'Say It & Collect It';
$favColor    = htmlspecialchars($child['favourite_color'] ?? '#118AB2');
$childDisplayName = $child['full_name'] ?? $child['name'] ?? 'Hero';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kelma — Child Learning Hub</title>
  <link rel="stylesheet" href="/kelma/kelma/web/assets/css/kelma-kids.css">
</head>
<body class="kelma-kids">
  <div class="topbar-kids" style="border-bottom-color: <?= $favColor ?>;">
    <a href="/kelma/kelma/web/parent_dashboard.php" class="btn-pill btn-coral">← Back to Parent Dashboard</a>
    <div class="star-badge">⭐ Stars: <?= $totalStars ?></div>
  </div>

  <div class="container-kids">
    <div style="text-align:center;">
      <div style="font-size:4.2rem;"><?= htmlspecialchars($child['pfp'] ?? '🦁') ?></div>
      <h1 style="font-size:2.3rem;margin:6px 0;color:<?= $favColor ?>;">
        WELCOME BACK, <?= strtoupper(htmlspecialchars($childDisplayName)) ?> ! 👋
      </h1>
      <p style="font-size:1.25rem;font-weight:700;">Which game do you want to play today ? </p>
    </div>

    <div class="game-grid-3">
      <div class="game-card say-it">
        <div style="font-size:3.5rem;">🗣️</div>
        <h2>SAY IT &amp; COLLECT IT</h2>
        <p>Practice target sounds &amp; Egyptian Arabic words!</p>
        <a href="/kelma/kelma/web/games/say-it.php?child_id=<?= $childId ?>" class="btn-pill btn-coral">Play Now ▶️</a>
      </div>

      <div class="game-card word-hunt">
        <div style="font-size:3.5rem;">🔍</div>
        <h2>WORD HUNT</h2>
        <p>Find objects, actions &amp; categories + say aloud for bonus stars!</p>
        <a href="/kelma/kelma/web/games/word-hunt.php?child_id=<?= $childId ?>" class="btn-pill btn-mint">Play Now ▶️</a>
      </div>

      <div class="game-card rocket-locked">
        <div style="font-size:3.5rem;">🚀</div>
        <h2>SPEECH ROCKET</h2>
        <p>Say words to fill the fuel meter &amp; launch!</p>
        <span class="btn-pill btn-yellow" style="cursor:not-allowed;">🔒 Coming in Milestone 3</span>
      </div>
    </div>

    <div class="mission-banner">
      <div>
        <div style="font-weight:900;color:#118AB2;font-size:1.05rem;">💡 AI THERAPY AGENT RECOMMENDATION (Agentic RAG)</div>
        <h3 style="margin:6px 0;">Today's Special Mission: Practice Sound "<?= htmlspecialchars($recSound) ?>" in <?= $recGameName ?></h3>
        <p style="margin:0;direction:rtl;text-align:left;font-weight:600;"><?= htmlspecialchars($recReason) ?></p>
      </div>
      <a href="<?= $recUrl ?>" class="btn-pill btn-blue">Start Recommended Activity 🚀</a>
    </div>
  </div>
</body>
</html>