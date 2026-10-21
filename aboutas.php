
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

<title>About HomeEase</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">

<style>

/* =====================================
   GLOBAL
===================================== */

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

    --white:#fff;
    --bg:#f8fafc;
    --border:#e2e8f0;

    --green:#16a34a;

    --shadow-sm:0 6px 20px rgba(15,23,42,.06);
    --shadow-md:0 15px 40px rgba(15,23,42,.10);
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
}


/* =====================================
   HERO
===================================== */

.about-hero{
    position:relative;
    overflow:hidden;

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

    padding:80px 20px;
}

.about-hero-container{
    max-width:1180px;
    margin:auto;

    display:grid;
    grid-template-columns:1fr 1fr;
    align-items:center;

    gap:65px;
}


/* HERO CONTENT */

.hero-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;

    background:#fff;
    border:1px solid #dbeafe;

    color:var(--primary);

    padding:9px 15px;
    border-radius:30px;

    font-size:12px;
    font-weight:700;

    margin-bottom:20px;

    box-shadow:var(--shadow-sm);
}

.about-hero-content h1{
    font-size:clamp(38px,5vw,58px);
    line-height:1.08;

    letter-spacing:-1.8px;

    font-weight:800;

    margin-bottom:20px;
}

.about-hero-content h1 span{
    color:var(--primary);
}

.about-hero-content p{
    max-width:560px;

    font-size:16px;
    line-height:1.75;

    color:var(--muted);

    margin-bottom:30px;
}


/* BUTTON */

.hero-button{
    display:inline-flex;
    align-items:center;
    gap:10px;

    padding:14px 22px;

    background:var(--primary);
    color:#fff;

    border-radius:12px;

    font-size:14px;
    font-weight:700;

    transition:.3s ease;

    box-shadow:0 8px 20px rgba(37,99,235,.22);
}

.hero-button:hover{
    background:var(--primary-dark);
    transform:translateY(-3px);
}


/* HERO IMAGE */

.about-hero-image{
    position:relative;
}

.about-hero-image img{
    width:100%;
    height:430px;

    object-fit:cover;

    border-radius:28px;

    display:block;

    box-shadow:var(--shadow-md);
}


/* floating card */

.hero-floating-card{
    position:absolute;

    left:-25px;
    bottom:25px;

    background:#fff;

    padding:15px 18px;

    border-radius:15px;

    display:flex;
    align-items:center;
    gap:12px;

    box-shadow:var(--shadow-md);
}

.hero-floating-icon{
    width:42px;
    height:42px;

    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ecfdf5;
    color:var(--green);
}

.hero-floating-card strong{
    display:block;
    font-size:14px;
}

.hero-floating-card small{
    color:var(--muted);
    font-size:11px;
}


/* =====================================
   INTRO
===================================== */

.intro-section{
    max-width:950px;
    margin:auto;

    padding:85px 20px;

    text-align:center;
}

.section-label{
    color:var(--primary);

    font-size:12px;
    font-weight:800;

    letter-spacing:1.5px;
    text-transform:uppercase;

    margin-bottom:10px;
}

.intro-section h2{
    font-size:clamp(28px,4vw,40px);

    letter-spacing:-.8px;

    margin-bottom:18px;
}

.intro-section p{
    color:var(--muted);

    max-width:760px;

    margin:auto;

    line-height:1.8;
    font-size:15px;
}


/* =====================================
   STATS
===================================== */

.stats-section{
    padding:70px 20px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color:#fff;
}

.stats-grid{
    max-width:1100px;
    margin:auto;

    display:grid;
    grid-template-columns:repeat(4,1fr);

    gap:20px;
}

.stat-card{
    text-align:center;

    padding:25px 15px;

    border-right:1px solid rgba(255,255,255,.20);
}

.stat-card:last-child{
    border-right:none;
}

.stat-icon{
    width:44px;
    height:44px;

    margin:0 auto 13px;

    border-radius:13px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:rgba(255,255,255,.13);

    font-size:17px;
}

.stat-card h2{
    font-size:36px;
    font-weight:800;

    margin-bottom:6px;
}

.stat-card p{
    font-size:13px;
    opacity:.85;
}


/* =====================================
   WHY HOME EASE
===================================== */

.values-section{
    background:var(--bg);

    padding:90px 20px;
}

.values-heading{
    text-align:center;

    max-width:700px;

    margin:0 auto 55px;
}

.values-heading h2{
    font-size:clamp(28px,4vw,40px);

    letter-spacing:-.8px;

    margin-bottom:12px;
}

.values-heading p{
    color:var(--muted);

    font-size:14px;
    line-height:1.7;
}

.values-grid{
    max-width:1100px;
    margin:auto;

    display:grid;
    grid-template-columns:repeat(3,1fr);

    gap:24px;
}

.value-card{
    background:#fff;

    border:1px solid var(--border);

    border-radius:22px;

    padding:32px 27px;

    transition:.3s ease;

    box-shadow:var(--shadow-sm);
}

