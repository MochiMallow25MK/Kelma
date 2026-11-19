<?php
require 'includes/config.php';
require 'includes/header.php';
?>

<style>
.about-hero {
    background: linear-gradient(135deg, #fff0e9, #fff8d9, #e6f8f1);
    border-radius: 25px;
    padding: 65px 35px;
    margin-top: 30px;
}

.about-hero h1 {
    color: #ff7465;
    font-size: 42px;
    font-weight: 800;
}

.about-hero p {
    font-size: 17px;
    color: #555;
    line-height: 1.8;
}

.about-hero img {
    width: 100%;
    max-width: 430px;
    border-radius: 25px;
}

.about-section {
    padding: 65px 0;
}

.section-title {
    color: #333;
    font-weight: 800;
    margin-bottom: 15px;
}

.section-subtitle {
    color: #777;
    max-width: 650px;
    margin: 0 auto 35px;
}

.about-card {
    background: white;
    border-radius: 22px;
    padding: 28px;
    height: 100%;
    box-shadow: 0 5px 22px rgba(0,0,0,0.06);
    transition: 0.3s;
}

.about-card:hover {
    transform: translateY(-7px);
}

.about-card .emoji {
    font-size: 42px;
    margin-bottom: 15px;
}

.coral { border-top: 5px solid #ff8275; }
.yellow { border-top: 5px solid #ffd166; }
.mint { border-top: 5px solid #7bdcb5; }

.about-image {
    width: 100%;
    border-radius: 22px;
    object-fit: cover;
    max-height: 350px;
}

.mission-box {
    background: #fff1ed;
    border-radius: 25px;
    padding: 40px;
}

.mission-box h2 {
    color: #ff7465;
    font-weight: 800;
}

.ai-box {
    background: linear-gradient(135deg, #e8f8f1, #edf2ff);
    padding: 40px;
    border-radius: 25px;
}

.ai-box h2 {
    color: #333;
    font-weight: 800;
}

.ai-list {
    line-height: 2.2;
    color: #555;
}

@media(max-width: 768px) {
    .about-hero {
        padding: 35px 22px;
        text-align: center;
    }

    .about-hero h1 {
        font-size: 32px;
    }

    .mission-box, .ai-box {
        padding: 25px;
    }
}
</style>

<!-- HERO SECTION -->
<section class="container about-hero">
    <div class="row align-items-center g-4">
        <div class="col-md-6">
            <h1>Welcome to Kelma! 🦊</h1>
            <p>
                Every child has a voice, and every voice deserves
                to be heard. Kelma combines the power of Artificial
                Intelligence with playful learning to make speech
                therapy more engaging, personalized, and accessible.
            </p>
        </div>
        <div class="col-md-6 text-center">
            <img
                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQdqzc-TOn-11qg1SLSNfaBolzVEFuLdPpSSNnTxpK2DCFAm0qqa2GMAT7o&s=10"
                alt="Children learning together"
            >
        </div>
    </div>
</section>

<!-- ABOUT SECTION -->
<section class="container about-section text-center">
    <h2 class="section-title" style="text-align: center;">What is Kelma?</h2>
    <p class="section-subtitle">
        Kelma is an Agentic RAG AI-driven speech therapy platform
        designed to support children with speech and language
        difficulties through interactive and personalized experiences.
    </p>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="about-card coral">
                <div class="emoji">🎮</div>
                <h4>Learn Through Play</h4>
                <p>
                    Fun and interactive speech games encourage children
                    to practice sounds, words, and communication skills.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="about-card yellow">
                <div class="emoji">🤖</div>
                <h4>Intelligent AI</h4>
                <p>
                    Our agentic AI uses Retrieval-Augmented Generation
                    (RAG) to retrieve relevant knowledge and help
                    personalize learning activities.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="about-card mint">
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

<!-- MISSION SECTION -->
<section class="container about-section">
    <div class="mission-box">
        <div class="row align-items-center g-4">
            <div class="col-md-6">
                <h2>Our Mission 💛</h2>
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

            <div class="col-md-6">
                <img
                    class="about-image"
                    src="https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800"
                    alt="Children learning together"
                >
            </div>
        </div>
    </div>
</section>

<!-- HOW AI WORKS -->
<section class="container about-section">
    <div class="ai-box">
        <div class="row align-items-center g-4">
            <div class="col-md-6">
                <h2>Powered by Agentic AI & RAG 🧠</h2>
                <p>
                    Kelma goes beyond traditional learning platforms.
                    Its AI-powered architecture combines intelligent
                    agents with Retrieval-Augmented Generation (RAG).
                </p>

                <ul class="ai-list">
                    <li>📚 Retrieves relevant information from a knowledge base.</li>
                    <li>🤖 Uses AI agents to select suitable learning activities.</li>
                    <li>🎯 Adapts activities based on the child's progress.</li>
                    <li>👩‍⚕️ Supports therapist supervision and guidance.</li>
                </ul>
            </div>

            <div class="col-md-6 text-center">
                <img
                    class="about-image"
                    src="https://images.unsplash.com/photo-1484820540004-14229fe36ca4?w=800"
                    alt="Technology and education"
                >
            </div>
        </div>
    </div>
</section>

<!-- WHO WE SUPPORT -->
<section class="container about-section text-center">
    <h2 class="section-title"style="text-align: center;">Who Is Kelma For?</h2>
    <p class="section-subtitle">
        A connected experience that brings everyone together.
    </p>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="about-card coral">
                <div class="emoji">👧</div>
                <h4>Children</h4>
                <p>
                    Enjoy exciting games and activities while practicing
                    speech and language skills.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="about-card yellow">
                <div class="emoji">👨‍👩‍👧</div>
                <h4>Parents</h4>
                <p>
                    Stay involved in your child's learning journey
                    and celebrate every achievement.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="about-card mint">
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

<!-- CLOSING SECTION -->
<section class="container about-section text-center">
<h2 class="section-title" style="text-align: center;">Every Word Matters! 🦊</h2>    
<p class="section-subtitle">
        At Kelma, we believe that small steps lead to big achievements.
        Let's make every child's communication journey brighter!
    </p>
</section>

<?php require 'includes/footer.php'; ?>
