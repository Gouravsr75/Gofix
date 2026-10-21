<?php
/*
|--------------------------------------------------------------------------
| main/dashboard.php
|--------------------------------------------------------------------------
| Login check + no-cache headers: auth.php में हैं (session_start भी वहीं है)।
| इस लाइन से पहले कोई भी आउटपुट या खाली लाइन नहीं होनी चाहिए।
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HomeEase - Home Services</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

/* =========================================================
   GLOBAL
========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --primary:#2563eb;
    --primary-dark:#1d4ed8;
    --primary-soft:#eff6ff;

    --dark:#0f172a;
    --text:#334155;
    --muted:#64748b;

    --white:#ffffff;
    --bg:#f8fafc;

    --border:#e2e8f0;

    --green:#16a34a;
    --yellow:#f59e0b;

    --shadow-sm:0 5px 20px rgba(15,23,42,.06);
    --shadow-md:0 15px 40px rgba(15,23,42,.10);
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Inter',sans-serif;
    background:var(--bg);
    color:var(--dark);
    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}


/* =========================================================
   PAGE
========================================================= */

.hs-page{
    width:100%;
}


/* =========================================================
   HERO
========================================================= */

.hs-hero{
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at 8% 20%, rgba(37,99,235,.13), transparent 30%),
        radial-gradient(circle at 95% 80%, rgba(59,130,246,.12), transparent 30%),
        linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    padding:75px 20px 90px;
}

.hs-hero::before{
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    border-radius:50%;
    border:35px solid rgba(37,99,235,.04);
    right:-90px;
    top:-80px;
}

.hs-hero-inner{
    max-width:1180px;
    margin:auto;
    display:grid;
    grid-template-columns:1.05fr .95fr;
    align-items:center;
    gap:60px;
    position:relative;
    z-index:2;
}


/* HERO LEFT */

.hs-hero-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 15px;
    background:#fff;
    border:1px solid #dbeafe;
    border-radius:30px;
    color:var(--primary);
    font-size:12px;
    font-weight:700;
    margin-bottom:20px;
    box-shadow:var(--shadow-sm);
}

.hs-hero-title{
    font-size:clamp(40px,5vw,58px);
    line-height:1.08;
    letter-spacing:-1.8px;
    font-weight:800;
    margin-bottom:18px;
}

.hs-hero-title span{
    color:var(--primary);
}

.hs-hero-description{
    max-width:570px;
    color:var(--muted);
    font-size:16px;
    line-height:1.75;
    margin-bottom:30px;
}


/* SEARCH */

.hs-search-wrapper{
    max-width:650px;
    background:#fff;
    border:1px solid var(--border);
    border-radius:18px;
    padding:8px;
    box-shadow:var(--shadow-md);
    margin-bottom:25px;
}

.hs-search-box{
    display:flex;
    align-items:center;
    gap:5px;
}

.hs-city-box{
    width:190px;
    display:flex;
    align-items:center;
    gap:8px;
    padding:11px 12px;
    color:var(--primary);
}

.hs-city-box i{
    font-size:15px;
}

.hs-city-select{
    width:100%;
    border:none;
    outline:none;
    background:transparent;
    color:var(--dark);
    font-family:inherit;
    font-size:13px;
    cursor:pointer;
}

.hs-search-divider{
    width:1px;
    height:30px;
    background:var(--border);
    flex-shrink:0;
}

.hs-search-input-box{
    flex:1;
    min-width:0;
    display:flex;
    align-items:center;
    gap:10px;
    padding:11px 13px;
    color:var(--primary);
}

.hs-search-input{
    width:100%;
    border:none;
    outline:none;
    background:transparent;
    color:var(--dark);
    font-family:inherit;
    font-size:13px;
}

.hs-search-input::placeholder{
    color:#94a3b8;
}

.hs-search-button{
    border:none;
    background:var(--primary);
    color:#fff;
    padding:12px 21px;
    border-radius:11px;
    font-family:inherit;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:.25s ease;
}

.hs-search-button:hover{
    background:var(--primary-dark);
    transform:translateY(-1px);
}


/* TRUST */

