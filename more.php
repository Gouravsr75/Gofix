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

/* ------------------------------------------------------------------
   SERVICES DATA
   To add or edit a service, change this list only. The cards, the
   search, the category filters and the service count all follow it.
   'link' => '#' means the booking page is not built yet.
------------------------------------------------------------------- */
$categories = [
    'repairs'  => 'Repairs',
    'upkeep'   => 'Home upkeep',
    'cleaning' => 'Cleaning',
    'personal' => 'Personal & car',
];

// ALL PAGE LINK ARE CONNECTING IN THIS LINE  
// WE CAN UPDATE IMAGE , EMOJI , PRODUCT NAME , DESC , AND OTHER THINGS IN THIS LINE
$services = [
    ['name' => 'Electrician',       'desc' => 'Electrical repair & installation', 'img' => 'elec.png',             'emoji' => '⚡',  'rating' => '4.8', 'tag' => 'Quick Service', 'cat' => 'repairs',  'popular' => true,  'link' => 'Electrician.php'],
    ['name' => 'Plumber',           'desc' => 'Pipe, tap & water repairs',        'img' => 'plumber.png',          'emoji' => '🔧', 'rating' => '4.7', 'tag' => 'Quick Service', 'cat' => 'repairs',  'popular' => true,  'link' => 'plumber.php'],
    ['name' => 'AC Repair',         'desc' => 'AC service & maintenance',         'img' => 'ac repair.png',        'emoji' => '❄️', 'rating' => '4.8', 'tag' => 'Professional',  'cat' => 'repairs',  'popular' => true,  'link' => 'ac-repair.php'],
    ['name' => 'Home Cleaning',     'desc' => 'Professional home cleaning',       'img' => 'home cleaning.png',    'emoji' => '🧹', 'rating' => '4.9', 'tag' => 'Professional',  'cat' => 'cleaning', 'popular' => true,  'link' => 'home-cleaning.php'],
    ['name' => 'Salon at Home',     'desc' => 'Beauty & grooming services',       'img' => 'Salon at Home.png',    'emoji' => '💇', 'rating' => '4.8', 'tag' => 'At Home',       'cat' => 'personal', 'popular' => false, 'link' => 'Salon_at_Home.php'],
    ['name' => 'Painting',          'desc' => 'Interior & exterior painting',     'img' => 'painter.png',          'emoji' => '🎨', 'rating' => '4.7', 'tag' => 'Professional',  'cat' => 'upkeep',   'popular' => false, 'link' => 'painter.php'],
    ['name' => 'Carpenter',         'desc' => 'Furniture repair & installation',  'img' => 'Carpenter.png',        'emoji' => '🔨', 'rating' => '4.6', 'tag' => 'Skilled Pro',   'cat' => 'upkeep',   'popular' => false, 'link' => 'carpenter.php'],
    ['name' => 'Appliance Repair',  'desc' => 'Repair household appliances',      'img' => 'appliance repair.png', 'emoji' => '📺', 'rating' => '4.7', 'tag' => 'Doorstep',      'cat' => 'repairs',  'popular' => false, 'link' => 'appliance.php'],
    ['name' => 'Pest Control',      'desc' => 'Safe pest treatment',              'img' => 'Pest Control.png',     'emoji' => '🐜', 'rating' => '4.8', 'tag' => 'Safe Service',  'cat' => 'upkeep',   'popular' => false, 'link' => 'pest_control.php'],
    ['name' => 'Car Wash',          'desc' => 'Car cleaning at your doorstep',    'img' => 'carwash.png',          'emoji' => '🚗', 'rating' => '4.7', 'tag' => 'Doorstep',      'cat' => 'personal', 'popular' => false, 'link' => 'car_wash.php'],
    ['name' => 'Sofa Cleaning',     'desc' => 'Deep sofa & upholstery cleaning',  'img' => 'Sofa Cleaning.png',    'emoji' => '🛋️', 'rating' => '4.8', 'tag' => 'Deep Clean',    'cat' => 'cleaning', 'popular' => false, 'link' => 'sofa_cleaning.php'],
    ['name' => 'Laundry',           'desc' => 'Pickup & doorstep laundry',        'img' => 'Laundry.png',          'emoji' => '🧺', 'rating' => '4.7', 'tag' => 'Doorstep',      'cat' => 'cleaning', 'popular' => false, 'link' => 'laundry.php'],
];
// ALL PAGE LINK ARE CONNECTING IN THIS LINE END

