<?php
require 'includes/config.php';
require 'includes/header.php';
?>

<style>
/* =========================
   GENERAL STYLES
========================= */

:root {
    --coral: #ff7465;
    --yellow: #ffd166;
    --mint: #7bdcb5;
    --peach: #fff1ed;
    --soft-yellow: #fff8e5;
    --soft-mint: #e8f8f1;
    --dark: #333333;
    --gray: #666666;
}

.about-page {
    overflow: hidden;
}

.about-section {
    padding: 75px 0;
}

.section-title {
    color: var(--dark);
    font-weight: 800;
    font-size: 36px;
    margin-bottom: 15px;
}

.section-subtitle {
    color: var(--gray);
    max-width: 650px;
    margin: 0 auto 40px;
    line-height: 1.8;
    font-size: 16px;
}

.about-image {
    width: 100%;
    height: 100%;
    min-height: 280px;
    max-height: 420px;
    object-fit: cover;
    border-radius: 24px;
}

/* =========================
   HERO SECTION
========================= */

.about-hero {
    margin-top: 35px;
    padding: 55px 45px;
    border-radius: 30px;
    background: linear-gradient(
        135deg,
        #fff0e9,
        #fff8d9,
        #e6f8f1
    );
    position: relative;
    overflow: hidden;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-label {
    display: inline-block;
    background: white;
    color: var(--coral);
    padding: 9px 18px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 22px;
}

.about-hero h1 {
    color: var(--coral);
    font-size: clamp(38px, 5vw, 58px);
    font-weight: 900;
    line-height: 1.15;
    margin-bottom: 22px;
}

.about-hero p {
    font-size: 17px;
    color: #555;
    line-height: 1.9;
    max-width: 500px;
}

.hero-image {
    width: 100%;
    height: 370px;
    object-fit: cover;
    border-radius: 28px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
}

.hero-decoration {
    position: absolute;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255, 209, 102, 0.25);
    right: -40px;
    top: -40px;
}

/* =========================
   INTRO SECTION
========================= */

.intro-section {
    padding: 85px 0 45px;
}

.intro-image {
    width: 100%;
    height: 380px;
    object-fit: cover;
    border-radius: 28px;
}

.intro-text {
    padding: 25px 15px;
}

.intro-text h2 {
    font-size: 38px;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 22px;
}

.intro-text p {
    color: var(--gray);
    line-height: 1.9;
    font-size: 16px;
}

/* =========================
   FEATURES / BENTO GRID
========================= */

.feature-section {
    padding: 65px 0;
}

.about-card {
    border-radius: 25px;
    padding: 30px;
    height: 100%;
    min-height: 235px;
    transition: 0.3s ease;
    position: relative;
    overflow: hidden;
}

.about-card:hover {
    transform: translateY(-7px);
}

.about-card h4 {
    font-weight: 800;
    margin-bottom: 12px;
    color: var(--dark);
}

.about-card p {
    color: #555;
    line-height: 1.8;
    margin-bottom: 0;
}

.about-card .emoji {
    font-size: 40px;
    margin-bottom: 18px;
}

.coral-card {
    background: #fff0ed;
}

.yellow-card {
    background: #fff8e5;
}

.mint-card {
    background: #e8f8f1;
}

.feature-number {
    position: absolute;
    right: 22px;
    top: 15px;
    font-size: 55px;
    font-weight: 900;
    opacity: 0.12;
}

/* =========================
   MISSION SECTION
========================= */

.mission-section {
    padding: 45px 0;
}

.mission-box {
    background: var(--peach);
    border-radius: 30px;
    padding: 45px;
}

.mission-box h2 {
    color: var(--coral);
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 22px;
}

.mission-box p {
    color: #555;
    line-height: 1.9;
    font-size: 16px;
}

.mission-image {
    width: 100%;
    height: 350px;
    object-fit: cover;
    border-radius: 25px;
}

/* =========================
   AI SECTION
========================= */

.ai-section {
    padding: 65px 0;
}

.ai-box {
    background: linear-gradient(
        135deg,
        #e8f8f1,
        #fff8e5
    );
    border-radius: 30px;
    padding: 45px;
}

