<?php
// Copy this file to config.local.php and fill in your own values.
// config.local.php is ignored by Git, so your keys stay private.

define('GROQ_API_KEY', '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');       // Groq key (Whisper speech-to-text). Empty = no Groq
// define('AZURE_KEY', '');       // optional: Azure Speech key
// define('AZURE_REGION', 'eastus');

// Which speech checker to use: 'groq', 'azure' or 'demo' (random scores).
// If you leave this line out, Kelma picks groq, then azure, then demo, depending on which key is filled in.
// define('SCORING_MODE', 'groq');
