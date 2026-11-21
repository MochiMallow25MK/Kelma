// FR20: Play Word Hunt game
// Word Hunt – vocabulary & listening game logic
(function () {
  var API_BASE = '/kelma/kelma/web/api/';
  var cfg = window.WORD_HUNT_CONFIG || {};
  var childId = cfg.childId || 0;
  var totalStars = parseInt(cfg.initialStars || 0, 10) || 0;

  var questions = [];
  var qIndex = 0;
  var currentQ = null;
  var solvedCurrent = false;
  var currentAudio = null;

  var $ = function (id) { return document.getElementById(id); };

  var FALLBACK_BANK = {
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

  if (window.speechSynthesis) {
    speechSynthesis.getVoices();
    speechSynthesis.onvoiceschanged = function () { speechSynthesis.getVoices(); };
  }

  function speakArabic(text, audioPath) {
    if (audioPath) {
      try {
        if (window.speechSynthesis) speechSynthesis.cancel();
        if (currentAudio) {
          currentAudio.pause();
          currentAudio.currentTime = 0;
        }
        currentAudio = new Audio(audioPath);
        currentAudio.play().catch(function () { speakTTS(text); });
        return;
      } catch (e) {}
    }
    speakTTS(text);
  }

  function speakTTS(text) {
    if (!window.speechSynthesis || !text) return;
    if (currentAudio) {
      currentAudio.pause();
      currentAudio.currentTime = 0;
    }
    var u = new SpeechSynthesisUtterance(text);
    u.lang = 'ar-EG';
    u.rate = 0.85;
    var voices = speechSynthesis.getVoices();
    var arVoice = voices.find(function (v) { return v.lang && v.lang.toLowerCase().indexOf('ar-eg') === 0; })
               || voices.find(function (v) { return v.lang && v.lang.toLowerCase().indexOf('ar') === 0; });
    if (arVoice) u.voice = arVoice;
    speechSynthesis.cancel();
    speechSynthesis.speak(u);
  }

  function addStars(earned, message) {
    var gained = parseInt(earned || 0, 10);
    if (gained > 0) {
      totalStars += gained;
      if ($('starCount')) $('starCount').textContent = totalStars;
    }
    if (gained > 0 && $('starOverlay')) {
      if ($('starBurst')) $('starBurst').textContent = '⭐'.repeat(Math.min(3, Math.max(1, gained)));
      if ($('starMessage')) $('starMessage').textContent = message || ('أحسنت! +' + gained + ' ⭐');
      $('starOverlay').classList.add('active');
      $('starOverlay').style.display = 'flex';
      setTimeout(function () {
        $('starOverlay').classList.remove('active');
        $('starOverlay').style.display = 'none';
      }, 1300);
    }
  }

  function normalizeQuestions(rawList, level) {
    if (!Array.isArray(rawList) || rawList.length === 0) {
      return FALLBACK_BANK[level] || FALLBACK_BANK[1];
    }
    return rawList.map(function (q, idx) {
      var opts = Array.isArray(q.options) ? q.options : [];
      var targetId = q.target_id || q.word_id || (opts[0] && opts[0].id) || (idx + 1);
      var targetWord = q.target_word || q.word || (opts[0] && opts[0].word) || '';
      var targetEn = q.target_en || q.english_meaning || '';
      return {
        target_id: targetId,
        target_word: targetWord,
        target_en: targetEn,
        speak_text: q.speak_text || targetWord,
        audio_path: q.audio_path || null,
        prompt_en: q.prompt_en || ('Find: ' + (targetEn || targetWord)),
        prompt_ar: q.prompt_ar || ('فين ' + targetWord + '؟'),
        options: opts.map(function (o, oIdx) {
          return {
            id: o.id || (oIdx + 1),
            word: o.word || '',
            emoji: o.emoji || o.image || '🌟'
          };
        })
      };
    });
  }

  async function loadLevel(level) {
    var lvl = parseInt(level, 10) || 1;
    if ($('promptEn')) $('promptEn').textContent = 'Loading questions...';
    if ($('promptAr')) $('promptAr').textContent = '...';
    if ($('huntGrid')) $('huntGrid').innerHTML = '';

    try {
      var url = API_BASE + 'word_hunt_questions.php?child_id=' + encodeURIComponent(childId) + '&level=' + encodeURIComponent(lvl);
      var res = await fetch(url);
      if (res.ok) {
        var data = await res.json();
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
      var levelSelect = $('levelSelect');
      var currentLvl = levelSelect ? parseInt(levelSelect.value, 10) : 1;
      var nextLvl = Math.min(3, currentLvl + 1);
      addStars(3, ' ! ممتاز لقد أنهيت الجولة  🎉');
      if (levelSelect) levelSelect.value = String(nextLvl);
      loadLevel(nextLvl);
      return;
    }

    currentQ = questions[qIndex];
    if ($('qCounter')) $('qCounter').textContent = 'Question ' + (qIndex + 1) + ' / ' + questions.length;
    if ($('promptEn')) $('promptEn').textContent = currentQ.prompt_en;
    if ($('promptAr')) $('promptAr').textContent = currentQ.prompt_ar;

    var grid = $('huntGrid');
    if (!grid) return;
    grid.innerHTML = '';

    (currentQ.options || []).forEach(function (opt) {
      var tile = document.createElement('button');
      tile.type = 'button';
      tile.className = 'hunt-tile';
      tile.innerHTML =
        '<span class="tile-emoji">' + (opt.emoji || '🌟') + '</span>' +
        '<div class="tile-arabic">' + (opt.word || '') + '</div>';

      tile.onclick = async function () {
        if (solvedCurrent) return;
        var isCorrect = parseInt(opt.id, 10) === parseInt(currentQ.target_id, 10);
        tile.classList.add(isCorrect ? 'correct' : 'wrong');

        // FR27: Award stars and reward animation 
        if (isCorrect) {
          solvedCurrent = true;
          addStars(1, ' ! أحسنت +1 ⭐');

          // FR23: Offer optional say-aloud 
          // Try optional backend attempt log if available
          try {
            var fd = new FormData();
            fd.append('child_id', childId);
            fd.append('word_id', currentQ.target_id);
            fd.append('activity', 'word_hunt');
            fd.append('passed', '1');
            await fetch(API_BASE + 'attempt_tap.php', { method: 'POST', body: fd });
          } catch (e) {}

          if ($('bonusWordLabel')) {
            $('bonusWordLabel').textContent = currentQ.target_word + (currentQ.target_en ? ' (' + currentQ.target_en + ')' : '');
          }
          if ($('sayAloudBox')) $('sayAloudBox').style.display = 'flex';

          setTimeout(function () {
            var micBtn = $('bonusMicBtn');
            if (solvedCurrent && (!micBtn || !micBtn.disabled)) {
              qIndex++;
              renderQuestion();
            }
          }, 4500);
        } else {
          if ($('speechFeedback')) {
            $('speechFeedback').textContent = 'جرب تاني! Try another card.';
          }
        }
      };

      grid.appendChild(tile);
    });

    speakArabic(currentQ.speak_text, currentQ.audio_path);
  }

  function recordShortWav(durationMs) {
    return new Promise(function (resolve, reject) {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        reject(new Error('No microphone support'));
        return;
      }
      navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        var ctx = new AudioCtx();
        var src = ctx.createMediaStreamSource(stream);
        var proc = ctx.createScriptProcessor(4096, 1, 1);
        var chunks = [];

        proc.onaudioprocess = function (e) {
          chunks.push(new Float32Array(e.inputBuffer.getChannelData(0)));
        };
        src.connect(proc);
        proc.connect(ctx.destination);

        setTimeout(function () {
          proc.disconnect();
          stream.getTracks().forEach(function (t) { t.stop(); });
          var length = chunks.reduce(function (n, c) { return n + c.length; }, 0);
          var all = new Float32Array(length);
          var pos = 0;
          chunks.forEach(function (c) { all.set(c, pos); pos += c.length; });
          var ratio = ctx.sampleRate / 16000;
          var outLen = Math.max(1, Math.floor(all.length / ratio));
          var buffer = new ArrayBuffer(44 + outLen * 2);
          var v = new DataView(buffer);
          function writeStr(o, s) { for (var i = 0; i < s.length; i++) v.setUint8(o + i, s.charCodeAt(i)); }
          writeStr(0, 'RIFF'); v.setUint32(4, 36 + outLen * 2, true); writeStr(8, 'WAVE'); writeStr(12, 'fmt ');
          v.setUint32(16, 16, true); v.setUint16(20, 1, true); v.setUint16(22, 1, true);
          v.setUint32(24, 16000, true); v.setUint32(28, 32000, true); v.setUint16(32, 2, true); v.setUint16(34, 16, true);
          writeStr(36, 'data'); v.setUint32(40, outLen * 2, true);
          for (var i = 0; i < outLen; i++) {
            var idx = Math.min(all.length - 1, Math.floor(i * ratio));
            var s = Math.max(-1, Math.min(1, all[idx] || 0));
            v.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
          }
          ctx.close();
          resolve(new Blob([buffer], { type: 'audio/wav' }));
        }, durationMs || 2500);
      }).catch(reject);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if ($('replayBtn')) {
      $('replayBtn').onclick = function () {
        if (currentQ) speakArabic(currentQ.speak_text, currentQ.audio_path);
      };
    }

    if ($('levelSelect')) {
      $('levelSelect').onchange = function (e) {
        loadLevel(e.target.value);
      };
    }

    if ($('bonusMicBtn')) {
      $('bonusMicBtn').onclick = async function () {
        if (!currentQ) return;
        var btn = $('bonusMicBtn');
        btn.disabled = true;
        if ($('speechFeedback')) $('speechFeedback').textContent = '🎙️ Listening... انطق الكلمة دلوقتي!';
        try {
          var wav = await recordShortWav(2500);
          var form = new FormData();
          form.append('audio', wav, 'speech.wav');
          form.append('word_id', currentQ.target_id);
          form.append('child_id', childId);
          form.append('fails', 0);

          var res = await fetch(API_BASE + 'assess.php', { method: 'POST', body: form });
          var data = await res.json();
          if (data.error) {
            if ($('speechFeedback')) $('speechFeedback').textContent = data.error;
          } else {
            var bonusStars = parseInt(data.stars || 1, 10);
            addStars(bonusStars, '! أحسنت +' + bonusStars + ' ⭐');
            if ($('speechFeedback')) {
              $('speechFeedback').textContent = '⭐ +' + bonusStars + ' Stars! Heard: "' + (data.heard || currentQ.target_word) + '"';
            }
          }
        } catch (e) {
          if ($('speechFeedback')) $('speechFeedback').textContent = 'Microphone unavailable.';
        } finally {
          btn.disabled = false;
          setTimeout(function () {
            qIndex++;
            renderQuestion();
          }, 1400);
        }
      };
    }

    var startLevel = ($('levelSelect') && $('levelSelect').value) || 1;
    loadLevel(startLevel);
  });
})();