.hs-trust-list{
    display:flex;
    flex-wrap:wrap;
    gap:20px;
}

.hs-trust-item{
    display:flex;
    align-items:center;
    gap:7px;
    color:var(--muted);
    font-size:12px;
    font-weight:600;
}

.hs-trust-item i{
    color:var(--primary);
}


/* HERO RIGHT */

.hs-hero-right{
    position:relative;
    display:flex;
    justify-content:center;
}

.hs-hero-image{
    width:100%;
    max-width:520px;
    height:390px;
    object-fit:cover;
    border-radius:30px;
    display:block;
    box-shadow:var(--shadow-md);
}


/* FLOATING CARD */

.hs-floating-card{
    position:absolute;
    left:-15px;
    bottom:25px;
    background:#fff;
    border-radius:15px;
    padding:13px 16px;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:var(--shadow-md);
}

.hs-floating-icon{
    width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:#ecfdf5;
    color:var(--green);
}

.hs-floating-card strong{
    display:block;
    font-size:13px;
    margin-bottom:2px;
}

.hs-floating-card span{
    color:var(--muted);
    font-size:10px;
}


/* =========================================================
   SERVICES
========================================================= */

.hs-services-section{
    max-width:1180px;
    margin:auto;
    padding:75px 20px 30px;
}

.hs-section-header{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:20px;
    margin-bottom:30px;
}

.hs-section-label{
    color:var(--primary);
    font-size:11px;
    font-weight:800;
    letter-spacing:1.5px;
    text-transform:uppercase;
    margin-bottom:7px;
}

.hs-services-title{
    font-size:32px;
    letter-spacing:-.8px;
}

.hs-section-subtitle{
    color:var(--muted);
    font-size:13px;
    margin-top:6px;
}

.hs-view-all{
    display:inline-flex;
    align-items:center;
    gap:7px;
    color:var(--primary);
    font-size:13px;
    font-weight:700;
    white-space:nowrap;
}

.hs-view-all:hover{
    text-decoration:underline;
}


/* SERVICE GRID */

.hs-services-grid{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:17px;
}


/* CARD */

.hs-service-card{
    position:relative;
    background:#fff;
    border:1px solid var(--border);
    border-radius:18px;
    overflow:hidden;
    transition:.3s ease;
    box-shadow:var(--shadow-sm);
}

.hs-service-card:hover{
    transform:translateY(-7px);
    border-color:#bfdbfe;
    box-shadow:var(--shadow-md);
}

.hs-service-picture{
    position:relative;
    width:100%;
    aspect-ratio:1/1;
    overflow:hidden;
    background:#f1f5f9;
}

.hs-service-picture img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.35s ease;
}

.hs-service-card:hover .hs-service-picture img{
    transform:scale(1.06);
}

.hs-service-details{
    padding:15px;
}

.hs-service-name{
    font-size:15px;
    font-weight:700;
    margin-bottom:6px;
}

.hs-service-bottom{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:5px;
}

.hs-service-price{
    color:var(--muted);
    font-size:11px;
}

.hs-service-arrow{
    width:27px;
    height:27px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:var(--primary-soft);
    color:var(--primary);
    font-size:10px;
}


/* MORE */

.hs-more-card{
    background:linear-gradient(145deg, #eff6ff, #dbeafe);
}

.hs-more-icon{
    aspect-ratio:1/1;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--primary);
    font-size:38px;
}

.hs-more-card .hs-service-details{
    background:#fff;
}


/* =========================================================
   TRUST STRIP
========================================================= */

.hs-trust-section{
    padding:60px 20px;
    background:#fff;
    margin-top:55px;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
}

