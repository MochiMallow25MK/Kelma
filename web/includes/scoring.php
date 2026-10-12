<?php
// Speech scoring. Which checker is used is set by SCORING_MODE in config.local.php:
//   'groq'  = Groq Whisper transcribes the speech, then we compare it with the target word
//   'azure' = Azure Pronunciation Assessment (phoneme-level, needs an Azure account)
//   'demo'  = random scores, for testing the game without any key
// Every function returns ['score' => 0-100, 'method' => ..., 'heard' => text or null]
// or ['error' => 'message'] when the service could not be reached.

function score_audio($wav_path, $target) {
    if (SCORING_MODE === 'groq' && GROQ_API_KEY !== '')  return score_with_groq($wav_path, $target);
    if (SCORING_MODE === 'azure' && AZURE_KEY !== '')    return score_with_azure($wav_path, $target);
    return ['score' => rand(40, 100), 'method' => 'demo', 'heard' => null];
}

// ---------- Groq Whisper (transcript matching) ----------
function score_with_groq($wav_path, $target) {
    $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . GROQ_API_KEY],
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($wav_path, 'audio/wav', 'speech.wav'),
            'model' => 'whisper-large-v3-turbo',
            'language' => 'en',
            'temperature' => '0',
            'response_format' => 'json',
            // We do NOT tell Whisper the target word, so it cannot "autocorrect" toward it.
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $reply = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!is_array($reply) || !isset($reply['text'])) {
        return ['error' => 'The speech checker is busy. Please try again in a moment.'];
    }
    $heard = trim($reply['text']);
    return ['score' => match_score($heard, $target), 'method' => 'groq', 'heard' => $heard];
}

// Compare what was heard with the target word.
//  exact match      -> 100
//  close but wrong  -> at most 50 (partial credit, always below the pass mark, e.g. "wabbit" for "rabbit")
//  nothing heard    -> 0
function match_score($heard, $target) {
    $words = preg_split('/[^a-z]+/', strtolower($heard), -1, PREG_SPLIT_NO_EMPTY);
    if (!$words) return 0;
    $target = strtolower($target);
    $best = 0;
    foreach ($words as $w) {
        if ($w === $target) return 100;
        similar_text($w, $target, $percent);
        $best = max($best, $percent);
    }
    return (int)round($best * 0.5);
}

// ---------- Azure Pronunciation Assessment (optional) ----------
function score_with_azure($wav_path, $target) {
    $params = base64_encode(json_encode([
        'ReferenceText' => $target, 'GradingSystem' => 'HundredMark',
        'Granularity' => 'Word', 'Dimension' => 'Comprehensive',
    ]));
    $url = 'https://' . AZURE_REGION . '.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language=en-US&format=detailed';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($wav_path),
        CURLOPT_HTTPHEADER => [
            'Ocp-Apim-Subscription-Key: ' . AZURE_KEY,
            'Content-Type: audio/wav; codecs=audio/pcm; samplerate=16000',
            'Accept: application/json',
            'Pronunciation-Assessment: ' . $params,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $reply = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!is_array($reply)) return ['error' => 'The speech checker is busy. Please try again in a moment.'];
    $score = 0;
    $heard = null;
    if (($reply['RecognitionStatus'] ?? '') === 'Success') {
        $best = $reply['NBest'][0] ?? [];
        $score = $best['PronunciationAssessment']['AccuracyScore'] ?? ($best['AccuracyScore'] ?? 0);
        $heard = $best['Display'] ?? null;
    }
    return ['score' => $score, 'method' => 'azure', 'heard' => $heard];
}