.ai-box h2 {
    color: var(--dark);
    font-size: 34px;
    font-weight: 900;
    margin-bottom: 20px;
}

.ai-box p {
    color: #555;
    line-height: 1.9;
}

.ai-list {
    padding-left: 0;
    list-style: none;
    margin-top: 20px;
}

.ai-list li {
    color: #555;
    line-height: 2.3;
    font-size: 15px;
}

.ai-image {
    width: 100%;
    height: 350px;
    object-fit: cover;
    border-radius: 25px;
}

/* =========================
   WHO WE SUPPORT
========================= */

.audience-section {
    padding: 70px 0;
}

.audience-card {
    background: white;
    border-radius: 25px;
    padding: 32px 25px;
    height: 100%;
    box-shadow: 0 7px 25px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.audience-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.09);
}

.audience-card .emoji {
    font-size: 45px;
    margin-bottom: 18px;
}

.audience-card h4 {
    font-weight: 800;
    color: var(--dark);
}

.audience-card p {
    color: #666;
    line-height: 1.8;
    margin-bottom: 0;
}

/* =========================
   CLOSING SECTION
========================= */

.closing-section {
    margin-top: 35px;
    margin-bottom: 65px;
    padding: 65px 30px;
    border-radius: 30px;
    background: linear-gradient(
        135deg,
        #fff0e9,
        #fff8d9,
        #e6f8f1
    );
    text-align: center;
}

.closing-section h2 {
    color: var(--coral);
    font-size: 40px;
    font-weight: 900;
    margin-bottom: 18px;
}

.closing-section p {
    max-width: 650px;
    margin: 0 auto;
    color: #555;
    line-height: 1.9;
    font-size: 17px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 768px) {

    .about-section {
        padding: 45px 0;
    }

    .about-hero {
        padding: 35px 22px;
        text-align: center;
    }

    .about-hero h1 {
        font-size: 36px;
    }

    .about-hero p {
        margin: auto;
    }

    .hero-image {
        height: 260px;
    }

    .intro-image {
        height: 280px;
    }

    .intro-text {
        text-align: center;
    }

    .intro-text h2 {
        font-size: 30px;
    }

    .section-title {
        font-size: 29px;
    }

    .mission-box,
    .ai-box {
        padding: 25px;
    }

    .mission-box h2,
    .ai-box h2 {
        font-size: 28px;
    }

    .mission-image,
    .ai-image {
        height: 260px;
    }

    .closing-section {
        padding: 45px 20px;
    }

    .closing-section h2 {
        font-size: 30px;
    }
}
</style>

<div class="about-page">

<!-- =========================
     HERO SECTION
========================= -->

<section class="container about-hero">

    <div class="hero-decoration"></div>

    <div class="row align-items-center g-5">

        <div class="col-lg-6 hero-content">

            <span class="hero-label">
                A Little About Us
            </span>

            <h1>
                Every Child Has a Voice.
            </h1>

            <p>
                Every child has a voice, and every voice deserves
                to be heard. Kelma combines the power of Artificial
                Intelligence with playful learning to make speech
                therapy more engaging, personalized, and accessible.
            </p>

        </div>

        <div class="col-lg-6">

            <img
                class="hero-image"
                src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1000"
                alt="Children enjoying a learning experience"
            >

        </div>

    </div>

</section>


<!-- =========================
     WHO WE ARE
========================= -->

<section class="container intro-section">

    <div class="row align-items-center g-5">

        <div class="col-lg-6 intro-text">

            <span class="hero-label">
                Get to Know Kelma
            </span>

            <h2>
                What is Kelma?
            </h2>

            <p>
                Kelma is an Agentic RAG AI-driven speech therapy
                platform designed to support children with speech
                and language difficulties through interactive
                and personalized experiences.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     WHAT DEFINES US
========================= -->