.hs-trust-container{
    max-width:1050px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.hs-trust-card{
    display:flex;
    align-items:center;
    gap:15px;
    padding:20px;
    border-radius:17px;
    background:var(--bg);
    border:1px solid var(--border);
}

.hs-trust-icon{
    width:48px;
    height:48px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    background:var(--primary-soft);
    color:var(--primary);
    font-size:18px;
}

.hs-trust-card h4{
    font-size:13px;
    margin-bottom:4px;
}

.hs-trust-card p{
    color:var(--muted);
    font-size:11px;
    line-height:1.5;
}


/* =========================================================
   HOW IT WORKS
========================================================= */

.hs-how-section{
    max-width:1180px;
    margin:auto;
    padding:80px 20px;
}

.hs-how-box{
    background:#fff;
    border:1px solid var(--border);
    border-radius:25px;
    padding:50px 35px;
    box-shadow:var(--shadow-sm);
}

.hs-how-header{
    text-align:center;
    margin-bottom:50px;
}

.hs-how-header h2{
    font-size:32px;
    letter-spacing:-.8px;
    margin-bottom:8px;
}

.hs-how-header p{
    color:var(--muted);
    font-size:13px;
}

.hs-how-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:25px;
    position:relative;
}

/* connecting line */
.hs-how-grid::before{
    content:"";
    position:absolute;
    top:31px;
    left:12%;
    right:12%;
    height:2px;
    background:#dbeafe;
    z-index:0;
}

.hs-how-step{
    position:relative;
    z-index:1;
    text-align:center;
}

.hs-how-icon{
    width:62px;
    height:62px;
    margin:0 auto 17px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:18px;
    background:var(--primary-soft);
    color:var(--primary);
    font-size:21px;
    border:5px solid #fff;
    box-shadow:0 0 0 1px #dbeafe;
}

.hs-step-number{
    position:absolute;
    top:-9px;
    right:calc(50% - 34px);
    width:22px;
    height:22px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--primary);
    color:#fff;
    border-radius:50%;
    font-size:9px;
    font-weight:800;
    border:2px solid #fff;
}

.hs-how-step h4{
    font-size:14px;
    margin-bottom:7px;
}

.hs-how-step p{
    color:var(--muted);
    font-size:11px;
    line-height:1.6;
    max-width:180px;
    margin:auto;
}


/* =========================================================
   FINAL CTA
========================================================= */

.hs-cta-section{
    padding:0 20px 80px;
}

.hs-cta{
    max-width:1100px;
    margin:auto;
    padding:48px;
    border-radius:26px;
    color:#fff;
    background:
        radial-gradient(circle at 90% 10%, rgba(255,255,255,.16), transparent 30%),
        linear-gradient(135deg, #2563eb, #1d4ed8);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:30px;
}

.hs-cta h2{
    font-size:28px;
    margin-bottom:7px;
}

.hs-cta p{
    color:#dbeafe;
    font-size:12px;
}

.hs-cta-button{
    flex-shrink:0;
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#fff;
    color:var(--primary);
    padding:13px 21px;
    border-radius:11px;
    font-size:12px;
    font-weight:800;
    transition:.3s ease;
}

.hs-cta-button:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1050px){

    .hs-services-grid{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }

    .hs-hero-inner{
        gap:35px;
    }
}


@media(max-width:850px){

    .hs-hero-inner{
        grid-template-columns:1fr;
        text-align:center;
    }

    .hs-hero-description{
        margin-left:auto;
        margin-right:auto;
    }

    .hs-search-wrapper{
        max-width:650px;
        margin-left:auto;
        margin-right:auto;
        text-align:left;
    }

    .hs-trust-list{
        justify-content:center;
    }

    .hs-hero-right{
        max-width:600px;
        width:100%;
        margin:auto;
    }

    .hs-floating-card{
        left:10px;
    }

    .hs-trust-container{
        grid-template-columns:1fr;
    }

    .hs-how-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .hs-how-grid::before{
        display:none;
    }
}


