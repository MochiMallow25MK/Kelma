// FR19: Play Say It & Collect It game
// Say It & Collect It – game logic (words come from the AI agent via PHP)
var API = '/kelma/kelma/web/api/';
var words = [], index = 0, fails = 0, stars = 0, plan = null;
var $ = function (id) { return document.getElementById(id); };
var currentAudio = null;

// Preload browser speech voices
if (window.speechSynthesis) {
  speechSynthesis.getVoices();
  speechSynthesis.onvoiceschanged = function () { speechSynthesis.getVoices(); };
}

// ---------- Speak a word out loud (WAV audio first, ar-EG TTS fallback) ----------
function speakTTS(text) {
  if (!window.speechSynthesis || !text) return;
  if (currentAudio) {
    currentAudio.pause();
    currentAudio.currentTime = 0;
  }
  var u = new SpeechSynthesisUtterance(text);
  u.lang = 'ar-EG';
  u.rate = 0.8;

  var voices = speechSynthesis.getVoices();
  var arVoice = voices.find(function (v) { return v.lang && v.lang.toLowerCase().indexOf('ar-eg') === 0; })
             || voices.find(function (v) { return v.lang && v.lang.toLowerCase().indexOf('ar') === 0; });
  if (arVoice) u.voice = arVoice;

  speechSynthesis.cancel();
  speechSynthesis.speak(u);
}

function speakWord(w) {
  if (!w) return;
  if (w.audio_path) {
    try {
      if (window.speechSynthesis) speechSynthesis.cancel();
      if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
      }
      currentAudio = new Audio(w.audio_path);
      currentAudio.play().catch(function () { speakTTS(w.word); });
      return;
    } catch (e) {}
  }
  speakTTS(w.word);
}

// ---------- 1. Ask PHP (and the agent) for today's words ----------
fetch(API + 'next_words.php').then(function (r) { return r.json(); }).then(function (data) {
  plan = data;
  words = data.words || [];
  var who = data.source === 'agent' ? '🤖 AI agent' + (data.llm ? ' (' + data.llm + ')' : '') : '⚙️ Backup plan';
  $('mission').textContent = who + ': ' + (data.reason || '');
  if (!words.length) {
    $('mission').textContent = 'No words found in the database.';
    return;
  }
  $('gameArea').style.display = 'block';
  $('recordZone').style.display = 'block';
  showWord();
}).catch(function () { $('mission').textContent = 'Could not load words. Is Apache & MySQL running?'; });

function showWord() {
  var w = words[index];
  $('picture').textContent = w.emoji || w.image || '🌟';
  $('word').textContent = w.word;
  $('feedback').textContent = '';
  $('heard').textContent = '';
  $('cueBox').style.display = 'none';
  $('progressText').textContent = 'Word ' + (index + 1) + ' / ' + words.length;
  $('progressFill').style.width = (index / words.length * 100) + '%';
  setTimeout(function () { speakWord(w); }, 400);
}
$('listenBtn').onclick = function () { speakWord(words[index]); };
$('cueSoundBtn').onclick = function () { speakTTS(words[index].phoneme); };

// FR24: Record Speech in the browser 
// ---------- 2. Record audio and turn it into a 16 kHz WAV file ----------
var audioCtx, micStream, processor, chunks = [], recording = false;

function startRecording() {
  if (recording) return;
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    $('feedback').textContent = 'Microphone is not supported in this browser.';
    return;
  }
  navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
    micStream = stream;
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    var source = audioCtx.createMediaStreamSource(stream);
    processor = audioCtx.createScriptProcessor(4096, 1, 1);
    chunks = [];
    processor.onaudioprocess = function (e) { chunks.push(new Float32Array(e.inputBuffer.getChannelData(0))); };
    source.connect(processor);
    processor.connect(audioCtx.destination);
    recording = true;
    $('recordBtn').classList.add('recording');
    $('recordBtn').innerHTML = '👂<br>Listening...';
    $('feedback').textContent = '';
  }).catch(function () { $('feedback').textContent = 'Please allow microphone access in your browser.'; });
}

function stopRecording() {
  if (!recording) return;
  recording = false;
  processor.disconnect();
  micStream.getTracks().forEach(function (t) { t.stop(); });
  $('recordBtn').classList.remove('recording');
  $('recordBtn').innerHTML = '🎙️<br>Press &amp; Say Word';
  var wav = makeWav(chunks, audioCtx.sampleRate);
  audioCtx.close();
  sendRecording(wav);
}

