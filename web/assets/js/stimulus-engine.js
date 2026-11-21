// FR18: Provide a shared stimulus
/*
(function () {
  var API_BASE = '/kelma/kelma/web/api/';

  class KelmaStimulusEngine {
    constructor(config = {}) {
      this.childId = config.childId || 0;
      this.activity = config.activity || 'general';
      this.onStarsUpdated = config.onStarsUpdated || (() => {});
      this._currentAudio = null;
      this._activeCtx = null;
      this._activeStream = null;
      this._activeProc = null;
      this._chunks = [];
      this._isCapturing = false;

      if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = function () {
          window.speechSynthesis.getVoices();
        };
      }
    }

    _stopCurrentAudio() {
      if (this._currentAudio) {
        try {
          this._currentAudio.pause();
          this._currentAudio.currentTime = 0;
        } catch (e) {}
        this._currentAudio = null;
      }
    }

    speakArabic(wordText, audioPath = null) {
      if (audioPath) {
        try {
          if ('speechSynthesis' in window) window.speechSynthesis.cancel();
          this._stopCurrentAudio();
          const audio = new Audio(audioPath);
          this._currentAudio = audio;
          audio.play().catch(() => this._ttsFallback(wordText));
          return;
        } catch (e) {}
      }
      this._ttsFallback(wordText);
    }

    _ttsFallback(text) {
      if (!('speechSynthesis' in window) || !text) return;
      this._stopCurrentAudio();
      window.speechSynthesis.cancel();

      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'ar-EG';
      utter.rate = 0.85;

      const voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
      const arVoice =
        voices.find((v) => v.lang && v.lang.toLowerCase().indexOf('ar-eg') === 0) ||
        voices.find((v) => v.lang && v.lang.toLowerCase().indexOf('ar') === 0);
      if (arVoice) utter.voice = arVoice;

      window.speechSynthesis.speak(utter);
    }

    showStarReward(stars, message = 'أحسنت! رائع! 🎉') {
      const overlay = document.getElementById('starOverlay');
      const burst = document.getElementById('starBurst');
      const msgEl = document.getElementById('starMessage');
      if (!overlay) return;

      const count = Math.max(1, Math.min(3, parseInt(stars || 1, 10)));
      if (burst) burst.textContent = '⭐'.repeat(count);
      if (msgEl) msgEl.textContent = message;
      overlay.style.display = 'flex';
      overlay.classList.add('active');

      setTimeout(() => {
        overlay.classList.remove('active');
        overlay.style.display = 'none';
      }, 1500);
    }

    encodeWav16k(chunks, sampleRate) {
      const length = chunks.reduce((n, c) => n + c.length, 0);
      const all = new Float32Array(length);
      let pos = 0;
      chunks.forEach((c) => {
        all.set(c, pos);
        pos += c.length;
      });

      const rate = sampleRate || 44100;
      const ratio = rate / 16000;
      const outLen = Math.max(1, Math.floor(all.length / ratio));
      const buffer = new ArrayBuffer(44 + outLen * 2);
      const view = new DataView(buffer);

      function writeStr(offset, str) {
        for (let i = 0; i < str.length; i++) {
          view.setUint8(offset + i, str.charCodeAt(i));
        }
      }

      writeStr(0, 'RIFF');
      view.setUint32(4, 36 + outLen * 2, true);
      writeStr(8, 'WAVE');
      writeStr(12, 'fmt ');
      view.setUint32(16, 16, true);
      view.setUint16(20, 1, true);
      view.setUint16(22, 1, true);
      view.setUint32(24, 16000, true);
      view.setUint32(28, 32000, true);
      view.setUint16(32, 2, true);
      view.setUint16(34, 16, true);
      writeStr(36, 'data');
      view.setUint32(40, outLen * 2, true);

      for (let i = 0; i < outLen; i++) {
        const idx = Math.min(all.length - 1, Math.floor(i * ratio));
        const sample = Math.max(-1, Math.min(1, all[idx] || 0));
        view.setInt16(44 + i * 2, sample < 0 ? sample * 0x8000 : sample * 0x7fff, true);
      }

      return new Blob([buffer], { type: 'audio/wav' });
    }

    async beginHoldRecording() {
      if (this._isCapturing) return;
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('Microphone is not supported in this browser.');
      }

      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      const ctx = new AudioCtx();
      const source = ctx.createMediaStreamSource(stream);
      const processor = ctx.createScriptProcessor(4096, 1, 1);

      this._chunks = [];
      this._activeStream = stream;
      this._activeCtx = ctx;
      this._activeProc = processor;
      this._isCapturing = true;

      processor.onaudioprocess = (e) => {
        if (this._isCapturing) {
          this._chunks.push(new Float32Array(e.inputBuffer.getChannelData(0)));
        }
      };

      source.connect(processor);
      processor.connect(ctx.destination);
    }

    async finishHoldRecording() {
      if (!this._isCapturing) return null;
      this._isCapturing = false;

      const processor = this._activeProc;
      const stream = this._activeStream;
      const ctx = this._activeCtx;
      const chunks = this._chunks || [];

      this._activeProc = null;
      this._activeStream = null;
      this._activeCtx = null;
      this._chunks = [];

      if (processor) {
        try { processor.disconnect(); } catch (e) {}
      }
      if (stream) {
        stream.getTracks().forEach((t) => {
          try { t.stop(); } catch (e) {}
        });
      }

      const sampleRate = ctx ? ctx.sampleRate : 44100;
      const wavBlob = this.encodeWav16k(chunks, sampleRate);

      if (ctx) {
        try { await ctx.close(); } catch (e) {}
      }

      return wavBlob;
    }

    async scoreBlob(wavBlob, wordId, fails = 0) {
      const fd = new FormData();
      fd.append('audio', wavBlob, 'attempt.wav');
      fd.append('word_id', wordId);
      if (this.childId) fd.append('child_id', this.childId);
      fd.append('activity', this.activity);
      fd.append('fails', fails);

      const resp = await fetch(API_BASE + 'assess.php', { method: 'POST', body: fd });
      const data = await resp.json();

      if (data.passed && data.stars > 0) {
        this.showStarReward(data.stars, `برافو! نطقت "${data.heard || ''}" صح! ⭐`);
      }
      if (typeof data.total_stars !== 'undefined') {
        this.onStarsUpdated(data.total_stars);
      }
      return data;
    }

    async submitTapAttempt(wordId, isCorrect) {
      try {
        const res = await fetch(API_BASE + 'word_hunt_attempt.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            child_id: this.childId,
            word_id: wordId,
            correct: isCorrect ? 1 : 0
          })
        });
        const data = await res.json();
        if (data && data.stars > 0) {
          this.showStarReward(data.stars, 'إجابة صحيحة! كسبت نجوم ⭐');
        }
        if (data && typeof data.total_stars !== 'undefined') {
          this.onStarsUpdated(data.total_stars);
        }
        return data;
      } catch (e) {
        return { ok: false, stars: isCorrect ? 1 : 0 };
      }
    }

    async recordAndScoreSpeech(wordId, fails = 0, durationMs = 3000) {
      await this.beginHoldRecording();
      await new Promise((resolve) => setTimeout(resolve, durationMs));
      const wavBlob = await this.finishHoldRecording();
      return this.scoreBlob(wavBlob, wordId, fails);
    }
  }

  // ---------- Combined Controller 1: Say It & Collect It ----------
  function initSayItGame() {
    const recordBtn = document.getElementById('recordBtn');
    const listenBtn = document.getElementById('listenBtn');
    const missionEl = document.getElementById('mission');
    if (!recordBtn || !listenBtn || !missionEl) return;

    const $ = (id) => document.getElementById(id);
    const cfg = window.SAY_IT_CONFIG || {};
    let words = [];
    let index = 0;
    let fails = 0;
    let stars = parseInt(cfg.initialStars || 0, 10) || 0;

    const engine = new KelmaStimulusEngine({
      childId: cfg.childId || 0,
      activity: 'say_it_and_collect_it',
      onStarsUpdated: (total) => {
        if ($('stars')) $('stars').textContent = total;
      }
    });

    function showWord() {
      const w = words[index];
      if (!w) return;
      if ($('picture')) $('picture').textContent = w.emoji || w.image || '🌟';
      if ($('word')) $('word').textContent = w.word;
      if ($('feedback')) $('feedback').textContent = '';
      if ($('heard')) $('heard').textContent = '';
      if ($('cueBox')) $('cueBox').style.display = 'none';
      if ($('progressText')) $('progressText').textContent = 'Word ' + (index + 1) + ' / ' + words.length;
      if ($('progressFill')) $('progressFill').style.width = ((index / words.length) * 100) + '%';
      setTimeout(() => {
        engine.speakArabic(w.word, w.audio_path || null);
      }, 400);
    }

    function nextWord() {
      index++;
      if (index >= words.length) {
        if ($('progressFill')) $('progressFill').style.width = '100%';
        if ($('gameArea')) {
          $('gameArea').innerHTML =
            '<div class="picture">🏆</div><h2>All done! You collected ' + stars + ' stars!</h2>' +
            '<a class="btn btn-coral btn-big" href="/kelma/kelma/web/games/say-it.php">Play again</a>';
        }
        if ($('recordZone')) $('recordZone').style.display = 'none';
        if ($('cueBox')) $('cueBox').style.display = 'none';
        return;
      }
      showWord();
    }

    async function handleFailure() {
      const w = words[index];
      if (!w) return;
      const form = new FormData();
      form.append('word_id', w.id);
      form.append('fails', fails);
      if (engine.childId) form.append('child_id', engine.childId);

      try {
        const r = await fetch(API_BASE + 'on_failure.php', { method: 'POST', body: form });
        const res = await r.json();
        if (res.action === 'cue') {
          if ($('feedback')) $('feedback').textContent = "Let's look at a hint! 💡";
          if ($('cueText')) $('cueText').textContent = w.phoneme + '... ' + w.phoneme + '... ' + w.word;
          if ($('cueBox')) $('cueBox').style.display = 'block';
        } else if (res.action === 'substitute' && res.word) {
          if ($('feedback')) $('feedback').textContent = "Let's try an easier word! 😊";
          words[index] = res.word;
          fails = 0;
          setTimeout(showWord, 1500);
        } else {
          if ($('feedback')) $('feedback').textContent = 'Nice try! Press and say it again 💪';
        }
      } catch (e) {
        if ($('feedback')) $('feedback').textContent = 'Nice try! Press and say it again 💪';
      }
    }

    async function onHoldStart() {
      if (!words[index] || engine._isCapturing) return;
      try {
        await engine.beginHoldRecording();
        recordBtn.classList.add('recording');
        recordBtn.innerHTML = '👂<br>Listening...';
        if ($('feedback')) $('feedback').textContent = '';
      } catch (e) {
        if ($('feedback')) $('feedback').textContent = 'Please allow microphone access in your browser.';
      }
    }

    async function onHoldEnd() {
      if (!engine._isCapturing) return;
      recordBtn.classList.remove('recording');
      recordBtn.innerHTML = '🎙️<br>Press &amp; Say Word';

      try {
        const wav = await engine.finishHoldRecording();
        if (!wav || !words[index]) return;
        if ($('feedback')) $('feedback').textContent = 'Checking... 🤔';

        const res = await engine.scoreBlob(wav, words[index].id, fails);
        if (res.error) {
          if ($('feedback')) $('feedback').textContent = res.error;
          return;
        }
        if ($('heard')) {
          $('heard').textContent = res.heard ? 'I heard: "' + res.heard + '"' : '';
        }
        const earned = parseInt(res.stars || 0, 10);
        stars += earned;
        if ($('stars')) $('stars').textContent = stars;

        if (res.passed) {
          if ($('feedback')) {
            $('feedback').textContent = 'Great job! +' + earned + ' Stars 🌟' + (res.demo ? ' (demo score)' : '');
          }
          fails = 0;
          setTimeout(nextWord, 1800);
        } else {
          fails++;
          handleFailure();
        }
      } catch (e) {
        if ($('feedback')) $('feedback').textContent = 'Something went wrong. Try again!';
      }
    }

    listenBtn.onclick = () => {
      if (words[index]) engine.speakArabic(words[index].word, words[index].audio_path || null);
    };

    if ($('cueSoundBtn')) {
      $('cueSoundBtn').onclick = () => {
        if (words[index]) engine._ttsFallback(words[index].phoneme);
      };
    }

    recordBtn.addEventListener('mousedown', onHoldStart);
    recordBtn.addEventListener('mouseup', onHoldEnd);
    recordBtn.addEventListener('mouseleave', onHoldEnd);
    recordBtn.addEventListener('touchstart', (e) => {
      e.preventDefault();
      onHoldStart();
    });
    recordBtn.addEventListener('touchend', (e) => {
      e.preventDefault();
      onHoldEnd();
    });

    fetch(API_BASE + 'next_words.php')
      .then((r) => r.json())
      .then((data) => {
        words = data.words || [];
        const who = data.source === 'agent' ? '🤖 AI agent' + (data.llm ? ' (' + data.llm + ')' : '') : '⚙️ Backup plan';
        missionEl.textContent = who + ': ' + (data.reason || '');
        if (!words.length) {
          missionEl.textContent = 'No words found in the database.';
          return;
        }
        if ($('gameArea')) $('gameArea').style.display = 'block';
        if ($('recordZone')) $('recordZone').style.display = 'block';
        showWord();
      })
      .catch(() => {
        missionEl.textContent = 'Could not load words. Is Apache & MySQL running?';
      });
  }

  // ---------- Combined Controller 2: Word Hunt ----------
  function initWordHuntGame() {
    const huntGrid = document.getElementById('huntGrid');
    const levelSelect = document.getElementById('levelSelect');
    if (!huntGrid || !levelSelect) return;

    const $ = (id) => document.getElementById(id);
    const cfg = window.WORD_HUNT_CONFIG || {};
    const childId = cfg.childId || 0;
    let totalStars = parseInt(cfg.initialStars || 0, 10) || 0;
    let questions = [];
    let qIndex = 0;
    let currentQ = null;
    let solvedCurrent = false;

    const engine = new KelmaStimulusEngine({
      childId: childId,
      activity: 'word_hunt',
      onStarsUpdated: (total) => {
        totalStars = parseInt(total || totalStars, 10);
        if ($('starCount')) $('starCount').textContent = totalStars;
      }
    });

    const FALLBACK_BANK = {
      1: [
        {
          target_id: 101,
          target_word: 'قِطَّة',
          target_en: 'Cat',
          speak_text: 'قِطَّة',
          audio_path: null,
          prompt_en: 'Find the Cat!',
          prompt_ar: 'فين القِطَّة؟',
          options: [
            { id: 101, word: 'قِطَّة', emoji: '🐱' },
            { id: 102, word: 'كَلْب', emoji: '🐶' },
            { id: 103, word: 'بَطَّة', emoji: '🦆' },
            { id: 104, word: 'سَمَكَة', emoji: '🐟' }
          ]
        },
        {
          target_id: 201,
          target_word: 'تُفَّاحَة',
          target_en: 'Apple',
          speak_text: 'تُفَّاحَة',
          audio_path: null,
          prompt_en: 'Find the Apple!',
          prompt_ar: 'فين التُّفَّاحَة؟',
          options: [
            { id: 201, word: 'تُفَّاحَة', emoji: '🍎' },
            { id: 202, word: 'مَوْزَة', emoji: '🍌' },
            { id: 203, word: 'بِطِّيخ', emoji: '🍉' },
            { id: 204, word: 'عِنَب', emoji: '🍇' }
          ]
        }
      ],
      2: [
        {
          target_id: 301,
          target_word: 'يَجْرِي',
          target_en: 'Runs',
          speak_text: 'يَجْرِي',
          audio_path: null,
          prompt_en: 'Who is running?',
          prompt_ar: 'مين بيجري؟',
          options: [
            { id: 301, word: 'يَجْرِي', emoji: '🏃' },
            { id: 302, word: 'يَنَام', emoji: '😴' },
            { id: 303, word: 'يَأْكُل', emoji: '🍽️' },
            { id: 304, word: 'يَرْسُم', emoji: '🖌️' }
          ]
        }
      ],
      3: [
        {
          target_id: 401,
          target_word: 'أَحْمَر',
          target_en: 'Red (Colours)',
          speak_text: 'أَحْمَر',
          audio_path: null,
          prompt_en: 'Pick the Red colour!',
          prompt_ar: 'اختار اللون الأحمر!',
          options: [
            { id: 401, word: 'أَحْمَر', emoji: '🔴' },
            { id: 402, word: 'أَزْرَق', emoji: '🔵' },
            { id: 403, word: 'أَخْضَر', emoji: '🟢' },
            { id: 404, word: 'أَصْفَر', emoji: '🟡' }
          ]
        }
      ]
    };

    function normalizeQuestions(rawList, level) {
      if (!Array.isArray(rawList) || rawList.length === 0) {
        return FALLBACK_BANK[level] || FALLBACK_BANK[1];
      }
      return rawList.map((q, idx) => {
        const opts = Array.isArray(q.options) ? q.options : [];
        const targetId = q.target_id || q.word_id || (opts[0] && opts[0].id) || (idx + 1);
        const targetWord = q.target_word || q.word || (opts[0] && opts[0].word) || '';
        const targetEn = q.target_en || q.english_meaning || '';
        return {
          target_id: targetId,
          target_word: targetWord,
          target_en: targetEn,
          speak_text: q.speak_text || targetWord,
          audio_path: q.audio_path || null,
          prompt_en: q.prompt_en || ('Find: ' + (targetEn || targetWord)),
          prompt_ar: q.prompt_ar || ('فين ' + targetWord + '؟'),
          options: opts.map((o, oIdx) => ({
            id: o.id || (oIdx + 1),
            word: o.word || '',
            emoji: o.emoji || o.image || '🌟'
          }))
        };
      });
    }

    async function loadLevel(level) {
      const lvl = parseInt(level, 10) || 1;
      if ($('promptEn')) $('promptEn').textContent = 'Loading questions...';
      if ($('promptAr')) $('promptAr').textContent = '...';
      huntGrid.innerHTML = '';

      try {
        const url = API_BASE + 'word_hunt_questions.php?child_id=' + encodeURIComponent(childId) + '&level=' + encodeURIComponent(lvl);
        const res = await fetch(url);
        if (res.ok) {
          const data = await res.json();
          questions = normalizeQuestions(data.questions || data.items || data, lvl);
        } else {
          questions = normalizeQuestions([], lvl);
        }
      } catch (e) {
        questions = normalizeQuestions([], lvl);
      }

      qIndex = 0;
      renderQuestion();
    }

    function renderQuestion() {
      solvedCurrent = false;
      if ($('sayAloudBox')) $('sayAloudBox').style.display = 'none';
      if ($('speechFeedback')) $('speechFeedback').textContent = '';

      if (qIndex >= questions.length) {
        const currentLvl = parseInt(levelSelect.value, 10) || 1;
        const nextLvl = Math.min(3, currentLvl + 1);
        totalStars += 3;
        if ($('starCount')) $('starCount').textContent = totalStars;
        engine.showStarReward(3, 'ممتاز! خلصت الجولة كلها! 🎉');
        levelSelect.value = String(nextLvl);
        loadLevel(nextLvl);
        return;
      }

      currentQ = questions[qIndex];
      if ($('qCounter')) $('qCounter').textContent = 'Question ' + (qIndex + 1) + ' / ' + questions.length;
      if ($('promptEn')) $('promptEn').textContent = currentQ.prompt_en;
      if ($('promptAr')) $('promptAr').textContent = currentQ.prompt_ar;

      huntGrid.innerHTML = '';
      (currentQ.options || []).forEach((opt) => {
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'hunt-tile';
        tile.innerHTML =
          '<span class="tile-emoji">' + (opt.emoji || '🌟') + '</span>' +
          '<div class="tile-arabic">' + (opt.word || '') + '</div>';

        tile.onclick = async () => {
          if (solvedCurrent) return;
          const isCorrect = parseInt(opt.id, 10) === parseInt(currentQ.target_id, 10);
          tile.classList.add(isCorrect ? 'correct' : 'wrong');

          if (isCorrect) {
            solvedCurrent = true;
            const res = await engine.submitTapAttempt(currentQ.target_id, true);
            if (!res || typeof res.total_stars === 'undefined') {
              totalStars += 1;
              if ($('starCount')) $('starCount').textContent = totalStars;
              engine.showStarReward(1, 'برافو! +1 ⭐');
            }

            if ($('bonusWordLabel')) {
              $('bonusWordLabel').textContent = currentQ.target_word + (currentQ.target_en ? ' (' + currentQ.target_en + ')' : '');
            }
            if ($('sayAloudBox')) $('sayAloudBox').style.display = 'flex';

            setTimeout(() => {
              const micBtn = $('bonusMicBtn');
              if (solvedCurrent && (!micBtn || !micBtn.disabled)) {
                qIndex++;
                renderQuestion();
              }
            }, 4500);
          } else if ($('speechFeedback')) {
            $('speechFeedback').textContent = 'جرب تاني! Try another card.';
          }
        };

        huntGrid.appendChild(tile);
      });

      engine.speakArabic(currentQ.speak_text, currentQ.audio_path || null);
    }

    if ($('replayBtn')) {
      $('replayBtn').onclick = () => {
        if (currentQ) engine.speakArabic(currentQ.speak_text, currentQ.audio_path || null);
      };
    }

    levelSelect.onchange = (e) => loadLevel(e.target.value);

    if ($('bonusMicBtn')) {
      $('bonusMicBtn').onclick = async () => {
        if (!currentQ) return;
        const btn = $('bonusMicBtn');
        btn.disabled = true;
        if ($('speechFeedback')) $('speechFeedback').textContent = '🎙️ Listening... انطق الكلمة دلوقتي!';
        try {
          const data = await engine.recordAndScoreSpeech(currentQ.target_id, 0, 2500);
          if (data.error) {
            if ($('speechFeedback')) $('speechFeedback').textContent = data.error;
          } else {
            const bonus = parseInt(data.stars || 1, 10);
            if (typeof data.total_stars === 'undefined') {
              totalStars += bonus;
              if ($('starCount')) $('starCount').textContent = totalStars;
            }
            if ($('speechFeedback')) {
              $('speechFeedback').textContent = '⭐ +' + bonus + ' Stars! Heard: "' + (data.heard || currentQ.target_word) + '"';
            }
          }
        } catch (e) {
          if ($('speechFeedback')) $('speechFeedback').textContent = 'Microphone unavailable.';
        } finally {
          btn.disabled = false;
          setTimeout(() => {
            qIndex++;
            renderQuestion();
          }, 1400);
        }
      };
    }

    loadLevel(levelSelect.value || 1);
  }

  window.KelmaStimulusEngine = KelmaStimulusEngine;

  document.addEventListener('DOMContentLoaded', function () {
    initSayItGame();
    initWordHuntGame();
  });
})();
*/