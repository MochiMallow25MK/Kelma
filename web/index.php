<?php $title = 'Home'; require 'includes/config.php'; require 'includes/header.php'; ?>

<section class="container hero">
  <div>
    <h1>Empowering Children's Speech Through Play &amp; AI!</h1>
    <p>AI-driven speech therapy for kids, parents, and therapists.</p>
    <div class="buttons">
      <a class="btn btn-coral btn-big" href="/kelma/web/register.php">Get Started</a>
      <a class="btn btn-yellow btn-big" href="#about">Watch Demo</a>
    </div>
  </div>
  <div class="mascot">🦊</div>
</section>

<section class="container center" id="modules">
  <h2>Core Focus Areas</h2>
  <div class="grid-3">
    <div class="card t-coral"><div class="icon">🗣️</div><h3>Articulation Difficulties</h3><p>Practice making clear, correct sounds.</p></div>
    <div class="card t-yellow"><div class="icon">🧩</div><h3>Phonological Disorders</h3><p>Learn the sound patterns of words.</p></div>
    <div class="card t-mint"><div class="icon">📚</div><h3>Vocabulary Delay</h3><p>Grow new words through fun games.</p></div>
  </div>
</section>

<section class="container center" id="about">
  <h2>How Kelma Works</h2>
  <div class="grid-3">
    <div class="card"><div class="step-number" style="margin:auto">1</div><h3>Interactive Speech Games</h3><p>Children say words and earn stars, coins, and rewards.</p></div>
    <div class="card"><div class="step-number" style="margin:auto">2</div><h3>Agentic AI Adaptation</h3><p>Our AI agent chooses the right game and words for each child.</p></div>
    <div class="card"><div class="step-number" style="margin:auto">3</div><h3>Parent &amp; Therapist Insights</h3><p>Clear progress reports. Therapists review and approve the plan.</p></div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