if (!function_exists('gf_esc')) {
    function gf_esc($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$total = count($services);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>All Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Base reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f6fc;
        }

        /* =========================
           PAGE TOKENS
        ========================= */

        .gf-page {
            --gf-ink: #0f1b3d;
            --gf-blue: #2456e6;
            --gf-blue-soft: #e8eeff;
            --gf-amber: #ffc83d;
            --gf-mist: #f3f6fc;
            --gf-line: #e1e7f3;
            --gf-text: #1a2340;
            --gf-muted: #5d6885;

            padding: 28px 0 90px;
            color: var(--gf-text);
            background: var(--gf-mist);
        }

        .gf-page,
        .gf-page * {
            font-family: "Bricolage Grotesque", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .gf-container {
            width: 92%;
            max-width: 1240px;
            margin: 0 auto;
        }

        .gf-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        /* =========================
           HERO
        ========================= */

        .gf-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            background: var(--gf-ink);
            color: #ffffff;
            border-radius: 28px;
            overflow: hidden;
        }

        .gf-hero-copy {
            padding: 58px 0 54px 56px;
        }

        .gf-hero h1 {
            font-size: clamp(2.1rem, 4.2vw, 3.5rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.025em;
        }

        .gf-hero-copy > p {
            margin-top: 16px;
            max-width: 46ch;
            font-size: 17px;
            line-height: 1.55;
            color: #c3cdea;
        }

        /* SEARCH */

        .gf-search {
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 520px;
            margin-top: 30px;
            padding: 5px 8px 5px 18px;
            background: #ffffff;
            border-radius: 16px;
        }

        .gf-search:focus-within {
            box-shadow: 0 0 0 3px var(--gf-amber);
        }

        .gf-search svg {
            flex-shrink: 0;
            color: var(--gf-blue);
        }

        .gf-search input {
            flex: 1;
            min-width: 0;
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            color: var(--gf-text);
            padding: 13px 0;
        }

        .gf-search input::placeholder {
            color: #8792ad;
        }

        .gf-clear {
            display: none;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 50%;
            background: var(--gf-mist);
            color: var(--gf-muted);
            cursor: pointer;
        }

        .gf-clear.is-visible {
            display: inline-flex;
        }

        .gf-clear:hover {
            background: var(--gf-blue-soft);
            color: var(--gf-blue);
        }

        .gf-clear:focus-visible,
        .gf-quick button:focus-visible,
        .gf-chip:focus-visible,
        .gf-empty button:focus-visible,
        .gf-cta a:focus-visible {
            outline: 3px solid var(--gf-amber);
            outline-offset: 2px;
        }

        /* QUICK SEARCHES */

        .gf-quick {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            font-size: 14px;
            color: #a9b5d8;
        }

        .gf-quick button {
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: transparent;
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background .2s;
        }

        .gf-quick button:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        /* DOORWAY ARCHES */

        .gf-hero-art {
            display: grid;
            grid-template-columns: 1fr 1.15fr 1fr;
            align-items: end;
            gap: 12px;
            padding: 40px 40px 0 0;
        }

        .gf-arch {
            overflow: hidden;
            background: #1d2d5c;
            border-radius: 999px 999px 0 0;
            animation: gf-rise .8s cubic-bezier(.2, .7, .2, 1) both;
        }

        .gf-arch:nth-child(1) { height: 235px; }
        .gf-arch:nth-child(2) { height: 330px; animation-delay: .08s; }
        .gf-arch:nth-child(3) { height: 195px; animation-delay: .16s; }

        .gf-arch img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @keyframes gf-rise {
            from { transform: translateY(70px); opacity: 0; }
            to   { transform: none; opacity: 1; }
        }

        /* =========================
           FILTER BAR
        ========================= */

        .gf-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 34px 0 22px;
        }

        .gf-chips {
            display: flex;
            gap: 8px;
            padding: 3px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .gf-chips::-webkit-scrollbar {
            display: none;
        }

        .gf-chip {
            flex: none;
            border: 1px solid var(--gf-line);
            background: #ffffff;
            color: var(--gf-text);
            padding: 9px 17px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s, border-color .2s;
        }

        .gf-chip:hover {
            border-color: var(--gf-blue);
        }

        .gf-chip[aria-pressed="true"] {
            background: var(--gf-ink);
            border-color: var(--gf-ink);
            color: #ffffff;
        }

        .gf-count {
            flex: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--gf-muted);
        }

        /* =========================
           SERVICE GRID
        ========================= */

        .gf-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .gf-card {
            display: flex;
            flex-direction: column;
            padding: 10px 10px 14px;
            background: #ffffff;
            border: 1px solid var(--gf-line);
            border-radius: 22px;
            color: inherit;
            text-decoration: none;
            transition: border-color .2s;
        }

        .gf-card[hidden] {
            display: none;
        }

        .gf-card:hover {
            border-color: var(--gf-blue);
        }

        .gf-card:focus-visible {
            outline: 3px solid var(--gf-blue);
            outline-offset: 3px;
        }

        .gf-thumb {
            position: relative;
            margin-bottom: 26px;
        }

        .gf-thumb img {
            display: block;
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: var(--gf-blue-soft);
            border-radius: 50% 50% 14px 14px / 60% 60% 14px 14px;
        }

        .gf-popular {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 11px;
            background: var(--gf-amber);
            color: var(--gf-ink);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .gf-emoji {
            position: absolute;
            left: 14px;
            bottom: -18px;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            background: #ffffff;
            border: 1px solid var(--gf-line);
            border-radius: 50%;
            font-size: 18px;
            line-height: 1;
        }

        .gf-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 0 8px;
        }

        .gf-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.25;
        }

        .gf-desc {
            margin-top: 4px;
            font-size: 14px;
            line-height: 1.45;
            color: var(--gf-muted);
        }

        .gf-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0 16px;
            font-size: 13px;
            color: var(--gf-muted);
        }

        .gf-rating {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            color: var(--gf-text);
        }

        .gf-tag {
            padding: 3px 10px;
            background: var(--gf-mist);
            border-radius: 999px;
            font-weight: 600;
        }

        .gf-book {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px;
            background: var(--gf-blue-soft);
            color: var(--gf-blue);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            transition: background .2s, color .2s;
        }

        .gf-book svg {
            transition: transform .2s;
        }

        .gf-card:hover .gf-book,
        .gf-card:focus-visible .gf-book {
            background: var(--gf-blue);
            color: #ffffff;
        }

        .gf-card:hover .gf-book svg {
            transform: translateX(3px);
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .gf-empty {
            padding: 56px 20px;
            text-align: center;
            background: #ffffff;
            border: 1px dashed #c4d0ea;
            border-radius: 22px;
        }

        .gf-empty[hidden] {
            display: none;
        }

        .gf-empty-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            background: var(--gf-blue-soft);
            color: var(--gf-blue);
            border-radius: 50%;
        }

        .gf-empty h3 {
            font-size: 20px;
            font-weight: 700;
        }

        .gf-empty p {
            max-width: 40ch;
            margin: 6px auto 20px;
            font-size: 15px;
            line-height: 1.5;
            color: var(--gf-muted);
        }

        .gf-empty button {
            border: none;
            background: var(--gf-ink);
            color: #ffffff;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        /* =========================
           BOTTOM CTA
        ========================= */

        .gf-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-top: 48px;
            padding: 36px 42px;
            background: var(--gf-blue);
            color: #ffffff;
            border-radius: 26px;
        }

        .gf-cta h2 {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .gf-cta p {
            max-width: 52ch;
            margin-top: 6px;
            font-size: 15px;
            line-height: 1.5;
            color: #e8eeff;
        }

        .gf-cta a {
            flex-shrink: 0;
            padding: 14px 28px;
            background: var(--gf-amber);
            color: var(--gf-ink);
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: background .2s;
        }

        .gf-cta a:hover {
            background: #ffd766;
        }

        /* =========================
           RESPONSIVE BREAKPOINTS
        ========================= */

        @media (max-width: 1050px) {
            .gf-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .gf-hero-copy {
                padding-left: 40px;
            }
            .gf-hero-art {
                padding-right: 28px;
            }
        }

        @media (max-width: 860px) {
            .gf-hero {
                grid-template-columns: 1fr;
                border-radius: 24px;
            }
            .gf-hero-copy {
                padding: 38px 26px 6px;
            }
            .gf-hero-art {
                padding: 28px 26px 0;
                max-width: 480px;
                width: 100%;
                margin: 0 auto;
            }
            .gf-arch:nth-child(1) { height: 125px; }
            .gf-arch:nth-child(2) { height: 175px; }
            .gf-arch:nth-child(3) { height: 105px; }
        }

        @media (max-width: 700px) {
            .gf-page {
                padding: 16px 0 70px;
            }
            .gf-container {
                width: 94%;
            }
            .gf-hero-copy > p {
                font-size: 15px;
            }
            .gf-search {
                margin-top: 22px;
            }
            .gf-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                margin: 24px 0 16px;
            }
            .gf-count {
                order: -1;
                font-size: 13px;
            }
            .gf-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }
            .gf-card {
                padding: 7px 7px 10px;
                border-radius: 18px;
            }
            .gf-thumb {
                margin-bottom: 22px;
            }
            .gf-thumb img {
                height: 125px;
            }
            .gf-popular {
                top: 8px;
                padding: 3px 9px;
                font-size: 11px;
            }
            .gf-emoji {
                left: 10px;
                bottom: -15px;
                width: 32px;
                height: 32px;
                font-size: 15px;
            }
            .gf-body {
                padding: 0 5px;
            }
            .gf-title {
                font-size: 15px;
            }
            .gf-desc {
                font-size: 12px;
            }
            .gf-meta {
                flex-wrap: wrap;
                gap: 6px;
                margin: 9px 0 12px;
                font-size: 11px;
            }
            .gf-tag {
                padding: 2px 8px;
            }
            .gf-book {
                padding: 9px 6px;
                border-radius: 10px;
                font-size: 13px;
            }
            .gf-cta {
                flex-direction: column;
                align-items: stretch;
                margin-top: 34px;
                padding: 28px 22px;
                border-radius: 22px;
            }
            .gf-cta h2 {
                font-size: 22px;
            }
            .gf-cta a {
                text-align: center;
            }
        }

        @media (max-width: 380px) {
            .gf-grid {
                gap: 8px;
            }
            .gf-thumb img {
                height: 105px;
            }
            .gf-title {
                font-size: 14px;
            }
            .gf-desc {
                font-size: 11px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .gf-arch {
                animation: none;
            }
            .gf-page * {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="gf-page">
    <div class="gf-container">

        <!-- =========================
             HERO
        ========================= -->
        <section class="gf-hero">
            <div class="gf-hero-copy">
                <h1>Skilled help for every corner of your home</h1>
                <p>
                    Electricians, plumbers, cleaners and more.
                    Pick a service and book a trusted professional
                    at your convenience.
                </p>

                <div class="gf-search" role="search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="M20 20l-3.5-3.5"></path>
                    </svg>
                    <label class="gf-sr-only" for="gfSearch">Search services</label>
                    <input
                        type="text"
                        id="gfSearch"
                        placeholder="What service do you need today?"
                        autocomplete="off"
                    >
                    <button type="button" class="gf-clear" id="gfClear" aria-label="Clear search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18"></path>
                        </svg>
                    </button>
                </div>

                <div class="gf-quick">
                    <span>Popular:</span>
                    <button type="button" data-q="Electrician">Electrician</button>
                    <button type="button" data-q="AC Repair">AC Repair</button>
                    <button type="button" data-q="Home Cleaning">Home Cleaning</button>
                </div>
            </div>

            <div class="gf-hero-art" aria-hidden="true">
                <div class="gf-arch"><img src="image/elec.png" alt=""></div>
                <div class="gf-arch"><img src="image/home%20cleaning.png" alt=""></div>
                <div class="gf-arch"><img src="image/plumber.png" alt=""></div>
            </div>
        </section>

        <!-- =========================
             FILTERS
        ========================= -->
        <div class="gf-toolbar" id="gfServices">
            <div class="gf-chips" role="group" aria-label="Filter by category">
                <button type="button" class="gf-chip" data-cat="all" aria-pressed="true">All</button>
                <?php foreach ($categories as $key => $label): ?>
                    <button type="button" class="gf-chip" data-cat="<?= gf_esc($key) ?>" aria-pressed="false">
                        <?= gf_esc($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="gf-count" id="gfCount" aria-live="polite">
                <?= $total ?> services
            </div>
        </div>

        <h2 class="gf-sr-only">Services</h2>

        <!-- =========================
             SERVICES
        ========================= -->
        <div class="gf-grid" id="gfGrid">
            <?php foreach ($services as $s):
                $search = strtolower($s['name'] . ' ' . $s['desc'] . ' ' . $categories[$s['cat']]);
                $imgSrc = 'image/' . rawurlencode($s['img']);
            ?>
                <a
                    class="gf-card"
                    href="<?= gf_esc($s['link']) ?>"
                    data-cat="<?= gf_esc($s['cat']) ?>"
                    data-search="<?= gf_esc($search) ?>"
                    aria-label="Book <?= gf_esc($s['name']) ?>"
                >
                    <div class="gf-thumb">
                        <img
                            src="<?= gf_esc($imgSrc) ?>"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        >
                        <?php if ($s['popular']): ?>
                            <span class="gf-popular">Popular</span>
                        <?php endif; ?>
                        <span class="gf-emoji" aria-hidden="true"><?= $s['emoji'] ?></span>
                    </div>

                    <div class="gf-body">
                        <h3 class="gf-title"><?= gf_esc($s['name']) ?></h3>
                        <p class="gf-desc"><?= gf_esc($s['desc']) ?></p>
                        <div class="gf-meta">
                            <span class="gf-rating">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#f5a623" aria-hidden="true">
                                    <path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8 5.8 21.1 7 14.2 2 9.3l6.9-1z"></path>
                                </svg>
                                <?= gf_esc($s['rating']) ?>
                            </span>
                            <span class="gf-tag"><?= gf_esc($s['tag']) ?></span>
                        </div>
                        <span class="gf-book">
                            Book now
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6"></path>
                            </svg>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- =========================
             EMPTY STATE
        ========================= -->
        <div class="gf-empty" id="gfEmpty" hidden>
            <div class="gf-empty-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
            </div>
            <h3>No matching service</h3>
            <p id="gfEmptyText"></p>
            <button type="button" id="gfReset">Show all services</button>
        </div>

        <!-- =========================
             BOTTOM CTA
        ========================= -->
        <section class="gf-cta">
            <div>
                <h2>Can't find the service you need?</h2>
                <p>
                    Tell us about the job and we'll help you
                    find the right professional.
                </p>
            </div>
            <a href="contact.php">Contact us</a>
        </section>

    </div>
</div>

<script>
(function () {
    var input     = document.getElementById("gfSearch");
    var clearBtn  = document.getElementById("gfClear");
    var cards     = Array.prototype.slice.call(document.querySelectorAll(".gf-card"));
    var chips     = Array.prototype.slice.call(document.querySelectorAll(".gf-chip"));
    var quick     = Array.prototype.slice.call(document.querySelectorAll(".gf-quick button"));
    var countEl   = document.getElementById("gfCount");
    var emptyEl   = document.getElementById("gfEmpty");
    var emptyText = document.getElementById("gfEmptyText");
    var resetBtn  = document.getElementById("gfReset");
    var anchor    = document.getElementById("gfServices");

    var activeCat = "all";

    function scrollToServices() {
        var reduce = window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        anchor.scrollIntoView({
            behavior: reduce ? "auto" : "smooth",
            block: "start"
        });
    }

    function apply() {
        var raw   = input.value.trim();
        var query = raw.toLowerCase();
        var shown = 0;

        cards.forEach(function (card) {
            var okCat   = activeCat === "all" || card.getAttribute("data-cat") === activeCat;
            var okQuery = !query || card.getAttribute("data-search").indexOf(query) !== -1;
            var visible = okCat && okQuery;

            card.hidden = !visible;

            if (visible) {
                shown++;
            }
        });

        countEl.textContent = shown + (shown === 1 ? " service" : " services");
        clearBtn.classList.toggle("is-visible", raw.length > 0);

        if (shown === 0) {
            emptyText.textContent = raw
                ? "Nothing matches \u201C" + raw + "\u201D. Try another word or choose a different category."
                : "There are no services in this category yet.";
            emptyEl.hidden = false;
        } else {
            emptyEl.hidden = true;
        }
    }

    function setCategory(cat) {
        activeCat = cat;

        chips.forEach(function (chip) {
            chip.setAttribute(
                "aria-pressed",
                chip.getAttribute("data-cat") === cat ? "true" : "false"
            );
        });

        apply();
    }

    input.addEventListener("input", apply);

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            scrollToServices();
        }
        if (e.key === "Escape") {
            input.value = "";
            apply();
        }
    });

    clearBtn.addEventListener("click", function () {
        input.value = "";
        apply();
        input.focus();
    });

    chips.forEach(function (chip) {
        chip.addEventListener("click", function () {
            setCategory(chip.getAttribute("data-cat"));
        });
    });

    quick.forEach(function (btn) {
        btn.addEventListener("click", function () {
            input.value = btn.getAttribute("data-q");
            setCategory("all");
            scrollToServices();
        });
    });

    resetBtn.addEventListener("click", function () {
        input.value = "";
        setCategory("all");
    });

    apply();
})();
</script>

</body>
</html>

<?php
include 'bottom.php';
?>