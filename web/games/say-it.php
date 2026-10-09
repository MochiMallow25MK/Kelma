<?php
$title = 'Say It & Collect It'; require '../includes/config.php'; require_role('parent');
require '../includes/header.php';
?>
<div class="container">
  <div class="game-top">
    <a class="btn btn-yellow" href="/kelma/web/parent_dashboard.php">‹ Back</a>
    <b id="progressText">Loading...</b>
    <span class="badge" style="font-size:1.3rem">⭐ Stars: <span id="stars">0</span></span>
  </div>
  <div class="progress-bar" style="margin-top:.6rem"><div id="progressFill"></div></div>

  <div class="mission" id="mission">🤖 Kelma's AI agent is choosing today's words...</div>

  <div class="card stimulus" id="gameArea" style="display:none">
    <div class="picture" id="picture"></div>
    <div class="word" id="word"></div>
    <button class="btn btn-blue" id="listenBtn">🔊 Listen</button>
  </div>

  <div class="record-zone" id="recordZone" style="display:none">
    <button class="record-btn" id="recordBtn">🎙️<br>Press &amp; Say Word</button>
    <div class="feedback" id="feedback"></div>
  </div>

  <div class="cue-box" id="cueBox">
    💡 <b>Hint:</b> Say the first sound slowly, like this: <b id="cueText"></b>
    <button class="btn btn-mint" id="cueSoundBtn">🔊 Hear the sound</button>
  </div>
</div>
<script src="/kelma/web/assets/js/say-it.js"></script>
<?php require '../includes/footer.php'; ?>
