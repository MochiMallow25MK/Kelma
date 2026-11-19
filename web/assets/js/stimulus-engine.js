class KelmaStimulusEngine {
  constructor(config) {
    this.childId = config.childId;
    this.activity = config.activity;
    this.onStarsUpdated = config.onStarsUpdated || (() => {});
  }

  speakArabic(wordText, audioPath = null) {
    if (audioPath) {
      const audio = new Audio(audioPath);
      audio.play().catch(() => this._ttsFallback(wordText));
      return;
    }
    this._ttsFallback(wordText);
  }

  _ttsFallback(text) {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'ar-EG';
    utter.rate = 0.85;
    window.speechSynthesis.speak(utter);
  }

  showStarReward(stars, message = 'أحسنت! رائع! 🎉') {
    const overlay = document.getElementById('starOverlay');
    const burst = document.getElementById('starBurst');
    const msgEl = document.getElementById('starMessage');
    if (!overlay) return;

    burst.textContent = '⭐'.repeat(Math.max(1, Math.min(3, stars)));
    msgEl.textContent = message;
    overlay.classList.add('active');
    setTimeout(() => overlay.classList.remove('active'), 1600);
  }

  async submitTapAttempt(wordId, isCorrect) {
    const res = await fetch('/kelma/kelma/web/api/word_hunt_attempt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        child_id: this.childId,
        word_id: wordId,
        correct: isCorrect ? 1 : 0
      })
    });
    const data = await res.json();
    if (data.stars > 0) {
      this.showStarReward(data.stars, 'إجابة صحيحة! كسبت نجوم ⭐');
    }
    if (typeof data.total_stars !== 'undefined') {
      this.onStarsUpdated(data.total_stars);
    }
    return data;
  }

  async recordAndScoreSpeech(wordId, fails = 0, durationMs = 3200) {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const mediaRecorder = new MediaRecorder(stream);
    const chunks = [];

    return new Promise((resolve, reject) => {
      mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) chunks.push(e.data); };
      mediaRecorder.onstop = async () => {
        stream.getTracks().forEach((t) => t.stop());
        const blob = new Blob(chunks, { type: 'audio/webm' });
        const fd = new FormData();
        fd.append('audio', blob, 'attempt.webm');
        fd.append('word_id', wordId);
        fd.append('child_id', this.childId);
        fd.append('activity', this.activity);
        fd.append('fails', fails);

        try {
          const resp = await fetch('/kelma/kelma/web/api/assess.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (data.passed && data.stars > 0) {
            this.showStarReward(data.stars, `برافو! نطقت "${data.heard || ''}" صح! ⭐`);
          }
          if (typeof data.total_stars !== 'undefined') {
            this.onStarsUpdated(data.total_stars);
          }
          resolve(data);
        } catch (err) {
          reject(err);
        }
      };

      mediaRecorder.start();
      setTimeout(() => {
        if (mediaRecorder.state !== 'inactive') mediaRecorder.stop();
      }, durationMs);
    });
  }
}