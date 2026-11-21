// FR20: Play Word Hunt game
<?php
require_once __DIR__ . '/../includes/config.php';
require_role('parent');

$parentId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$childId  = (int)($_GET['child_id'] ?? $_SESSION['child_id'] ?? $_SESSION['active_child_id'] ?? 0);

// Resolve child via mysqli ($db)
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

$starStmt = $db->prepare('SELECT COALESCE(SUM(motivational_stars), 0) AS total_stars FROM attempts WHERE child_id = ?');
$starStmt->bind_param('i', $childId);
$starStmt->execute();
$totalStars = (int)($starStmt->get_result()->fetch_assoc()['total_stars'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kelma — Word Hunt</title>
  <link rel="stylesheet" href="/kelma/kelma/web/assets/css/kelma-kids.css">
</head>
<body class="kelma-kids">
  <div class="topbar-kids">
    <a href="/kelma/kelma/web/games/hub.php?child_id=<?= $childId ?>" class="btn-pill btn-blue">← Back to Hub</a>
    <div class="star-badge">⭐ Stars: <span id="starCount"><?= $totalStars ?></span></div>
  </div>

  <div class="container-kids">
    <h1 style="margin-bottom:10px;">🔍 GAME 2: WORD HUNT (VOCABULARY GAME)</h1>

    <div class="game-card" style="border-color:#118AB2;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
          <label for="levelSelect"><strong>Level Progress:</strong></label>
          <select id="levelSelect" class="btn-pill btn-yellow" style="padding:8px 16px;">
            <option value="1">Level 1: Objects (الأشياء والحيوانات)</option>
            <option value="2">Level 2: Actions (الأفعال)</option>
            <option value="3">Level 3: Categories (التصنيفات)</option>
          </select>
        </div>
        <div class="btn-pill btn-mint" id="qCounter">Question 1 / 5</div>
      </div>

      <div style="margin-top:20px;padding:18px;background:#FFF9E6;border-radius:18px;">
        <div style="font-size:1.3rem;font-weight:800;color:#118AB2;" id="promptEn">Loading...</div>
        <div style="font-size:2rem;font-weight:900;direction:rtl;margin:8px 0;" id="promptAr">...</div>
        <button type="button" id="replayBtn" class="btn-pill btn-yellow">🔊 Listen Again / اسمع الكلمة</button>
      </div>
    </div>

    <div class="hunt-grid" id="huntGrid"></div>

    <!-- FR-23: Optional Say-Aloud Practice -->
    <div class="mission-banner" id="sayAloudBox" style="display:none;border-color:#FF5964;">
      <div>
        <strong style="font-size:1.15rem;">🎙️ Say "<span id="bonusWordLabel"></span>" aloud to unlock extra stars!</strong>
        <div id="speechFeedback" style="margin-top:6px;font-weight:700;color:#073B4C;"></div>
      </div>
      <button type="button" id="bonusMicBtn" class="btn-pill btn-coral">🎤 Say Word Aloud (+Stars)</button>
    </div>
  </div>

  <!-- FR-27: Star-Only Reward Animation Overlay -->
  <div class="star-overlay" id="starOverlay" style="display:none;">
    <div class="star-popup">
      <div class="star-burst" id="starBurst">⭐⭐</div>
      <h2 id="starMessage" style="margin:12px 0 0;">أحسنت!</h2>
    </div>
  </div>

  <script>
    window.WORD_HUNT_CONFIG = {
      childId: <?= $childId ?>,
      initialStars: <?= $totalStars ?>
    };
  </script>
  <script src="/kelma/kelma/web/assets/js/word-hunt.js"></script>
</body>
</html>