// Join the chunks, shrink to 16000 Hz, write a WAV header
function makeWav(chunks, rate) {
  var length = chunks.reduce(function (n, c) { return n + c.length; }, 0);
  var all = new Float32Array(length), pos = 0;
  chunks.forEach(function (c) { all.set(c, pos); pos += c.length; });
  var ratio = rate / 16000, outLen = Math.max(1, Math.floor(all.length / ratio));
  var buffer = new ArrayBuffer(44 + outLen * 2), v = new DataView(buffer);
  function text(o, s) { for (var i = 0; i < s.length; i++) v.setUint8(o + i, s.charCodeAt(i)); }
  text(0, 'RIFF'); v.setUint32(4, 36 + outLen * 2, true); text(8, 'WAVE'); text(12, 'fmt ');
  v.setUint32(16, 16, true); v.setUint16(20, 1, true); v.setUint16(22, 1, true);
  v.setUint32(24, 16000, true); v.setUint32(28, 32000, true); v.setUint16(32, 2, true); v.setUint16(34, 16, true);
  text(36, 'data'); v.setUint32(40, outLen * 2, true);
  for (var i = 0; i < outLen; i++) {
    var idx = Math.min(all.length - 1, Math.floor(i * ratio));
    var s = Math.max(-1, Math.min(1, all[idx] || 0));
    v.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
  }
  return new Blob([buffer], { type: 'audio/wav' });
}

// Press and hold (mouse or touch)
var btn = $('recordBtn');
btn.addEventListener('mousedown', startRecording);
btn.addEventListener('mouseup', stopRecording);
btn.addEventListener('mouseleave', stopRecording);
btn.addEventListener('touchstart', function (e) { e.preventDefault(); startRecording(); });
btn.addEventListener('touchend', function (e) { e.preventDefault(); stopRecording(); });

// ---------- 3. Send to PHP for scoring ----------
function sendRecording(wav) {
  $('feedback').textContent = 'Checking... 🤔';
  var form = new FormData();
  form.append('audio', wav, 'speech.wav');
  form.append('word_id', words[index].id);
  form.append('fails', fails);
  fetch(API + 'assess.php', { method: 'POST', body: form }).then(function (r) { return r.json(); }).then(function (res) {
    if (res.error) { $('feedback').textContent = res.error; return; }
    $('heard').textContent = res.heard ? 'I heard: "' + res.heard + '"' : '';
    stars += res.stars;
    $('stars').textContent = stars;
    if (res.passed) {
      $('feedback').textContent = 'Great job! +' + res.stars + ' Stars 🌟' + (res.demo ? ' (demo score)' : '');
      fails = 0;
      setTimeout(nextWord, 1800);
    } else {
      fails++;
      handleFailure();
    }
  }).catch(function () { $('feedback').textContent = 'Something went wrong. Try again!'; });
}

// FR-28: Handle repeated failure in three tiers
// ---------- 4. Three-tier failure fallback ----------
function handleFailure() {
  var form = new FormData();
  form.append('word_id', words[index].id);
  form.append('fails', fails);
  fetch(API + 'on_failure.php', { method: 'POST', body: form }).then(function (r) { return r.json(); }).then(function (res) {
    if (res.action === 'cue') {
      $('feedback').textContent = "Let's look at a hint! 💡";
      $('cueText').textContent = words[index].phoneme + '... ' + words[index].phoneme + '... ' + words[index].word;
      $('cueBox').style.display = 'block';
    } else if (res.action === 'substitute') {
      $('feedback').textContent = "Let's try an easier word! 😊";
      words[index] = res.word;
      fails = 0;
      setTimeout(showWord, 1500);
    } else {
      $('feedback').textContent = 'Nice try! Press and say it again 💪';
    }
  });
}

function nextWord() {
  index++;
  if (index >= words.length) {
    $('progressFill').style.width = '100%';
    $('gameArea').innerHTML = '<div class="picture">🏆</div><h2>All done! You collected ' + stars + ' stars!</h2>' +
      '<a class="btn btn-coral btn-big" href="/kelma/kelma/web/games/say-it.php">Play again</a>';
    $('recordZone').style.display = 'none';
    $('cueBox').style.display = 'none';
    return;
  }
  showWord();
}