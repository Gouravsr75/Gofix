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
require_once "db.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $mobile    = trim($_POST["phone"] ?? "");
    $subject   = trim($_POST["subject"] ?? "");
    $message   = trim($_POST["message"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $full_name === "" ||
        $email === "" ||
        $subject === "" ||
        $message === ""
    ) {
        $error = "Please fill all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($mobile !== "" && !preg_match('/^[0-9+\-\s]{7,15}$/', $mobile)) {

        $error = "Please enter a valid mobile number.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Insert Data
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            INSERT INTO contact_messages
            (full_name, email, mobile, subject, message)
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param(
                "sssss",
                $full_name,
                $email,
                $mobile,
                $subject,
                $message
            );

            if ($stmt->execute()) {

                $success = "Thank you! Your message has been sent successfully.";

                // Clear form
                $full_name = "";
                $email = "";
                $mobile = "";
                $subject = "";
                $message = "";

            } else {

                $error = "Something went wrong. Please try again.";
            }

            $stmt->close();

        } else {

            $error = "Database error. Please check your database configuration.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Contact HomeEase</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

/* =====================================================
   GLOBAL
===================================================== */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{

    --primary:#2563eb;
    --primary-dark:#1d4ed8;

    --blue-light:#eff6ff;

    --dark:#0f172a;
    --text:#334155;
    --muted:#64748b;

    --white:#ffffff;

    --bg:#f8fafc;

    --border:#e2e8f0;

    --green:#16a34a;
    --red:#dc2626;

    --shadow-sm:
    0 6px 20px rgba(15,23,42,.06);

    --shadow-md:
    0 18px 45px rgba(15,23,42,.10);
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Inter',sans-serif;
    color:var(--dark);
    background:#fff;
    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}

/* =====================================================
   HERO
===================================================== */

.contact-hero{

    position:relative;

    overflow:hidden;

    padding:75px 20px 85px;

    text-align:center;

    background:

    radial-gradient(
        circle at 10% 20%,
        rgba(37,99,235,.12),
        transparent 30%
    ),

    radial-gradient(
        circle at 90% 80%,
        rgba(59,130,246,.10),
        transparent 30%
    ),

    linear-gradient(
        135deg,
        #f8fbff,
        #eef5ff
    );
}

.hero-badge{

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:9px 16px;

    border-radius:30px;

    background:#fff;

    border:1px solid #dbeafe;

    color:var(--primary);

    font-size:12px;

    font-weight:700;

    margin-bottom:20px;

    box-shadow:var(--shadow-sm);
}

.contact-hero h1{

    font-size:clamp(36px,5vw,56px);

    font-weight:800;

    letter-spacing:-1.5px;

    margin-bottom:15px;
}

.contact-hero h1 span{
    color:var(--primary);
}

.contact-hero p{

    max-width:650px;

    margin:auto;

    color:var(--muted);

    font-size:15px;

    line-height:1.7;
}

/* =====================================================
   CONTACT SECTION
===================================================== */

.contact-section{

    max-width:1150px;

    margin:auto;

    padding:80px 20px 90px;

    display:grid;

    grid-template-columns:.9fr 1.1fr;

    gap:55px;

    align-items:start;
}

/* =====================================================
   LEFT
===================================================== */

.contact-info{
    position:relative;
}

.contact-info h2{

    font-size:32px;

    letter-spacing:-.7px;

    margin-bottom:12px;
}

.contact-info > p{

    color:var(--muted);

    font-size:14px;

    line-height:1.7;

    margin-bottom:30px;

    max-width:470px;
}

/* =====================================================
   INFO CARDS
===================================================== */

.info-list{

    display:flex;

    flex-direction:column;

    gap:14px;
}

.info-card{

    display:flex;

    align-items:center;

    gap:15px;

    padding:18px;

    border:1px solid var(--border);

    border-radius:17px;

    background:#fff;

    box-shadow:var(--shadow-sm);

    transition:.3s ease;
}

.info-card:hover{

    transform:translateX(5px);

    border-color:#bfdbfe;

    box-shadow:var(--shadow-md);
}

.info-icon{

    width:48px;

    height:48px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:14px;

    background:var(--blue-light);

    color:var(--primary);

    font-size:18px;
}

.info-details h4{

    font-size:14px;

    margin-bottom:5px;
}

.info-details p{

    color:var(--muted);

    font-size:12px;

    line-height:1.5;
}

/* =====================================================
   QUICK CONTACT
===================================================== */

.quick-contact{

    margin-top:25px;

    padding:20px;

    border-radius:18px;

    background:#f8fafc;

    border:1px solid var(--border);
}

.quick-contact h4{

    font-size:14px;

    margin-bottom:14px;
}

.quick-buttons{

    display:flex;

    gap:10px;
}

.quick-button{

    flex:1;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    padding:11px;

    border-radius:11px;

    font-size:12px;

    font-weight:700;

    background:#fff;

    border:1px solid var(--border);

    color:var(--dark);

    transition:.25s ease;
}

.quick-button:hover{

    color:var(--primary);

    border-color:#bfdbfe;

    background:var(--blue-light);
}

/* =====================================================
   FORM
===================================================== */

.contact-form-wrapper{

    background:#fff;

    border:1px solid var(--border);

    border-radius:24px;

    padding:35px;

    box-shadow:var(--shadow-md);
}

.form-header{

    display:flex;

    align-items:center;

    gap:14px;

    margin-bottom:28px;
}

.form-header-icon{

    width:48px;

    height:48px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:14px;

    background:var(--primary);

    color:#fff;

    font-size:18px;
}

.form-header h3{

    font-size:21px;

    margin-bottom:3px;
}

.form-header p{

    color:var(--muted);

    font-size:11px;
}

/* =====================================================
   ALERT
===================================================== */

.alert{

    padding:14px 16px;

    border-radius:12px;

    margin-bottom:22px;

    font-size:13px;

    line-height:1.5;
}

.alert-success{

    background:#f0fdf4;

    color:#166534;

    border:1px solid #bbf7d0;
}

.alert-error{

    background:#fef2f2;

    color:#991b1b;

    border:1px solid #fecaca;
}

/* =====================================================
   FORM ELEMENTS
===================================================== */

.form-group{

    margin-bottom:18px;
}

.form-row{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:15px;
}

.form-group label{

    display:block;

    font-size:12px;

    font-weight:700;

    margin-bottom:7px;
}

.required{

    color:var(--red);
}

.form-control{

    width:100%;

    padding:13px 14px;

    border:1px solid var(--border);

    border-radius:11px;

    background:#f8fafc;

    color:var(--dark);

    font-family:inherit;

    font-size:13px;

    transition:.25s ease;
}

.form-control::placeholder{

    color:#94a3b8;
}

.form-control:focus{

    outline:none;

    background:#fff;

    border-color:var(--primary);

    box-shadow:
    0 0 0 3px rgba(37,99,235,.10);
}

textarea.form-control{

    min-height:125px;

    resize:vertical;
}

/* =====================================================
   SUBMIT
===================================================== */

.submit-btn{

    width:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:9px;

    padding:14px;

    border:none;

    border-radius:12px;

    background:var(--primary);

    color:#fff;

    font-family:inherit;

    font-size:14px;

    font-weight:700;

    cursor:pointer;

    transition:.3s ease;
}

.submit-btn:hover{

    background:var(--primary-dark);

    transform:translateY(-2px);

    box-shadow:
    0 8px 20px rgba(37,99,235,.20);
}

/* =====================================================
   SUPPORT
===================================================== */

.support-section{

    background:var(--bg);

    padding:65px 20px;
}

.support-container{

    max-width:1050px;

    margin:auto;

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:20px;
}

.support-card{

    background:#fff;

    border:1px solid var(--border);

    border-radius:18px;

    padding:25px;

    text-align:center;

    box-shadow:var(--shadow-sm);

    transition:.3s ease;
}

.support-card:hover{

    transform:translateY(-5px);

    box-shadow:var(--shadow-md);

    border-color:#bfdbfe;
}

.support-icon{

    width:52px;

    height:52px;

    margin:0 auto 15px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:15px;

    background:var(--blue-light);

    color:var(--primary);

    font-size:20px;
}

.support-card h4{

    font-size:15px;

    margin-bottom:7px;
}

.support-card p{

    color:var(--muted);

    font-size:12px;

    line-height:1.5;
}

/* =====================================================
   CTA
===================================================== */

.contact-cta{

    padding:0 20px 80px;
}

.cta-box{

    max-width:1100px;

    margin:auto;

    padding:45px;

    border-radius:25px;

    background:
    linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:25px;
}

.cta-box h2{

    font-size:27px;

    margin-bottom:7px;
}

.cta-box p{

    color:#dbeafe;

    font-size:13px;
}

.cta-button{

    flex-shrink:0;

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:13px 20px;

    background:#fff;

    color:var(--primary);

    border-radius:11px;

    font-size:13px;

    font-weight:800;

    transition:.3s ease;
}

.cta-button:hover{

    transform:translateY(-3px);

    box-shadow:
    0 10px 25px rgba(0,0,0,.15);
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:900px){

    .contact-section{

        grid-template-columns:1fr;

        gap:40px;
    }

    .contact-info{

        max-width:650px;

        margin:auto;

        width:100%;
    }

    .contact-form-wrapper{

        max-width:650px;

        margin:auto;

        width:100%;
    }

    .support-container{

        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:768px){

    .contact-hero{

        padding:60px 18px 65px;
    }

    .contact-hero h1{

        font-size:36px;

        letter-spacing:-1px;
    }

    .contact-hero p{

        font-size:14px;
    }

    .contact-section{

        padding:60px 18px 70px;
    }

    .contact-info h2{

        font-size:28px;
    }

    .contact-form-wrapper{

        padding:25px;

        border-radius:20px;
    }

    .form-row{

        grid-template-columns:1fr;

        gap:0;
    }

    .support-section{

        padding:55px 18px;
    }

    .support-container{

        grid-template-columns:1fr;
    }

    .contact-cta{

        padding:0 18px 65px;
    }

    .cta-box{

        padding:35px 25px;

        border-radius:20px;

        flex-direction:column;

        align-items:flex-start;
    }

    .cta-box h2{

        font-size:24px;
    }

    .cta-button{

        width:100%;

        justify-content:center;
    }
}

@media(max-width:480px){

    .contact-hero h1{

        font-size:32px;
    }

    .info-card{

        padding:15px;
    }

    .info-icon{

        width:43px;

        height:43px;

        border-radius:12px;
    }

    .quick-buttons{

        flex-direction:column;
    }

    .contact-form-wrapper{

        padding:21px;
    }
}

</style>

</head>

<body>

<!-- =====================================================
     HERO
===================================================== -->

<section class="contact-hero">

    <div class="hero-badge">

        <i class="fa-solid fa-headset"></i>

        We're Here To Help

    </div>

    <h1>

        Get in <span>Touch</span>

    </h1>

    <p>

        Have a question about a service or need help
        with your booking? Contact the HomeEase team
        and we'll be happy to help.

    </p>

</section>


<!-- =====================================================
     CONTACT AREA
===================================================== -->

<section class="contact-section">

    <!-- LEFT -->

    <div class="contact-info">

        <h2>
            We'd Love To Hear From You
        </h2>

        <p>

            Whether you have a question about services,
            bookings or anything else, our team is ready
            to help you.

        </p>


        <div class="info-list">

            <!-- LOCATION -->

            <div class="info-card">

                <div class="info-icon">

                    <i class="fa-solid fa-location-dot"></i>

                </div>

                <div class="info-details">

                    <h4>
                        Our Office
                    </h4>

                    <p>
                        123 More Street, Udhwa Block,
                        <br>
                        Udhwa, Jharkhand 700016
                    </p>

                </div>

            </div>


            <!-- PHONE -->

            <div class="info-card">

                <div class="info-icon">

                    <i class="fa-solid fa-phone"></i>

                </div>

                <div class="info-details">

                    <h4>
                        Phone
                    </h4>

                    <p>
                        +91 82101 13048
                    </p>

                    <p>
                        Support: +91 95463 28811
                    </p>

                </div>

            </div>


            <!-- EMAIL -->

            <div class="info-card">

                <div class="info-icon">

                    <i class="fa-solid fa-envelope"></i>

                </div>

                <div class="info-details">

                    <h4>
                        Email
                    </h4>

                    <p>
                        support@gofix.com
                    </p>

                    <p>
                        partners@gofix.com
                    </p>

                </div>

            </div>


            <!-- HOURS -->

            <div class="info-card">

                <div class="info-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>

                <div class="info-details">

                    <h4>
                        Working Hours
                    </h4>

                    <p>
                        Monday - Sunday
                    </p>

                    <p>
                        08:00 AM - 10:00 PM
                    </p>

                </div>

            </div>

        </div>


        <!-- QUICK CONTACT -->

        <div class="quick-contact">

            <h4>
                Quick Contact
            </h4>

            <div class="quick-buttons">

                <a
                    href="tel:+919546328811"
                    class="quick-button"
                >

                    <i class="fa-solid fa-phone"></i>

                    Call Us

                </a>


                <a
                    href="mailto:support@gofix.com"
                    class="quick-button"
                >

                    <i class="fa-solid fa-envelope"></i>

                    Email Us

                </a>

            </div>

        </div>

    </div>


    <!-- RIGHT FORM -->

    <div class="contact-form-wrapper">

        <div class="form-header">

            <div class="form-header-icon">

                <i class="fa-regular fa-paper-plane"></i>

            </div>

            <div>

                <h3>
                    Send Us a Message
                </h3>

                <p>
                    Fill out the form and we'll get back to you.
                </p>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if ($success !== ""): ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            action=""
            method="POST"
            autocomplete="off"
        >

            <!-- NAME + EMAIL -->

            <div class="form-row">

                <div class="form-group">

                    <label for="name">

                        Full Name

                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="Enter your name"
                        value="<?= htmlspecialchars($full_name ?? '') ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">

                        Email Address

                        <span class="required">*</span>

                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        maxlength="150"
                        required
                    >

                </div>

            </div>


            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">
                    Mobile Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control"
                    placeholder="Enter your mobile number"
                    value="<?= htmlspecialchars($mobile ?? '') ?>"
                    maxlength="15"
                >

            </div>


            <!-- SUBJECT -->

            <div class="form-group">

                <label for="subject">

                    Subject

                    <span class="required">*</span>

                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    class="form-control"
                    placeholder="How can we help you?"
                    value="<?= htmlspecialchars($subject ?? '') ?>"
                    maxlength="200"
                    required
                >

            </div>


            <!-- MESSAGE -->

            <div class="form-group">

                <label for="message">

                    Message

                    <span class="required">*</span>

                </label>

                <textarea
                    id="message"
                    name="message"
                    class="form-control"
                    placeholder="Write your message here..."
                    maxlength="5000"
                    required
                ><?= htmlspecialchars($message ?? '') ?></textarea>

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="submit-btn"
            >

                Send Message

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>

    </div>

</section>


<!-- =====================================================
     SUPPORT OPTIONS
===================================================== -->

<section class="support-section">

    <div class="support-container">


        <div class="support-card">

            <div class="support-icon">

                <i class="fa-solid fa-calendar-check"></i>

            </div>

            <h4>
                Booking Help
            </h4>

            <p>

                Need help with an existing service
                booking? Contact our support team.

            </p>

        </div>


        <div class="support-card">

            <div class="support-icon">

                <i class="fa-solid fa-screwdriver-wrench"></i>

            </div>

            <h4>
                Service Support
            </h4>

            <p>

                Questions about our home services?
                We're ready to assist you.

            </p>

        </div>


        <div class="support-card">

            <div class="support-icon">

                <i class="fa-solid fa-user-tie"></i>

            </div>

            <h4>
                Partner Support
            </h4>

            <p>

                Want to join HomeEase as a professional?
                Get in touch with our team.

            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     CTA
===================================================== -->

<section class="contact-cta">

    <div class="cta-box">

        <div>

            <h2>
                Looking for a home service?
            </h2>

            <p>

                Explore our services and book the help
                you need for your home.

            </p>

        </div>

        <a
            href="more.php"
            class="cta-button"
        >

            Explore Services

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>

</section>


<!-- =====================================================
     MOBILE MENU
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function(){

        const menuToggle =
            document.getElementById("menuToggle");

        const mobileNav =
            document.getElementById("mobileNav");

        if(menuToggle && mobileNav){

            menuToggle.addEventListener(
                "click",
                function(){

                    mobileNav.classList.toggle("active");

                    const icon =
                        menuToggle.querySelector("i");

                    if(icon){

                        if(
                            mobileNav.classList.contains(
                                "active"
                            )
                        ){

                            icon.classList.remove(
                                "fa-bars"
                            );

                            icon.classList.add(
                                "fa-xmark"
                            );

                        }
                        else{

                            icon.classList.remove(
                                "fa-xmark"
                            );

                            icon.classList.add(
                                "fa-bars"
                            );

                        }

                    }

                }
            );

        }

    }
);

</script>

</body>
</html>

<?php include "bottom.php"; ?>