@media(max-width:700px){

    .hs-hero{
        padding:50px 15px 65px;
    }

    .hs-hero-title{
        font-size:36px;
        letter-spacing:-1px;
    }

    .hs-hero-description{
        font-size:14px;
    }

    /* SEARCH */

    .hs-search-wrapper{
        padding:0;
        border:none;
        background:transparent;
        box-shadow:none;
    }

    .hs-search-box{
        flex-direction:column;
        gap:9px;
    }

    .hs-city-box{
        width:100%;
        background:#fff;
        border:1px solid var(--border);
        border-radius:12px;
        padding:14px;
    }

    .hs-search-divider{
        display:none;
    }

    .hs-search-input-box{
        width:100%;
        background:#fff;
        border:1px solid var(--border);
        border-radius:12px;
        padding:14px;
    }

    .hs-search-button{
        width:100%;
        padding:14px;
    }

    /* SERVICES */

    .hs-services-section{
        padding:55px 15px 20px;
    }

    .hs-section-header{
        align-items:flex-start;
        flex-direction:column;
        gap:10px;
        margin-bottom:20px;
    }

    .hs-services-title{
        font-size:26px;
    }

    .hs-services-grid{
        display:flex;
        overflow-x:auto;
        gap:12px;
        padding:5px 2px 15px;
        scroll-snap-type:x mandatory;
        scrollbar-width:none;
    }

    .hs-services-grid::-webkit-scrollbar{
        display:none;
    }

    .hs-service-card{
        flex:0 0 155px;
        scroll-snap-align:start;
    }

    .hs-service-picture{
        aspect-ratio:1/1;
    }

    /* TRUST */

    .hs-trust-section{
        padding:50px 15px;
    }

    /* HOW */

    .hs-how-section{
        padding:60px 15px;
    }

    .hs-how-box{
        padding:35px 20px;
    }

    .hs-how-header{
        margin-bottom:35px;
    }

    .hs-how-header h2{
        font-size:27px;
    }

    .hs-how-grid{
        grid-template-columns:1fr;
        gap:25px;
    }

    .hs-how-step{
        display:flex;
        align-items:center;
        text-align:left;
        gap:15px;
    }

    .hs-how-icon{
        margin:0;
        flex-shrink:0;
    }

    .hs-step-number{
        left:45px;
        right:auto;
    }

    .hs-how-step p{
        margin:0;
        max-width:none;
    }

    /* CTA */

    .hs-cta-section{
        padding:0 15px 65px;
    }

    .hs-cta{
        padding:35px 25px;
        flex-direction:column;
        align-items:flex-start;
        border-radius:21px;
    }

    .hs-cta h2{
        font-size:24px;
    }

    .hs-cta-button{
        width:100%;
        justify-content:center;
    }
}


@media(max-width:400px){

    .hs-hero-title{
        font-size:32px;
    }

    .hs-service-card{
        flex-basis:145px;
    }

    .hs-service-details{
        padding:12px;
    }

    .hs-service-name{
        font-size:13px;
    }

    .hs-service-price{
        font-size:10px;
    }
}

</style>

<script src="history-lock.js?v=<?= (string) filemtime(__DIR__ . '/history-lock.js') ?>"></script>
</head>


<body>

<?php
/* Navbar: अब <body> के अंदर है (पहले DOCTYPE से पहले था) */
include __DIR__ . '/nav.php';
?>

<div class="hs-page">


<!-- =====================================================
     HERO
===================================================== -->

