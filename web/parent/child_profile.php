<?php
require_once __DIR__ . '/../includes/config.php';
require_role('parent');

$parentId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$childId  = (int)($_GET['child_id'] ?? $_SESSION['child_id'] ?? $_SESSION['active_child_id'] ?? 0);

// Fetch parent's children using mysqli ($db)
$children = [];
if ($parentId > 0) {
    $stmt = $db->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$child = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) {
        $child = $c;
        break;
    }
}
if (!$child && !empty($children)) {
    $child   = $children[0];
    $childId = (int)$child['id'];
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_profile';

    if ($action === 'save_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $dob      = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender   = $_POST['gender'] ?? 'Prefer not to say';
        $favColor = $_POST['favourite_color'] ?? '#118AB2';
        $hobby    = trim($_POST['hobby'] ?? '');
        $pfp      = trim($_POST['pfp'] ?? ($child['pfp'] ?? '🐼'));

        $ageBand = '4-6';
        if ($dob) {
            $age = (new DateTime($dob))->diff(new DateTime())->y;
            $ageBand = $age <= 5 ? '4-5' : ($age <= 7 ? '6-7' : '8+');
        }

        if ($child) {
            $upd = $db->prepare("
                UPDATE children
                SET name = ?, full_name = ?, dob = ?, age_band = ?, gender = ?, favourite_color = ?, hobby = ?, pfp = ?, language = 'Egyptian Arabic'
                WHERE id = ? AND parent_id = ?
            ");
            $upd->bind_param('ssssssssii', $fullName, $fullName, $dob, $ageBand, $gender, $favColor, $hobby, $pfp, $childId, $parentId);
            $upd->execute();
        } else {
            $ins = $db->prepare("
                INSERT INTO children (parent_id, name, full_name, dob, age_band, gender, favourite_color, hobby, pfp, language)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Egyptian Arabic')
            ");
            $ins->bind_param('issssssss', $parentId, $fullName, $fullName, $dob, $ageBand, $gender, $favColor, $hobby, $pfp);
            $ins->execute();
            $childId = (int)$db->insert_id;
        }

        $_SESSION['child_id'] = $childId;
        $_SESSION['active_child_id'] = $childId;
        $_SESSION['child_name'] = $fullName;
        header("Location: /kelma/kelma/web/parent/child_profile.php?child_id={$childId}&saved=1");
        exit;
    }

    if ($action === 'link_therapist' && $child) {
        $therapistEmail = trim($_POST['therapist_email'] ?? '');
        $tStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'therapist' LIMIT 1");
        $tStmt->bind_param('s', $therapistEmail);
        $tStmt->execute();
        $therapist = $tStmt->get_result()->fetch_assoc();

        if ($therapist && !empty($_POST['guardian_consent'])) {
            $tId = (int)$therapist['id'];
            $cIns = $db->prepare("INSERT INTO consents (parent_id, child_id, scope) VALUES (?, ?, 'therapy_oversight_and_speech_scoring')");
            $cIns->bind_param('ii', $parentId, $childId);
            $cIns->execute();

            $lIns = $db->prepare("INSERT INTO therapist_links (therapist_id, child_id, status) VALUES (?, ?, 'approved') ON DUPLICATE KEY UPDATE status = 'approved'");
            $lIns->bind_param('ii', $tId, $childId);
            $lIns->execute();
            $msg = 'Guardian consent saved and therapist linked successfully!';
        } else {
            $msg = 'Please check the consent box and enter a valid registered therapist email.';
        }
    }
}

if (isset($_GET['saved'])) {
    $msg = 'Child profile saved successfully!';
}

$avatars = ['🦁','🐱','🐶','🐰','🐼','🦄','🐸','🐧','🚀','🎨'];
$selectedPfp = (!empty($child['pfp'])) ? $child['pfp'] : '🐼';
if (!in_array($selectedPfp, $avatars, true)) {
    $avatars[] = $selectedPfp;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kelma — Child Profile</title>
  <link rel="stylesheet" href="/kelma/kelma/web/assets/css/kelma-kids.css">
  <style>
    .pfp-picker {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 10px 0 18px;
    }
    .pfp-option {
      cursor: pointer;
      font-size: 2.2rem;
      padding: 8px 14px;
      border: 3px solid #E2E8F0;
      border-radius: 16px;
      background: #FFFFFF;
      user-select: none;
      transition: transform 0.12s ease, border-color 0.12s ease, background-color 0.12s ease;
    }
    .pfp-option:hover {
      transform: translateY(-2px);
      border-color: #94A3B8;
    }
    .pfp-option.selected {
      border-color: #FF5964;
      background: #FFF1F2;
      box-shadow: 0 0 0 3px rgba(255, 89, 100, 0.18);
    }
    .pfp-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
  </style>
</head>
<body class="kelma-kids">
  <div class="topbar-kids">
    <a href="/kelma/kelma/web/parent_dashboard.php" class="btn-pill btn-blue">← Back to Parent Dashboard</a>
    <strong>👶 Child Profile &amp; Guardian Settings</strong>
    <a href="/kelma/kelma/web/games/hub.php?child_id=<?= $childId ?>" class="btn-pill btn-mint">Go to Child Hub</a>
  </div>

  <div class="container-kids">
    <?php if ($msg): ?>
      <div style="background:#E6FCF5;border:3px solid #06D6A0;padding:14px 20px;border-radius:16px;margin-bottom:18px;font-weight:700;">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="game-card" style="text-align:left;border-color:<?= htmlspecialchars($child['favourite_color'] ?? '#118AB2') ?>;">
      <h2><?= $child ? 'Edit Profile: ' . htmlspecialchars($child['full_name'] ?? $child['name']) : 'Add Your Child' ?></h2>
      <form method="POST" id="childProfileForm">
        <input type="hidden" name="action" value="save_profile">

        <label><strong>Choose Avatar (PFP):</strong> <span id="selectedPfpBadge" style="font-size:1.5rem;margin-left:6px;"><?= htmlspecialchars($selectedPfp) ?></span></label>
        <div class="pfp-picker" id="pfpPicker">
          <?php foreach ($avatars as $idx => $av): ?>
            <?php $isSelected = ($selectedPfp === $av); ?>
            <label class="pfp-option <?= $isSelected ? 'selected' : '' ?>" data-pfp="<?= htmlspecialchars($av) ?>">
              <input type="radio" name="pfp" value="<?= htmlspecialchars($av) ?>" <?= $isSelected ? 'checked' : '' ?>>
              <span><?= htmlspecialchars($av) ?></span>
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
            <input type="text" id="hobbyInput" name="hobby" placeholder="e.g. Drawing, Football, Blocks" value="<?= htmlspecialchars($child['hobby'] ?? '') ?>" style="width:100%;padding:12px;border-radius:12px;border:2px solid #CBD5E1;">
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
        <button type="submit" class="btn-pill btn-mint">✅ Grant Consent &amp; Link Therapist</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <script>
    (function () {
      var picker = document.getElementById('pfpPicker');
      var badge = document.getElementById('selectedPfpBadge');
      if (!picker) return;

      var options = Array.prototype.slice.call(picker.querySelectorAll('.pfp-option'));

      function syncSelection(activeOption) {
        options.forEach(function (opt) {
          var isMatch = (opt === activeOption);
          opt.classList.toggle('selected', isMatch);
          var radio = opt.querySelector('input[type="radio"]');
          if (radio) radio.checked = isMatch;
        });
        if (activeOption && badge) {
          badge.textContent = activeOption.getAttribute('data-pfp') || '';
        }
      }

      options.forEach(function (opt) {
        opt.addEventListener('click', function (e) {
          e.preventDefault();
          syncSelection(opt);
        });
      });

      // Ensure hobby input never alters avatar selection
      var hobbyInput = document.getElementById('hobbyInput');
      if (hobbyInput) {
        hobbyInput.addEventListener('input', function () {
          var checkedOption = options.find(function (opt) {
            var r = opt.querySelector('input[type="radio"]');
            return r && r.checked;
          });
          if (checkedOption) syncSelection(checkedOption);
        });
      }
    })();
  </script>
</body>
</html>