<section class="container feature-section text-center">

    <h2 class="section-title">
        What Makes Kelma Special?
    </h2>

    <p class="section-subtitle">
        Combining technology, creativity, and care to make
        every learning experience meaningful.
    </p>

    <div class="row g-4">

        <div class="col-md-4">

            <div class="about-card coral-card text-start">

                <span class="feature-number">01</span>

                <div class="emoji">🎮</div>

                <h4>Learn Through Play</h4>

                <p>
                    Fun and interactive speech games encourage
                    children to practice sounds, words, and
                    communication skills.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="about-card yellow-card text-start">

                <span class="feature-number">02</span>

                <div class="emoji">🤖</div>

                <h4>Intelligent AI</h4>

                <p>
                    Our agentic AI uses Retrieval-Augmented
                    Generation (RAG) to retrieve relevant
                    knowledge and help personalize learning activities.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="about-card mint-card text-start">

                <span class="feature-number">03</span>

                <div class="emoji">💚</div>

                <h4>Progress Together</h4>

                <p>
                    Parents and therapists can follow children's
                    progress and support their speech development.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     OUR MISSION
========================= -->

<section class="container mission-section">

    <div class="mission-box">

        <div class="row align-items-center g-5">

            <div class="col-lg-6">

                <span class="hero-label">
                    Why We Exist
                </span>

                <h2>
                    Our Mission 💛
                </h2>

                <p>
                    Our mission is to make speech therapy a positive
                    and enjoyable journey for every child.
                </p>

                <p>
                    We aim to combine technology, educational
                    resources, and personalized activities to help
                    children build confidence and improve their
                    communication skills.
                </p>

            </div>

            <div class="col-lg-6">

                <img
                    class="mission-image"
                    src="https://images.unsplash.com/photo-1511895426328-dc8714191300?w=1000"
                    alt="Family sharing a happy moment"
                >

            </div>

        </div>

    </div>

</section>


<!-- =========================
     POWERED BY AI
========================= -->

<section class="container ai-section">

    <div class="ai-box">

        <div class="row align-items-center g-5">

            <div class="col-lg-6">

                <span class="hero-label">
                    Technology Meets Learning
                </span>

                <h2>
                    Powered by Agentic AI & RAG 🧠
                </h2>

                <p>
                    Kelma goes beyond traditional learning platforms.
                    Its AI-powered architecture combines intelligent
                    agents with Retrieval-Augmented Generation (RAG).
                </p>

                <ul class="ai-list">

                    <li>
                        📚 Retrieves relevant information from a knowledge base.
                    </li>

                    <li>
                        🤖 Uses AI agents to select suitable learning activities.
                    </li>

                    <li>
                        🎯 Adapts activities based on the child's progress.
                    </li>

                    <li>
                        👩‍⚕️ Supports therapist supervision and guidance.
                    </li>

                </ul>

            </div>

            <div class="col-lg-6">

                <img
                    class="ai-image"
                    src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1000"
                    alt="Digital technology and interactive learning"
                >

            </div>

        </div>

    </div>

</section>


<!-- =========================
     WHO WE SUPPORT
========================= -->

<section class="container audience-section text-center">

    <h2 class="section-title">
        Who Is Kelma For?
    </h2>

    <p class="section-subtitle">
        A connected experience that brings everyone together.
    </p>

    <div class="row g-4">

        <div class="col-md-4">

            <div class="audience-card">

                <div class="emoji">👧</div>

                <h4>Children</h4>

                <p>
                    Enjoy exciting games and activities while
                    practicing speech and language skills.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="audience-card">

                <div class="emoji">👨‍👩‍👧</div>

                <h4>Parents</h4>

                <p>
                    Stay involved in your child's learning journey
                    and celebrate every achievement.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="audience-card">

                <div class="emoji">👩‍⚕️</div>

                <h4>Speech Therapists</h4>

                <p>
                    Review progress, provide guidance, and help
                    personalize children's learning plans.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     CLOSING SECTION
========================= -->

<section class="container closing-section">

    <h2>
        Every Word Matters!
    </h2>

    <p>
        At Kelma, we believe that small steps lead to big achievements.
        Let's make every child's communication journey brighter!
    </p>

</section>

</div>

<?php require 'includes/footer.php'; ?>