<section class="hs-hero">

    <div class="hs-hero-inner">


        <!-- LEFT -->

        <div class="hs-hero-left">

            <div class="hs-hero-badge">
                <i class="fa-solid fa-circle-check"></i>
                Trusted Home Services
            </div>


            <h1 class="hs-hero-title">
                Your Home.
                <br>
                <span>Our Expertise.</span>
            </h1>


            <p class="hs-hero-description">
                Find trusted professionals for cleaning,
                repairs, maintenance and everyday home
                services. Book the help you need in just
                a few clicks.
            </p>


            <!-- SEARCH -->

            <div class="hs-search-wrapper">

                <div class="hs-search-box">


                    <!-- CITY -->

                    <div class="hs-city-box">

                        <i class="fa-solid fa-location-dot"></i>

                        <?php $hs_selected_city = $_GET['city'] ?? ''; ?>

                        <form method="GET" id="hsCityForm" style="width:100%;">

                            <select
                                name="city"
                                class="hs-city-select"
                                onchange="document.getElementById('hsCityForm').submit();"
                            >

                                <option value="">Select City</option>

                                <?php
                                $hs_cities = ['Dumka', 'Udhwa', 'Barharwa', 'Sahibganj'];
                                foreach ($hs_cities as $hs_city):
                                ?>
                                    <option
                                        value="<?= htmlspecialchars($hs_city, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $hs_selected_city === $hs_city ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($hs_city, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                        </form>

                    </div>


                    <div class="hs-search-divider"></div>


                    <!-- SERVICE -->

                    <div class="hs-search-input-box">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="hsServiceSearch"
                            class="hs-search-input"
                            placeholder="Search for a service..."
                        >

                    </div>


                    <button
                        type="button"
                        class="hs-search-button"
                        onclick="hsSearchServices()"
                    >
                        Search
                    </button>


                </div>

            </div>


            <!-- TRUST -->

            <div class="hs-trust-list">

                <div class="hs-trust-item">
                    <i class="fa-solid fa-circle-check"></i>
                    Verified Professionals
                </div>

                <div class="hs-trust-item">
                    <i class="fa-solid fa-star"></i>
                    Top Rated
                </div>

                <div class="hs-trust-item">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                    Transparent Pricing
                </div>

            </div>

        </div>


        <!-- RIGHT -->

        <div class="hs-hero-right">

            <img
                src="image/ac repair.png"
                class="hs-hero-image"
                alt="Home Service Professional"
            >

            <div class="hs-floating-card">

                <div class="hs-floating-icon">
                    <i class="fa-solid fa-user-check"></i>
                </div>

                <div>
                    <strong>Trusted Professionals</strong>
                    <span>Ready to serve you</span>
                </div>

            </div>

        </div>


    </div>

</section>



<!-- =====================================================
     SERVICES
===================================================== -->

<section class="hs-services-section" id="hsPopularServices">


    <div class="hs-section-header">

        <div>

            <div class="hs-section-label">Explore</div>

            <h2 class="hs-services-title">Popular Services</h2>

            <p class="hs-section-subtitle">
                Get reliable help for your everyday home needs.
            </p>

        </div>

        <a href="more.php" class="hs-view-all">
            View All Services
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>


    <div class="hs-services-grid">


        <!-- ELECTRICIAN -->

        <a href="Electrician.php" class="hs-service-card" data-hs-service="electrician">

            <div class="hs-service-picture">
                <img src="image/elec.png" alt="Electrician">
            </div>

            <div class="hs-service-details">

                <h3 class="hs-service-name">Electrician</h3>

                <div class="hs-service-bottom">
                    <p class="hs-service-price">Starts at ₹299</p>
                    <span class="hs-service-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>

            </div>

        </a>


        <!-- PLUMBER -->

        <a href="plumber.php" class="hs-service-card" data-hs-service="plumber">

            <div class="hs-service-picture">
                <img src="image/plumber.png" alt="Plumber">
            </div>

            <div class="hs-service-details">

                <h3 class="hs-service-name">Plumber</h3>

                <div class="hs-service-bottom">
                    <p class="hs-service-price">Starts at ₹299</p>
                    <span class="hs-service-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>

            </div>

        </a>


        <!-- AC -->

        <a href="ac-repair.php" class="hs-service-card" data-hs-service="ac repair">

            <div class="hs-service-picture">
                <img src="image/ac repair.png" alt="AC Repair">
            </div>

            <div class="hs-service-details">

                <h3 class="hs-service-name">AC Repair</h3>

                <div class="hs-service-bottom">
                    <p class="hs-service-price">Starts at ₹399</p>
                    <span class="hs-service-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>

            </div>

        </a>


        <!-- HOME CLEANING -->

        <a href="home-cleaning.php" class="hs-service-card" data-hs-service="home cleaning">

            <div class="hs-service-picture">
                <img src="image/home cleaning.png" alt="Home Cleaning">
            </div>

            <div class="hs-service-details">

                <h3 class="hs-service-name">Home Cleaning</h3>

                <div class="hs-service-bottom">
                    <p class="hs-service-price">Starts at ₹249</p>
                    <span class="hs-service-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>

            </div>

        </a>


        <!-- MORE -->

        <a href="more.php" class="hs-service-card hs-more-card" data-hs-service="explore all more services">

            <div class="hs-more-icon">
                <i class="fa-solid fa-table-cells-large"></i>
            </div>

            <div class="hs-service-details">

                <h3 class="hs-service-name">Explore All</h3>

                <div class="hs-service-bottom">
                    <p class="hs-service-price">View all services</p>
                    <span class="hs-service-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>

            </div>

        </a>


    </div>