.value-card:hover{
    transform:translateY(-7px);

    border-color:#bfdbfe;

    box-shadow:var(--shadow-md);
}

.value-icon{
    width:58px;
    height:58px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:17px;

    background:var(--blue-light);
    color:var(--primary);

    font-size:22px;

    margin-bottom:22px;

    transition:.3s ease;
}

.value-card:hover .value-icon{
    background:var(--primary);
    color:#fff;
}

.value-card h3{
    font-size:18px;
    margin-bottom:10px;
}

.value-card p{
    color:var(--muted);

    font-size:13px;
    line-height:1.7;
}


/* =====================================
   HOW WE HELP
===================================== */

.help-section{
    max-width:1100px;

    margin:auto;

    padding:90px 20px;
}

.help-container{
    display:grid;

    grid-template-columns:.9fr 1.1fr;

    gap:70px;

    align-items:center;
}

.help-image img{
    width:100%;
    height:420px;

    object-fit:cover;

    border-radius:25px;

    box-shadow:var(--shadow-md);
}

.help-content h2{
    font-size:36px;

    letter-spacing:-1px;

    margin-bottom:18px;
}

.help-content > p{
    color:var(--muted);

    line-height:1.7;

    font-size:14px;

    margin-bottom:30px;
}

.help-list{
    display:flex;
    flex-direction:column;

    gap:18px;
}

.help-item{
    display:flex;
    align-items:flex-start;

    gap:14px;
}

.help-check{
    flex-shrink:0;

    width:35px;
    height:35px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ecfdf5;
    color:var(--green);

    border-radius:10px;

    font-size:14px;
}

.help-item h4{
    font-size:14px;

    margin-bottom:4px;
}

.help-item p{
    color:var(--muted);

    font-size:12px;

    line-height:1.5;
}


/* =====================================
   CTA
===================================== */

.about-cta{
    padding:0 20px 90px;
}

.cta-box{
    max-width:1100px;

    margin:auto;

    padding:55px 45px;

    border-radius:28px;

    color:#fff;

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

    display:flex;
    justify-content:space-between;
    align-items:center;

    gap:30px;
}

.cta-box h2{
    font-size:30px;

    margin-bottom:8px;
}

.cta-box p{
    color:#dbeafe;

    font-size:13px;
}

.cta-button{
    flex-shrink:0;

    display:inline-flex;

    align-items:center;
    gap:9px;

    background:#fff;
    color:var(--primary);

    padding:14px 22px;

    border-radius:12px;

    font-size:13px;
    font-weight:800;

    transition:.3s ease;
}

.cta-button:hover{
    transform:translateY(-3px);

    box-shadow:0 10px 25px rgba(0,0,0,.15);
}


/* =====================================
   RESPONSIVE
===================================== */

@media(max-width:1000px){

    .about-hero-container{
        grid-template-columns:1fr;

        text-align:center;
    }

    .about-hero-content p{
        margin-left:auto;
        margin-right:auto;
    }

    .about-hero-image{
        max-width:650px;
        width:100%;
        margin:auto;
    }

    .hero-floating-card{
        left:15px;
    }

    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .stat-card:nth-child(2){
        border-right:none;
    }

    .values-grid{
        grid-template-columns:1fr 1fr;
    }

    .help-container{
        grid-template-columns:1fr;
    }

    .help-image{
        max-width:650px;
        margin:auto;
    }
}


@media(max-width:768px){

    .about-hero{
        padding:60px 18px 70px;
    }

    .about-hero-content h1{
        font-size:36px;
        letter-spacing:-1px;
    }

    .about-hero-content p{
        font-size:14px;
    }

    .about-hero-image img{
        height:330px;
        border-radius:22px;
    }

    .hero-floating-card{
        left:10px;
        bottom:15px;
    }

    .intro-section{
        padding:65px 18px;
    }

    .stats-section{
        padding:45px 18px;
    }

    .stat-card{
        border-right:none;
        padding:18px 10px;
    }

    .stat-card h2{
        font-size:30px;
    }

    .values-section{
        padding:65px 18px;
    }

    .values-grid{
        grid-template-columns:1fr;
    }

    .help-section{
        padding:65px 18px;
    }

    .help-content h2{
        font-size:29px;
    }

    .help-image img{
        height:320px;
    }

    .about-cta{
        padding:0 18px 70px;
    }

    .cta-box{
        padding:38px 25px;

        border-radius:22px;

        flex-direction:column;

        align-items:flex-start;
    }

    .cta-box h2{
        font-size:25px;
    }

    .cta-button{
        width:100%;
        justify-content:center;
    }
}


@media(max-width:480px){

    .about-hero-content h1{
        font-size:32px;
    }

    .about-hero-image img{
        height:270px;
    }

    .hero-floating-card{
        padding:11px 13px;
    }

    .hero-floating-icon{
        width:36px;
        height:36px;
    }

    .hero-floating-card strong{
        font-size:12px;
    }

    .hero-floating-card small{
        font-size:9px;
    }

    .stats-grid{
        gap:5px;
    }

    .stat-card h2{
        font-size:25px;
    }

    .stat-card p{
        font-size:11px;
    }

    .value-card{
        padding:25px 22px;
    }

    .help-content h2{
        font-size:27px;
    }

}

