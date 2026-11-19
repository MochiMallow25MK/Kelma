<?php
require 'includes/config.php';
require 'includes/header.php';
?>

<style>
.contact-hero {
    background: linear-gradient(135deg, #fff0e9, #fff8d9, #e6f8f1);
    border-radius: 25px;
    padding: 55px 35px;
    margin-top: 30px;
}

.contact-hero h1 {
    color: #ff7465;
    font-size: 42px;
    font-weight: 800;
}

.contact-hero p {
    color: #666;
    line-height: 1.8;
    font-size: 17px;
}

.contact-hero img {
    width: 100%;
    max-width: 400px;
    border-radius: 25px;
}

.contact-section {
    padding: 60px 0;
}

.contact-title {
    font-weight: 800;
    color: #333;
    margin-bottom: 15px;
}

.contact-subtitle {
    color: #777;
    margin-bottom: 35px;
}

.contact-form {
    background: white;
    padding: 35px;
    border-radius: 22px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.06);
}

.contact-form label {
    font-weight: 600;
    color: #444;
    margin-bottom: 8px;
}

.contact-form .form-control {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 13px;
    margin-bottom: 18px;
}

.contact-form .form-control:focus {
    border-color: #ff8275;
    box-shadow: 0 0 0 3px rgba(255,130,117,0.12);
}

.contact-form textarea {
    resize: vertical;
    min-height: 140px;
}

.contact-btn {
    background: #ff8275;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 13px 30px;
    font-weight: 700;
    transition: 0.3s;
}

.contact-btn:hover {
    background: #f36559;
    color: white;
    transform: translateY(-2px);
}

.contact-card {
    padding: 25px;
    border-radius: 20px;
    height: 100%;
    text-align: center;
}

.contact-card .emoji {
    font-size: 35px;
    margin-bottom: 12px;
}

.contact-card h4 {
    font-weight: 700;
    color: #333;
}

.contact-card p {
    color: #666;
    margin-bottom: 0;
}

.coral-card {
    background: #fff0ed;
}

.yellow-card {
    background: #fff8df;
}

.mint-card {
    background: #eafaf2;
}

.contact-note {
    background: #f1efff;
    border-radius: 22px;
    padding: 30px;
    text-align: center;
}

@media(max-width: 768px) {
    .contact-hero {
        padding: 35px 20px;
        text-align: center;
    }

    .contact-hero h1 {
        font-size: 32px;
    }

    .contact-form {
        padding: 23px;
    }
}
</style>

<!-- HERO SECTION -->
<section class="container contact-hero">
    <div class="row align-items-center g-4">
        <div class="col-md-6">
            <h1>Let's Talk! 💬</h1>

            <p>
                Have a question about Kelma? Want to learn more
                about our AI-powered speech therapy platform?
                We'd love to hear from you!
            </p>

            <p>
                Whether you're a parent, therapist, or simply
                interested in our project, feel free to reach out.
            </p>
        </div>

        <div class="col-md-6 text-center">
            <img
                src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800"
                alt="Communication and connection"
            >
        </div>
    </div>
</section>

<!-- CONTACT INFORMATION -->
<section class="container contact-section text-center">

    <h2 class="contact-title">Get in Touch With Us</h2>

    <p class="contact-subtitle">
        We're always happy to connect with you!
    </p>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="contact-card coral-card">
                <div class="emoji">📧</div>
                <h4>Email Us</h4>
                <p>Send us your questions and suggestions.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="contact-card yellow-card">
                <div class="emoji">💡</div>
                <h4>Share Ideas</h4>
                <p>We'd love to hear your feedback and ideas.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="contact-card mint-card">
                <div class="emoji">🤝</div>
                <h4>Collaborate</h4>
                <p>Let's work together to make a difference.</p>
            </div>
        </div>

    </div>
</section>

<!-- CLOSING SECTION -->
<section class="container contact-section">
    <div class="contact-note">
        <h3>Every Voice Deserves to Be Heard! 💛</h3>

        <p>
            Thank you for being part of the Kelma journey.
            Together, we can help children communicate with
            confidence, one word at a time.
        </p>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