</section>



<!-- =====================================================
     TRUST
===================================================== -->

<section class="hs-trust-section">

    <div class="hs-trust-container">


        <div class="hs-trust-card">

            <div class="hs-trust-icon">
                <i class="fa-solid fa-user-check"></i>
            </div>

            <div>
                <h4>Verified Professionals</h4>
                <p>
                    Find professionals for your
                    home-service needs.
                </p>
            </div>

        </div>


        <div class="hs-trust-card">

            <div class="hs-trust-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div>
                <h4>Flexible Booking</h4>
                <p>
                    Choose a convenient date and
                    time for your service.
                </p>
            </div>

        </div>


        <div class="hs-trust-card">

            <div class="hs-trust-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div>
                <h4>Simple &amp; Secure</h4>
                <p>
                    Easy booking with transparent
                    service information.
                </p>
            </div>

        </div>


    </div>

</section>



<!-- =====================================================
     HOW IT WORKS
===================================================== -->

<section class="hs-how-section">

    <div class="hs-how-box">


        <div class="hs-how-header">

            <div class="hs-section-label">Simple Process</div>

            <h2>How It Works</h2>

            <p>Book your home service in four simple steps.</p>

        </div>


        <div class="hs-how-grid">


            <!-- STEP 1 -->

            <div class="hs-how-step">

                <div class="hs-how-icon">
                    <i class="fa-solid fa-hand-pointer"></i>
                    <span class="hs-step-number">1</span>
                </div>

                <div>
                    <h4>Select Service</h4>
                    <p>Choose the service you need.</p>
                </div>

            </div>


            <!-- STEP 2 -->

            <div class="hs-how-step">

                <div class="hs-how-icon">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="hs-step-number">2</span>
                </div>

                <div>
                    <h4>Pick a Time</h4>
                    <p>Select your preferred date and time.</p>
                </div>

            </div>


            <!-- STEP 3 -->

            <div class="hs-how-step">

                <div class="hs-how-icon">
                    <i class="fa-solid fa-lock"></i>
                    <span class="hs-step-number">3</span>
                </div>

                <div>
                    <h4>Book Securely</h4>
                    <p>Confirm your booking details.</p>
                </div>

            </div>


            <!-- STEP 4 -->

            <div class="hs-how-step">

                <div class="hs-how-icon">
                    <i class="fa-solid fa-house-circle-check"></i>
                    <span class="hs-step-number">4</span>
                </div>

                <div>
                    <h4>Get It Done</h4>
                    <p>Your professional completes the job.</p>
                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     CTA
===================================================== -->

<section class="hs-cta-section">

    <div class="hs-cta">

        <div>

            <h2>Need help around your home?</h2>

            <p>
                Explore our services and find the right
                professional for your needs.
            </p>

        </div>

        <a href="more.php" class="hs-cta-button">
            Explore Services
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>


</div>



<!-- =====================================================
     SEARCH JAVASCRIPT
===================================================== -->

<script>

function hsSearchServices(){

    const searchBox = document.getElementById("hsServiceSearch");

    const searchText = searchBox.value.toLowerCase().trim();

    const serviceCards = document.querySelectorAll(".hs-service-card");

    serviceCards.forEach(function(card){

        const serviceName = card.getAttribute("data-hs-service");

        if(
            searchText === "" ||
            serviceName.toLowerCase().includes(searchText)
        ){
            card.style.display = "";
        }
        else{
            card.style.display = "none";
        }

    });

}


/* ENTER KEY SEARCH */

document
    .getElementById("hsServiceSearch")
    .addEventListener("keydown", function(event){

        if(event.key === "Enter"){
            event.preventDefault();
            hsSearchServices();
        }

    });

</script>




<?php include __DIR__ . '/bottom.php'; ?>


</body>

</html>
