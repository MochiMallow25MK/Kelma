<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) {
  throw new RuntimeException('Database connection is unavailable.');
}

$user = require_login('parent');
$childId = (int)($_GET['child_id'] ?? $_SESSION['active_child_id'] ?? 0);

// Fetch parent's children
$stmt = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY id ASC');
$stmt->execute([$user['user_id']]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$child = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) { $child = $c; break; }
}
if (!$child && !empty($children)) {
    $child = $children[0];
    $childId = (int)$child['id'];
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_profile';

    if ($action === 'save_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $dob      = $_POST['dob'] ?: null;
        $gender   = $_POST['gender'] ?? 'Prefer not to say';
        $favColor = $_POST['favourite_color'] ?? '#118AB2';
        $hobby    = trim($_POST['hobby'] ?? '');
        $pfp      = $_POST['pfp'] ?? '🦁';

        // Compute age_band automatically from DOB if provided
        $ageBand = '4-6';
        if ($dob) {
            $age = (new DateTime($dob))->diff(new DateTime())->y;
            $ageBand = $age <= 5 ? '4-5' : ($age <= 7 ? '6-7' : '8+');
        }

        if ($child) {
            $upd = $pdo->prepare("
                UPDATE children
                SET name = ?, full_name = ?, dob = ?, age_band = ?, gender = ?, favourite_color = ?, hobby = ?, pfp = ?, language = 'Egyptian Arabic'
                WHERE id = ? AND parent_id = ?
            ");
            $upd->execute([$fullName, $fullName, $dob, $ageBand, $gender, $favColor, $hobby, $pfp, $childId, $user['user_id']]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO children (parent_id, name, full_name, dob, age_band, gender, favourite_color, hobby, pfp, language)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Egyptian Arabic')
            ");
            $ins->execute([$user['user_id'], $fullName, $fullName, $dob, $ageBand, $gender, $favColor, $hobby, $pfp]);
            $childId = (int)$pdo->lastInsertId();
        }
        $_SESSION['active_child_id'] = $childId;
        header("Location: child_profile.php?child_id={$childId}&saved=1");
        exit;
    }

    if ($action === 'link_therapist' && $child) {
        $therapistEmail = trim($_POST['therapist_email'] ?? '');
        $tStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'therapist'");
        $tStmt->execute([$therapistEmail]);
        $therapist = $tStmt->fetch(PDO::FETCH_ASSOC);

        if ($therapist && !empty($_POST['guardian_consent'])) {
            $cIns = $pdo->prepare("INSERT INTO consents (parent_id, child_id, scope) VALUES (?, ?, 'therapy_oversight_and_speech_scoring')");
            $cIns->execute([$user['user_id'], $childId]);

            $lIns = $pdo->prepare("INSERT INTO therapist_links (therapist_id, child_id, status) VALUES (?, ?, 'approved') ON DUPLICATE KEY UPDATE status = 'approved'");
            $lIns->execute([$therapist['id'], $childId]);
            $msg = 'Guardian consent saved and therapist linked successfully!';
        } else {
            $msg = 'Please check the consent box and enter a valid registered therapist email.';
        }
    }
}

if (isset($_GET['saved'])) {
    $msg = 'Child profile saved successfully!';
}
$avatars = ['🦁','🐱','🐶','🐰','🦊','🐼','🦄','🐸','🐧','🚀','🌟','🎨'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kelma — Child Profile</title>
  <link rel="stylesheet" href="/kelma/kelma/web/assets/css/kelma-kids.css">
</head>
<body class="kelma-kids">
  <div class="topbar-kids">
    <a href="/kelma/kelma/web/parent/dashboard.php" class="btn-pill btn-blue">← Back to Parent Dashboard</a>
    <strong>👶 Child Profile & Guardian Settings</strong>
    <a href="/kelma/kelma/web/games/hub.php?child_id=<?= $childId ?>" class="btn-pill btn-mint">🎮 Open Child Hub</a>
  </div>

  <div class="container-kids">
    <?php if ($msg): ?>
      <div style="background:#E6FCF5;border:3px solid #06D6A0;padding:14px 20px;border-radius:16px;margin-bottom:18px;font-weight:700;">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="game-card" style="text-align:left;border-color:<?= htmlspecialchars($child['favourite_color'] ?? '#118AB2') ?>;">
      <h2><?= $child ? 'Edit Profile: ' . htmlspecialchars($child['full_name'] ?? $child['name']) : 'Add Your Child' ?></h2>
      <form method="POST">
        <input type="hidden" name="action" value="save_profile">

        <label><strong>Choose Avatar (PFP):</strong></label>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 18px;">
          <?php foreach ($avatars as $av): ?>
            <label style="cursor:pointer;font-size:2.2rem;padding:8px 12px;border:3px solid <?= (($child['pfp'] ?? '🦁') === $av) ? '#FF5964' : '#E2E8F0' ?>;border-radius:16px;">
              <input type="radio" name="pfp" value="<?= $av ?>" <?= (($child['pfp'] ?? '🦁') === $av) ? 'checked' : '' ?> style="display:none;">
              <?= $av ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
          <div>
            <label><strong>Full Name / Nickname *</strong></label><br>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($child['full_name'] ?? $child['name'] ?? '') ?>" style="width:100%;padding:12px;border-radius:12px;border:2px solid #CBD5E1;">
          </div>
          <div>
            <label><strong>Date of Birth (DOB)</strong></label><br>
            <input type="date" name="dob" value="<?= htmlspecialchars($child['dob'] ?? '') ?>" style="width:100%;padding:12px;border-radius:12px;border:2px solid #CBD5E1;">
          </div>
          <div>
            <label><strong>Gender</strong></label><br>
            <select name="gender" style="width:100%;padding:12px;border-radius:12px;border:2px solid #CBD5E1;">
              <?php foreach (['Boy', 'Girl', 'Prefer not to say'] as $g): ?>
                <option value="<?= $g ?>" <?= (($child['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label><strong>Favourite Colour</strong></label><br>
            <input type="color" name="favourite_color" value="<?= htmlspecialchars($child['favourite_color'] ?? '#118AB2') ?>" style="width:100%;height:46px;border-radius:12px;border:2px solid #CBD5E1;">
          </div>
          <div>
            <label><strong>Favourite Hobby</strong></label><br>
            <input type="text" name="hobby" placeholder="e.g. Drawing, Football, Blocks" value="<?= htmlspecialchars($child['hobby'] ?? '') ?>" style="width:100%;padding:12px;border-radius:12px;border:2px solid #CBD5E1;">
          </div>
          <div>
            <label><strong>Therapy Language</strong></label><br>
            <input type="text" readonly value="Egyptian Arabic (العربية المصرية)" style="width:100%;padding:12px;border-radius:12px;border:2px solid #E2E8F0;background:#F1F5F9;">
          </div>
        </div>

        <div style="margin-top:20px;">
          <button type="submit" class="btn-pill btn-coral">💾 Save Child Profile</button>
        </div>
      </form>
    </div>

    <?php if ($child): ?>
    <div class="game-card" style="text-align:left;margin-top:24px;border-color:#06D6A0;">
      <h3>🩺 Link Speech Therapist with Guardian Consent (FR-06)</h3>
      <form method="POST">
        <input type="hidden" name="action" value="link_therapist">
        <label><strong>Therapist Email:</strong></label><br>
        <input type="email" name="therapist_email" required placeholder="therapist@kelma.app" style="width:100%;max-width:400px;padding:12px;border-radius:12px;border:2px solid #CBD5E1;margin:8px 0;">
        <div style="margin:10px 0;">
          <label>
            <input type="checkbox" name="guardian_consent" value="1" required>
            I consent (Egypt PDPL Law No. 151/2020) to share <?= htmlspecialchars($child['full_name'] ?? $child['name']) ?>'s diagnostic scores and session plans with this licensed therapist. No voice recordings are stored.
          </label>
        </div>
        <button type="submit" class="btn-pill btn-mint">✅ Grant Consent & Link Therapist</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>