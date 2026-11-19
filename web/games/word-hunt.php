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
  <div class="star-overlay" id="starOverlay">
    <div class="star-popup">
      <div class="star-burst" id="starBurst">⭐⭐</div>
      <h2 id="starMessage" style="margin:12px 0 0;">أحسنت!</h2>
    </div>
  </div>

  <script src="/kelma/kelma/web/assets/js/stimulus-engine.js"></script>
  <script>
    const childId = <?= $childId ?>;
    const engine = (typeof KelmaStimulusEngine !== 'undefined')
      ? new KelmaStimulusEngine({
          childId,
          activity: 'word_hunt',
          onStarsUpdated: (total) => { document.getElementById('starCount').textContent = total; }
        })
      : null;

    let questions = [];
    let qIndex = 0;
    let currentQ = null;
    let solvedCurrent = false;

    function fallbackSpeak(text, audioPath) {
      if (audioPath) {
        const a = new Audio(audioPath);
        a.play().catch(() => {});
        return;
      }
      if (!window.speechSynthesis || !text) return;
      const u = new SpeechSynthesisUtterance(text);
      u.lang = 'ar-EG';
      u.rate = 0.85;
      const arVoice = speechSynthesis.getVoices().find(v => v.lang && v.lang.toLowerCase().startsWith('ar'));
      if (arVoice) u.voice = arVoice;
      speechSynthesis.cancel();
      speechSynthesis.speak(u);
    }

    async function loadLevel(level) {
      try {
        const res = await fetch(`/kelma/kelma/web/api/word_hunt_questions.php?child_id=${childId}&level=${level}`);
        const data = await res.json();
        questions = data.questions || [];
        qIndex = 0;
        if (!questions.length) {
          document.getElementById('promptEn').textContent = 'No questions returned for this level yet.';
          return;
        }
        renderQuestion();
      } catch (e) {
        document.getElementById('promptEn').textContent = 'Could not load Word Hunt questions.';
      }
    }

    function renderQuestion() {
      solvedCurrent = false;
      document.getElementById('sayAloudBox').style.display = 'none';
      document.getElementById('speechFeedback').textContent = '';

      if (qIndex >= questions.length) {
        const nextLvl = Math.min(3, parseInt(document.getElementById('levelSelect').value, 10) + 1);
        if (engine) engine.showStarReward(3, 'ممتاز! خلصت الجولة كلها! 🎉');
        document.getElementById('levelSelect').value = String(nextLvl);
        loadLevel(nextLvl);
        return;
      }

      currentQ = questions[qIndex];
      document.getElementById('qCounter').textContent = `Question ${qIndex + 1} / ${questions.length}`;
      document.getElementById('promptEn').textContent = currentQ.prompt_en;
      document.getElementById('promptAr').textContent = currentQ.prompt_ar;

      const grid = document.getElementById('huntGrid');
      grid.innerHTML = '';

      currentQ.options.forEach((opt) => {
        const tile = document.createElement('div');
        tile.className = 'hunt-tile';
        tile.innerHTML = `
          <span class="tile-emoji">${opt.emoji || opt.image || '🌟'}</span>
          <div class="tile-arabic">${opt.word}</div>
        `;
        tile.onclick = async () => {
          if (solvedCurrent) return;
          const isCorrect = parseInt(opt.id, 10) === parseInt(currentQ.target_id, 10);
          tile.classList.add(isCorrect ? 'correct' : 'wrong');
          if (engine) {
            await engine.submitTapAttempt(currentQ.target_id, isCorrect);
          }

          if (isCorrect) {
            solvedCurrent = true;
            document.getElementById('bonusWordLabel').textContent = `${currentQ.target_word} (${currentQ.target_en})`;
            document.getElementById('sayAloudBox').style.display = 'flex';
            setTimeout(() => {
              if (solvedCurrent && !document.getElementById('bonusMicBtn').disabled) {
                qIndex++;
                renderQuestion();
              }
            }, 4800);
          }
        };
        grid.appendChild(tile);
      });

      if (engine && engine.speakArabic) {
        engine.speakArabic(currentQ.speak_text, currentQ.audio_path);
      } else {
        fallbackSpeak(currentQ.speak_text, currentQ.audio_path);
      }
    }

    document.getElementById('replayBtn').onclick = () => {
      if (!currentQ) return;
      if (engine && engine.speakArabic) {
        engine.speakArabic(currentQ.speak_text, currentQ.audio_path);
      } else {
        fallbackSpeak(currentQ.speak_text, currentQ.audio_path);
      }
    };

    document.getElementById('levelSelect').onchange = (e) => loadLevel(e.target.value);

    document.getElementById('bonusMicBtn').onclick = async () => {
      const btn = document.getElementById('bonusMicBtn');
      btn.disabled = true;
      document.getElementById('speechFeedback').textContent = '🎙️ Listening... انطق الكلمة دلوقتي!';
      try {
        const res = await engine.recordAndScoreSpeech(currentQ.target_id, 0, 3000);
        document.getElementById('speechFeedback').textContent = res.passed
          ? `⭐ Bonus Star Unlocked! Heard: ${res.heard}`
          : `Good try! Heard: ${res.heard || '...'}`;
      } catch (e) {
        document.getElementById('speechFeedback').textContent = 'Microphone unavailable.';
      } finally {
        btn.disabled = false;
        setTimeout(() => { qIndex++; renderQuestion(); }, 1500);
      }
    };

    loadLevel(1);
  </script>
</body>
</html>