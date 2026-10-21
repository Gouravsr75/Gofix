<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (matches common session keys like user_id, user, or email)
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']) || isset($_SESSION['email']);

// Conditionally load the appropriate header/navbar
if ($isLoggedIn) {
    include 'nav.php';   // Active navbar for logged-in users
} else {
    include 'top.php';   // Default navbar for guests
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>How HomeEase Works</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>

        /* ==============================
           GLOBAL
        ============================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --blue-light: #eff6ff;

            --dark: #0f172a;
            --text: #334155;
            --muted: #64748b;

            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e2e8f0;

            --green: #16a34a;

            --shadow-sm: 0 5px 20px rgba(15, 23, 42, .06);
            --shadow-md: 0 15px 40px rgba(15, 23, 42, .10);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--white);
            color: var(--dark);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }


        /* ==============================
           HERO
        ============================== */

        .works-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, .12), transparent 28%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, .10), transparent 30%),
                linear-gradient(135deg, #f8fbff, #eef5ff);

            padding: 90px 20px 100px;
            text-align: center;
        }

        .hero-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(37, 99, 235, .06);
            pointer-events: none;
        }

        .hero-decoration.one {
            width: 180px;
            height: 180px;
            top: -80px;
            left: -50px;
        }

        .hero-decoration.two {
            width: 250px;
            height: 250px;
            right: -100px;
            bottom: -130px;
        }

        .hero-content {
            max-width: 850px;
            margin: auto;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 9px 16px;
            border-radius: 30px;

            background: rgba(255, 255, 255, .85);
            border: 1px solid #dbeafe;

            color: var(--primary);
            font-size: 13px;
            font-weight: 700;

            box-shadow: var(--shadow-sm);

            margin-bottom: 22px;
        }

        .hero-badge i {
            font-size: 13px;
        }

        .hero-content h1 {
            font-size: clamp(36px, 5vw, 58px);
            line-height: 1.08;
            letter-spacing: -1.8px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero-content h1 span {
            color: var(--primary);
        }

        .hero-content p {
            max-width: 680px;
            margin: auto;

            font-size: 17px;
            line-height: 1.7;
            color: var(--muted);
        }


        /* ==============================
           MAIN PROCESS
        ============================== */

        .process-section {
            max-width: 1180px;
            margin: auto;
            padding: 90px 20px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 65px;
        }

        .section-heading .small-title {
            color: var(--primary);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            margin-bottom: 10px;
        }

        .section-heading h2 {
            font-size: clamp(28px, 4vw, 40px);
            letter-spacing: -.8px;
            margin-bottom: 12px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 15px;
        }


        /* ==============================
           PROCESS GRID
        ============================== */

        .process-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;

            position: relative;
        }

        /* connecting line */

        .process-grid::before {
            content: "";

            position: absolute;

            top: 43px;
            left: 12%;
            right: 12%;

            height: 2px;

            background: linear-gradient(
                90deg,
                #bfdbfe,
                #60a5fa,
                #bfdbfe
            );

            z-index: 0;
        }

        .process-card {
            position: relative;
            z-index: 2;

            background: var(--white);
            border: 1px solid var(--border);

            border-radius: 22px;

            padding: 30px 24px;

            text-align: center;

            box-shadow: var(--shadow-sm);

            transition:
                transform .3s ease,
                box-shadow .3s ease,
                border-color .3s ease;
        }

        .process-card:hover {
            transform: translateY(-8px);
            border-color: #bfdbfe;
            box-shadow: var(--shadow-md);
        }


        /* number */

        .process-number {
            width: 38px;
            height: 38px;

            position: absolute;

            top: -18px;
            left: 50%;

            transform: translateX(-50%);

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--primary);
            color: white;

            font-size: 14px;
            font-weight: 800;

            border: 4px solid white;

            box-shadow: 0 4px 12px rgba(37, 99, 235, .25);
        }


        /* icon */

        .process-icon {
            width: 82px;
            height: 82px;

            margin: 8px auto 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 22px;

            background: var(--blue-light);

            color: var(--primary);

            font-size: 31px;

            transition: .3s ease;
        }

        .process-card:hover .process-icon {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        .process-card h3 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        .process-card p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--muted);
        }


        /* ==============================
           MINI BENEFITS
        ============================== */

        .benefits-section {
            background: var(--bg);
            padding: 75px 20px;
        }

        .benefits-container {
            max-width: 1050px;
            margin: auto;

            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .benefit {
            background: white;

            border: 1px solid var(--border);
            border-radius: 18px;

            padding: 28px;

            display: flex;
            align-items: flex-start;
            gap: 16px;

            box-shadow: var(--shadow-sm);
        }

        .benefit-icon {
            flex-shrink: 0;

            width: 48px;
            height: 48px;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #ecfdf5;
            color: var(--green);

            font-size: 19px;
        }

        .benefit h4 {
            font-size: 15px;
            margin-bottom: 6px;
        }

        .benefit p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }


        /* ==============================
           FAQ
        ============================== */

        .faq-section {
            max-width: 850px;
            margin: auto;

            padding: 90px 20px 100px;
        }

        .faq-heading {
            text-align: center;
            margin-bottom: 45px;
        }

        .faq-heading h2 {
            font-size: clamp(28px, 4vw, 38px);
            margin-bottom: 10px;
        }

        .faq-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .faq-item {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: white;

            transition: .25s ease;
        }

        .faq-item:hover {
            border-color: #bfdbfe;
        }

        .faq-question {
            width: 100%;

            padding: 21px 22px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            background: white;
            border: none;

            cursor: pointer;

            text-align: left;

            font-size: 15px;
            font-weight: 700;

            color: var(--dark);
        }

        .faq-question i {
            width: 32px;
            height: 32px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--blue-light);
            color: var(--primary);

            transition: transform .3s ease;
        }

        .faq-question.active i {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;

            padding: 0 22px;

            color: var(--muted);

            font-size: 14px;
            line-height: 1.7;

            transition:
                max-height .35s ease,
                padding .35s ease;
        }

        .faq-question.active + .faq-answer {
            max-height: 250px;
            padding: 0 22px 22px;
        }


        /* ==============================
           CTA
        ============================== */

        .cta-section {
            padding: 0 20px 90px;
        }

        .cta-box {
            max-width: 1100px;
            margin: auto;

            padding: 55px 40px;

            border-radius: 28px;

            background:
                radial-gradient(
                    circle at 90% 10%,
                    rgba(255,255,255,.18),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 30px;
        }

        .cta-text h2 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .cta-text p {
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.6;
        }

        .cta-button {
            flex-shrink: 0;

            display: inline-flex;
            align-items: center;
            gap: 9px;

            background: white;
            color: var(--primary);

            padding: 14px 22px;

            border-radius: 12px;

            font-size: 14px;
            font-weight: 800;

            transition: .25s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,.15);
        }


        /* ==============================
           MOBILE MENU
        ============================== */

        .mobile-menu-btn {
            display: none;
        }


        /* ==============================
           RESPONSIVE
        ============================== */

        @media (max-width: 950px) {

            .process-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .process-grid::before {
                display: none;
            }

            .benefits-container {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .works-hero {
                padding: 65px 18px 70px;
            }

            .hero-content h1 {
                letter-spacing: -1px;
            }

            .hero-content p {
                font-size: 15px;
            }

            .process-section {
                padding: 65px 18px;
            }

            .section-heading {
                margin-bottom: 50px;
            }

            .process-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .process-card {
                padding: 30px 22px;
            }

            .process-icon {
                width: 72px;
                height: 72px;
                font-size: 27px;
            }

            .benefits-section {
                padding: 60px 18px;
            }

            .benefit {
                padding: 22px;
            }

            .faq-section {
                padding: 65px 18px 75px;
            }

            .faq-question {
                padding: 18px;
                font-size: 14px;
            }

            .faq-answer {
                font-size: 13px;
            }

            .cta-section {
                padding: 0 18px 70px;
            }

            .cta-box {
                padding: 38px 25px;
                border-radius: 22px;

                flex-direction: column;
                align-items: flex-start;
            }

            .cta-text h2 {
                font-size: 25px;
            }

            .cta-button {
                width: 100%;
                justify-content: center;
            }
        }


        @media (max-width: 480px) {

            .works-hero {
                padding-top: 55px;
            }

            .hero-badge {
                font-size: 11px;
            }

            .hero-content h1 {
                font-size: 34px;
            }

            .hero-content p {
                font-size: 14px;
            }

            .process-card h3 {
                font-size: 17px;
            }

            .process-card p {
                font-size: 13px;
            }

            .section-heading h2,
            .faq-heading h2 {
                font-size: 27px;
            }

            .cta-text h2 {
                font-size: 23px;
            }
        }

    </style>
</head>

<body>


<!-- =========================================
     HERO
========================================= -->

<section class="works-hero">

    <div class="hero-decoration one"></div>
    <div class="hero-decoration two"></div>

    <div class="hero-content">

        <div class="hero-badge">
            <i class="fa-solid fa-circle-check"></i>
            Simple • Fast • Reliable
        </div>

        <h1>
            How <span>Gofix</span> Works
        </h1>

        <p>
            Getting trusted home services is simple.
            Choose a service, select a convenient time,
            book a professional, and relax.
        </p>

    </div>

</section>


<!-- =========================================
     PROCESS
========================================= -->

<section class="process-section">

    <div class="section-heading">

        <div class="small-title">
            Simple Process
        </div>

        <h2>
            Get Your Service in 4 Easy Steps
        </h2>

        <p>
            Everything you need, from booking to completion.
        </p>

    </div>


    <div class="process-grid">


        <!-- STEP 1 -->

        <div class="process-card">

            <div class="process-number">
                1
            </div>

            <div class="process-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>

            <h3>
                Choose a Service
            </h3>

            <p>
                Browse HomeEase services and select the
                service you need for your home.
            </p>

        </div>


        <!-- STEP 2 -->

        <div class="process-card">

            <div class="process-number">
                2
            </div>

            <div class="process-icon">
                <i class="fa-regular fa-calendar-days"></i>
            </div>

            <h3>
                Pick Date & Time
            </h3>

            <p>
                Select a convenient date and available
                time slot according to your schedule.
            </p>

        </div>


        <!-- STEP 3 -->

        <div class="process-card">

            <div class="process-number">
                3
            </div>

            <div class="process-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <h3>
                Confirm Booking
            </h3>

            <p>
                Check your booking details and confirm
                your service request securely.
            </p>

        </div>


        <!-- STEP 4 -->

        <div class="process-card">

            <div class="process-number">
                4
            </div>

            <div class="process-icon">
                <i class="fa-solid fa-house-circle-check"></i>
            </div>

            <h3>
                Get It Done
            </h3>

            <p>
                A professional arrives at your doorstep
                and completes the requested service.
            </p>

        </div>

    </div>

</section>


<!-- =========================================
     BENEFITS
========================================= -->

<section class="benefits-section">

    <div class="section-heading">

        <div class="small-title">
            Why HomeEase
        </div>

        <h2>
            Home Services Made Easier
        </h2>

        <p>
            Designed to make everyday home maintenance simple.
        </p>

    </div>


    <div class="benefits-container">


        <div class="benefit">

            <div class="benefit-icon">
                <i class="fa-solid fa-user-check"></i>
            </div>

            <div>
                <h4>
                    Trusted Professionals
                </h4>

                <p>
                    Connect with professionals available
                    for your selected service.
                </p>
            </div>

        </div>


        <div class="benefit">

            <div class="benefit-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <h4>
                    Convenient Scheduling
                </h4>

                <p>
                    Select a suitable date and time
                    for your booking.
                </p>
            </div>

        </div>


        <div class="benefit">

            <div class="benefit-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div>
                <h4>
                    Easy & Secure
                </h4>

                <p>
                    Simple booking experience with
                    clear service information.
                </p>
            </div>

        </div>

    </div>

</section>


<!-- =========================================
     FAQ
========================================= -->

<section class="faq-section">

    <div class="faq-heading">

        <h2>
            Frequently Asked Questions
        </h2>

        <p>
            Everything you need to know about using HomeEase.
        </p>

    </div>


    <div class="faq-list">


        <!-- FAQ 1 -->

        <div class="faq-item">

            <button class="faq-question">

                <span>
                    Are your professionals verified?
                </span>

                <i class="fa-solid fa-chevron-down"></i>

            </button>

            <div class="faq-answer">

                Yes, professionals on the platform can be
                verified before being allowed to accept
                service bookings.

            </div>

        </div>


        <!-- FAQ 2 -->

        <div class="faq-item">

            <button class="faq-question">

                <span>
                    Can I cancel or reschedule my booking?
                </span>

                <i class="fa-solid fa-chevron-down"></i>

            </button>

            <div class="faq-answer">

                Yes. Booking cancellation and rescheduling
                can be managed according to the cancellation
                policy configured by HomeEase.

            </div>

        </div>


        <!-- FAQ 3 -->

        <div class="faq-item">

            <button class="faq-question">

                <span>
                    How do I know the booking is confirmed?
                </span>

                <i class="fa-solid fa-chevron-down"></i>

            </button>

            <div class="faq-answer">

                After successfully confirming a booking,
                your booking details can be displayed in
                your booking history or confirmation page.

            </div>

        </div>


        <!-- FAQ 4 -->

        <div class="faq-item">

            <button class="faq-question">

                <span>
                    Are there any hidden charges?
                </span>

                <i class="fa-solid fa-chevron-down"></i>

            </button>

            <div class="faq-answer">

                HomeEase can display the service price and
                applicable charges clearly before the booking
                is confirmed.

            </div>

        </div>


    </div>

</section>


<!-- =========================================
     CTA
========================================= -->

<section class="cta-section">

    <div class="cta-box">

        <div class="cta-text">

            <h2>
                Need a service at home?
            </h2>

            <p>
                Find the right service and book it in just
                a few simple steps.
            </p>

        </div>

        <a href="more.php" class="cta-button">

            Explore Services

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>

</section>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =========================
       FAQ ACCORDION
    ========================= */

    const faqQuestions =
        document.querySelectorAll(".faq-question");


    faqQuestions.forEach(function (question) {

        question.addEventListener("click", function () {

            const currentAnswer =
                this.nextElementSibling;

            const isActive =
                this.classList.contains("active");


            /* Close all */

            faqQuestions.forEach(function (item) {

                item.classList.remove("active");

                item.nextElementSibling.style.maxHeight = null;

            });


            /* Open selected */

            if (!isActive) {

                this.classList.add("active");

                currentAnswer.style.maxHeight =
                    currentAnswer.scrollHeight + "px";

            }

        });

    });


    /* =========================
       MOBILE MENU
       Compatible with top.php
    ========================= */

    const menuToggle =
        document.getElementById("menuToggle");

    const mobileNav =
        document.getElementById("mobileNav");


    if (menuToggle && mobileNav) {

        menuToggle.addEventListener("click", function () {

            mobileNav.classList.toggle("active");

            const icon =
                menuToggle.querySelector("i");


            if (mobileNav.classList.contains("active")) {

                if (icon) {

                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-xmark");

                }

            } else {

                if (icon) {

                    icon.classList.remove("fa-xmark");
                    icon.classList.add("fa-bars");

                }

            }

        });

    }


});

</script>


</body>
</html>
