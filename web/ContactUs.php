<?php
require 'includes/config.php';
require 'includes/header.php';
?>

<style>

/* =========================
   COLOR PALETTE
========================= */

:root {
    --coral: #ff7465;
    --coral-dark: #f36559;
    --yellow: #ffd166;
    --mint: #7bdcb5;
    --peach: #fff1ed;
    --soft-yellow: #fff8df;
    --soft-mint: #eafaf2;
    --dark: #333333;
    --gray: #666666;
}

.contact-page {
    overflow: hidden;
}

.contact-section {
    padding: 70px 0;
}

/* =========================
   HERO SECTION
========================= */

.contact-hero {
    margin-top: 30px;
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

.contact-hero h1 {
    color: var(--coral);
    font-size: clamp(36px, 5vw, 52px);
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 22px;
}

.contact-hero p {
    color: #555;
    font-size: 17px;
    line-height: 1.9;
}

.hero-label {
    display: inline-block;
    background: white;
    color: var(--coral);
    padding: 9px 18px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 20px;
}

.hero-image {
    width: 100%;
    height: 330px;
    object-fit: contain;
    border-radius: 25px;
}

.hero-decoration {
    position: absolute;
    width: 160px;
    height: 160px;
    background: rgba(255, 209, 102, 0.25);
    border-radius: 50%;
    right: -45px;
    top: -45px;
}

/* =========================
   CONTACT MAIN SECTION
========================= */

.contact-main {
    padding: 75px 0;
}

.contact-intro h2 {
    color: var(--dark);
    font-size: 35px;
    font-weight: 900;
    margin-bottom: 15px;
}

.contact-intro p {
    color: var(--gray);
    line-height: 1.9;
    margin-bottom: 30px;
}

/* Contact information */

.contact-info-card {
    display: flex;
    align-items: center;
    gap: 17px;
    padding: 19px;
    border-radius: 18px;
    margin-bottom: 15px;
    transition: 0.3s;
}

.contact-info-card:hover {
    transform: translateX(5px);
}

.contact-info-card .emoji {
    font-size: 30px;
    min-width: 40px;
}

.contact-info-card h5 {
    color: var(--dark);
    font-weight: 800;
    margin-bottom: 5px;
}

.contact-info-card p {
    color: #666;
    font-size: 14px;
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

/* =========================
   CONTACT FORM
========================= */

.contact-form {
    background: white;
    padding: 38px;
    border-radius: 25px;
    box-shadow: 0 8px 35px rgba(0,0,0,0.07);
    border: 1px solid #f5f5f5;
}

.contact-form h3 {
    font-weight: 900;
    color: var(--dark);
    font-size: 27px;
    margin-bottom: 10px;
}

.form-description {
    color: #777;
    font-size: 14px;
    margin-bottom: 28px;
    line-height: 1.7;
}

.contact-form label {
    font-weight: 700;
    color: #444;
    margin-bottom: 9px;
    font-size: 14px;
}

.contact-form .form-control,
.contact-form .form-select {
    border: 1px solid #eee;
    border-radius: 13px;
    padding: 13px 15px;
    margin-bottom: 19px;
    background-color: #fdfdfd;
    font-size: 14px;
}

.contact-form .form-control:focus,
.contact-form .form-select:focus {
    border-color: var(--coral);
    box-shadow: 0 0 0 3px rgba(255,116,101,0.12);
    background: white;
}

.contact-form textarea {
    min-height: 140px;
    resize: vertical;
}

.contact-btn {
    width: 100%;
    background: var(--coral);
    color: white;
    border: none;
    border-radius: 13px;
    padding: 15px;
    font-weight: 800;
    font-size: 15px;
    transition: 0.3s;
}

.contact-btn:hover {
    background: var(--coral-dark);
    color: white;
    transform: translateY(-3px);
}

/* =========================
   FAQ SECTION
========================= */

.faq-section {
    padding: 65px 0;
    background: #fffaf5;
    border-radius: 30px;
}

.faq-section h2 {
    color: var(--dark);
    font-weight: 900;
    margin-bottom: 15px;
}

.faq-item {
    background: white;
    border: none;
    border-radius: 15px !important;
    margin-bottom: 13px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    overflow: hidden;
}

.faq-item .accordion-button {
    border-radius: 15px !important;
    font-weight: 700;
    color: var(--dark);
    padding: 20px;
    box-shadow: none;
}

.faq-item .accordion-button:not(.collapsed) {
    background: var(--peach);
    color: var(--coral);
}

.faq-item .accordion-body {
    color: #666;
    line-height: 1.8;
}

/* =========================
   CLOSING SECTION
========================= */

.contact-note {
    background: linear-gradient(
        135deg,
        #fff0e9,
        #fff8d9,
        #e6f8f1
    );
    border-radius: 28px;
    padding: 55px 30px;
    text-align: center;
}

.contact-note h2 {
    color: var(--coral);
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 18px;
}

.contact-note p {
    color: #555;
    max-width: 650px;
    margin: auto;
    line-height: 1.9;
    font-size: 16px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 768px) {

    .contact-hero {
        padding: 35px 22px;
        text-align: center;
    }

    .contact-hero h1 {
        font-size: 34px;
    }

    .hero-image {
        height: 240px;
    }

    .contact-main {
        padding: 45px 0;
    }

    .contact-intro {
        text-align: center;
    }

    .contact-intro h2 {
        font-size: 29px;
    }

    .contact-form {
        padding: 25px;
    }

    .contact-note {
        padding: 40px 22px;
    }

    .contact-note h2 {
        font-size: 29px;
    }

}

</style>


<div class="contact-page">

<!-- =========================
     HERO SECTION
========================= -->

<section class="container contact-hero">

    <div class="hero-decoration"></div>

    <div class="row align-items-center g-4">

        <div class="col-lg-6">

            <span class="hero-label">
                We're Here for You 
            </span>
            <h1>
                We'd Love to Hear From You!
            </h1>
        </div>

        <div class="col-lg-6 text-center">

            <img
                class="hero-image"
                src="https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=1000"
                alt="Happy children enjoying a playful learning experience"
            >

        </div>

    </div>

</section>


<!-- =========================
     CONTACT FORM + INFORMATION
========================= -->

<section class="container contact-main">

    <div class="row g-5 align-items-start">

        <!-- LEFT SIDE -->

        <div class="col-lg-5">

            <div class="contact-intro">

                <span class="hero-label">
                    Get in Touch
                </span>

                <h2>
                    Let's Talk!
                </h2>

                <p>
                    We're always happy to connect with you!
                    Whether you have a question, an idea, or
                    simply want to learn more about Kelma,
                    our team would love to hear from you.
                </p>

            </div>


            <!-- CONTACT OPTIONS -->

            <div class="contact-info-card coral-card">

                <div class="emoji">📧</div>

                <div>
                    <h5>Email Us</h5>
                    <p>Send us your questions and suggestions.</p>
                </div>

            </div>


            <div class="contact-info-card yellow-card">

                <div class="emoji">💡</div>

                <div>
                    <h5>Share Ideas</h5>
                    <p>We'd love to hear your feedback and ideas.</p>
                </div>

            </div>


            <div class="contact-info-card mint-card">

                <div class="emoji">🤝</div>

                <div>
                    <h5>Collaborate</h5>
                    <p>Let's work together to make a difference.</p>
                </div>

            </div>

        </div>


        <!-- RIGHT SIDE: CONTACT FORM -->

        <div class="col-lg-7">

            <div class="contact-form">

                <h3>Send Us a Message ✉️</h3>


                <p class="form-description">
                    Fill out the form below and tell us how
                    we can help. We'll be happy to hear from you!
                </p>

                <form action="" method="POST">

                    <div class="row">

                        <div class="col-md-6">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                placeholder="Enter your name"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="example@email.com"
                                required
                            >

                        </div>

                    </div>


                    <label for="inquiry">
                        I am a...
                    </label>

                    <select
                        class="form-select"
                        id="inquiry"
                        name="inquiry"
                        required
                    >

                        <option value="" selected disabled>
                            Select your inquiry type
                        </option>

                        <option value="parent">
                            Parent
                        </option>

                        <option value="therapist">
                            Speech Therapist
                        </option>

                        <option value="other">
                            Other
                        </option>

                    </select>


                    <label for="subject">
                        Subject
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="subject"
                        name="subject"
                        placeholder="What is your message about?"
                        required
                    >


                    <label for="message">
                        Your Message
                    </label>

                    <textarea
                        class="form-control"
                        id="message"
                        name="message"
                        placeholder="Communicate your thoughts with us..."
                        required
                    ></textarea>


                    <button
                        type="submit"
                        name="submit"
                        class="contact-btn"
                    >
                        Send Message 💌
                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<!-- =========================
     CLOSING SECTION
========================= -->

<section class="container contact-section">

    <div class="contact-note">

        <h2>
            Every Voice Deserves to Be Heard!
        </h2>

        <p>
            Thank you for being part of the Kelma journey.
            Together, we can help children communicate with
            confidence, one word at a time.
        </p>

    </div>

</section>

</div>


<?php require 'includes/footer.php'; ?>