</style>

</head>

<body>


<!-- =========================================
     HERO
========================================= -->

<section class="about-hero">

    <div class="about-hero-container">

        <div class="about-hero-content">

            <div class="hero-badge">
                <i class="fa-solid fa-house"></i>
                Your Home. Our Responsibility.
            </div>

            <h1>
                Making Home Services
                <span>Simple & Reliable</span>
            </h1>

            <p>
                HomeEase connects you with professionals for
                everyday home services. From cleaning and
                electrical work to repairs and maintenance,
                we make booking help for your home simple.
            </p>

            <a href="more.php" class="hero-button">
                Explore Services
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>


        <div class="about-hero-image">

            <img
                src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=85"
                alt="Home service professional"
            >

            <div class="hero-floating-card">

                <div class="hero-floating-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <strong>Easy Booking</strong>
                    <small>Simple home services</small>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     INTRO
========================================= -->

<section class="intro-section">

    <div class="section-label">
        About HomeEase
    </div>

    <h2>
        Home Services, Without The Hassle
    </h2>

    <p>
        HomeEase is designed to make it easier for homeowners
        to discover and book services they need. Our platform
        brings different home-service categories together in
        one convenient place, helping customers save time and
        manage their service bookings more easily.
    </p>

</section>


<!-- =========================================
     STATS
========================================= -->

<section class="stats-section">

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <h2>15K+</h2>
            <p>Happy Customers</p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-user-check"></i>
            </div>

            <h2>500+</h2>
            <p>Verified Professionals</p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>

            <h2>50+</h2>
            <p>Cities Covered</p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-star"></i>
            </div>

            <h2>4.8/5</h2>
            <p>Average Rating</p>

        </div>

    </div>

</section>


<!-- =========================================
     VALUES
========================================= -->

<section class="values-section">

    <div class="values-heading">

        <div class="section-label">
            Why HomeEase
        </div>

        <h2>
            Built Around Your Convenience
        </h2>

        <p>
            We focus on making the home-service booking
            experience straightforward, transparent and
            convenient.
        </p>

    </div>


    <div class="values-grid">


        <div class="value-card">

            <div class="value-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h3>
                Verified Professionals
            </h3>

            <p>
                Professionals can be reviewed and verified
                before becoming available for service
                bookings.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                <i class="fa-solid fa-tags"></i>
            </div>

            <h3>
                Transparent Pricing
            </h3>

            <p>
                Service information and applicable pricing
                can be shown clearly before customers
                confirm their booking.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                <i class="fa-solid fa-medal"></i>
            </div>

            <h3>
                Quality Service
            </h3>

            <p>
                Our platform is designed to help customers
                find suitable professionals and manage
                their service requests easily.
            </p>

        </div>


    </div>

</section>


<!-- =========================================
     HOW WE HELP
========================================= -->

<section class="help-section">

    <div class="help-container">


        <div class="help-image">

            <img
                src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=900&q=85"
                alt="Home service"
            >

        </div>


        <div class="help-content">

            <div class="section-label">
                What We Do
            </div>

            <h2>
                Everything You Need For Your Home
            </h2>

            <p>
                From everyday cleaning to electrical repairs,
                plumbing, appliance services and more, HomeEase
                brings useful home services together in one
                platform.
            </p>


            <div class="help-list">


                <div class="help-item">

                    <div class="help-check">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <h4>
                            Multiple Home Services
                        </h4>

                        <p>
                            Find different service categories
                            from one platform.
                        </p>
                    </div>

                </div>


                <div class="help-item">

                    <div class="help-check">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <h4>
                            Simple Booking
                        </h4>

                        <p>
                            Select your service, date and
                            preferred time.
                        </p>
                    </div>

                </div>


                <div class="help-item">

                    <div class="help-check">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <h4>
                            Manage Your Bookings
                        </h4>

                        <p>
                            Keep track of your service requests
                            and booking history.
                        </p>
                    </div>

                </div>


            </div>

        </div>

    </div>

</section>


<!-- =========================================
     CTA
========================================= -->

<section class="about-cta">

    <div class="cta-box">

        <div>

            <h2>
                Ready to make your home life easier?
            </h2>

            <p>
                Explore HomeEase services and find the help
                you need.
            </p>

        </div>

        <a href="more.php" class="cta-button">

            Explore Services

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>

</section>


<!-- =========================================
     MOBILE MENU
========================================= -->

<script>

document.addEventListener("DOMContentLoaded", function(){

    const menuToggle =
        document.getElementById("menuToggle");

    const mobileNav =
        document.getElementById("mobileNav");


    if(menuToggle && mobileNav){

        menuToggle.addEventListener("click", function(){

            mobileNav.classList.toggle("active");

            const icon =
                menuToggle.querySelector("i");

            if(icon){

                if(mobileNav.classList.contains("active")){

                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-xmark");

                }else{

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
