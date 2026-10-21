<?php

require_once __DIR__ . '/auth.php';

/* USER INFORMATION */
$userName    = $_SESSION["full_name"] ?? "Customer";
$currentPage = basename($_SERVER["PHP_SELF"]);


$gofixNavItems = [
    ["dashboard.php",    "Home",         "fa-house"],
    ["more.php",         "Services",     "fa-table-cells-large"],
    ["orders.php",       "My Orders",    "fa-clipboard-list"],
    ["howitis work.php", "How It Works", "fa-circle-info"],
    ["aboutas.php",      "About Us",     "fa-users"],
    ["contact.php",      "Contact",      "fa-phone"],
];
?>

<!-- =======================================================
     GOFIX CUSTOMER NAVBAR
======================================================= -->

<style>

.gofix-header,
.gofix-header * {
    box-sizing: border-box;
}

.gofix-header {
    width: 100%;
    position: sticky;
    top: 0;
    left: 0;
    z-index: 9999;
    background: rgba(255,255,255,.97);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid #e2e8f0;
    font-family: "Inter", Arial, sans-serif;
}

.gofix-nav-container {
    width: 100%;
    max-width: 1400px;
    height: 70px;
    margin: 0 auto;
    padding: 0 30px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
}


/* =========================================================
   LOGO
========================================================= */

.gofix-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    justify-self: start;
}

.gofix-logo-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #ffffff;
    border-radius: 11px;
    flex-shrink: 0;
    box-shadow: 0 5px 15px rgba(37,99,235,.20);
}

.gofix-logo-icon i {
    font-size: 18px;
}

.gofix-logo-text h2 {
    margin: 0;
    font-size: 20px;
    line-height: 1.1;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.4px;
}

.gofix-logo-text p {
    margin: 3px 0 0;
    font-size: 10px;
    color: #64748b;
}


/* =========================================================
   DESKTOP NAVIGATION
========================================================= */

.gofix-desktop-nav {
    justify-self: center;
}

.gofix-desktop-nav ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.gofix-desktop-nav a {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 13px;
    border-radius: 9px;
    color: #64748b;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s ease;
}

.gofix-desktop-nav a:hover {
    color: #2563eb;
    background: #eff6ff;
}

.gofix-desktop-nav a.active {
    color: #2563eb;
    background: #eff6ff;
    font-weight: 700;
}

.gofix-desktop-nav a i {
    font-size: 12px;
}


/* =========================================================
   RIGHT SIDE USER AREA
========================================================= */

.gofix-user-area {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 10px;
}

.gofix-user-profile {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 11px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    max-width: 190px;
}

.gofix-user-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 50%;
    flex-shrink: 0;
}

.gofix-user-icon i {
    font-size: 16px;
}

.gofix-user-name {
    max-width: 115px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}


/* =========================================================
   LOGOUT BUTTON
========================================================= */

.gofix-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 13px;
    background: #ffffff;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: all .2s ease;
}

.gofix-logout:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

.gofix-logout i {
    font-size: 12px;
}


/* =========================================================
   MOBILE MENU BUTTON
========================================================= */

.gofix-menu-btn {
    display: none;
    width: 42px;
    height: 42px;
    margin-left: auto;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #0f172a;
    border-radius: 9px;
    cursor: pointer;
    font-size: 18px;
}

.gofix-menu-btn:hover {
    background: #f8fafc;
}


/* =========================================================
   MOBILE MENU
========================================================= */

.gofix-mobile-nav {
    display: none;
    width: 100%;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 12px 25px rgba(15,23,42,.08);
    padding: 10px 20px 20px;
}

.gofix-mobile-nav.open {
    display: block;
}


/* =========================================================
   MOBILE LINKS
========================================================= */

.gofix-mobile-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.gofix-mobile-links li {
    border-bottom: 1px solid #f1f5f9;
}

.gofix-mobile-links a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 5px;
    color: #334155;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.gofix-mobile-links a:hover {
    color: #2563eb;
}

.gofix-mobile-links a.active {
    color: #2563eb;
    font-weight: 700;
}

.gofix-mobile-links a i {
    width: 20px;
    text-align: center;
    color: #64748b;
}

.gofix-mobile-links a.active i {
    color: #2563eb;
}


/* =========================================================
   MOBILE USER
========================================================= */

.gofix-mobile-user {
    margin-top: 15px;
    padding: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}

.gofix-mobile-user-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 50%;
}

.gofix-mobile-user-icon i {
    font-size: 18px;
}

.gofix-mobile-user-info {
    flex: 1;
    min-width: 0;
}

.gofix-mobile-user-info small {
    display: block;
    color: #94a3b8;
    font-size: 10px;
    margin-bottom: 2px;
}

.gofix-mobile-user-info strong {
    display: block;
    color: #334155;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}


/* =========================================================
   MOBILE LOGOUT
========================================================= */

.gofix-mobile-logout {
    margin-top: 10px;
    width: 100%;
    height: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.gofix-mobile-logout:hover {
    background: #fee2e2;
}


/* =========================================================
   TABLET / MOBILE
========================================================= */

@media (max-width: 1000px) {

    .gofix-nav-container {
        height: 64px;
        display: flex;
        padding: 0 18px;
    }

    .gofix-desktop-nav,
    .gofix-user-area {
        display: none;
    }

    .gofix-menu-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 420px) {

    .gofix-nav-container {
        padding: 0 13px;
    }

    .gofix-logo-icon {
        width: 37px;
        height: 37px;
    }

    .gofix-logo-text h2 {
        font-size: 18px;
    }

    .gofix-logo-text p {
        display: none;
    }
}


/* =========================================================
   VERY SMALL SCREEN
========================================================= */

@media (max-width: 330px) {

    .gofix-logo-text h2 {
        font-size: 16px;
    }

    .gofix-menu-btn {
        width: 38px;
        height: 38px;
    }
}

</style>


<!-- =======================================================
     HEADER
======================================================= -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<header class="gofix-header" id="gofixHeader">

    <div class="gofix-nav-container">


        <!-- LOGO -->

        <a
            href="dashboard.php"
            class="gofix-logo"
            aria-label="Gofix Dashboard"
        >

            <span class="gofix-logo-icon">
                <i class="fa-solid fa-house"></i>
            </span>

            <span class="gofix-logo-text">
                <h2>Gofix</h2>
                <p>Services at your doorstep</p>
            </span>

        </a>


        <!-- DESKTOP NAVIGATION -->

        <nav class="gofix-desktop-nav" aria-label="Customer Navigation">

            <ul>

                <?php foreach ($gofixNavItems as $item): ?>

                    <li>
                        <a
                            href="<?= str_replace(' ', '%20', $item[0]) ?>"
                            class="<?= $currentPage === $item[0] ? 'active' : '' ?>"
                        >
                            <i class="fa-solid <?= $item[2] ?>"></i>
                            <?= $item[1] ?>
                        </a>
                    </li>

                <?php endforeach; ?>

            </ul>

        </nav>


        <!-- DESKTOP USER AREA -->

        <div class="gofix-user-area">

            <div class="gofix-user-profile">

                <span class="gofix-user-icon">
                    <i class="fa-solid fa-user"></i>
                </span>

                <span class="gofix-user-name">
                  <a href="profile.php"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></a>  
                </span>

            </div>

            <a
                href="logout.php"
                class="gofix-logout"
                onclick="return confirm('Are you sure you want to logout?');"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>


        <!-- MOBILE MENU BUTTON -->

        <button
            type="button"
            class="gofix-menu-btn"
            id="gofixMenuBtn"
            aria-label="Open menu"
            aria-expanded="false"
        >
            <i class="fa-solid fa-bars"></i>
        </button>


    </div>


    <!-- MOBILE NAVIGATION -->

    <div class="gofix-mobile-nav" id="gofixMobileNav">

        <ul class="gofix-mobile-links">

            <?php foreach ($gofixNavItems as $item): ?>

                <li>
                    <a
                        href="<?= str_replace(' ', '%20', $item[0]) ?>"
                        class="<?= $currentPage === $item[0] ? 'active' : '' ?>"
                    >
                        <i class="fa-solid <?= $item[2] ?>"></i>
                        <?= $item[1] ?>
                    </a>
                </li>

            <?php endforeach; ?>

        </ul>


        <!-- MOBILE USER -->

        <div class="gofix-mobile-user">

            <span class="gofix-mobile-user-icon">
                <i class="fa-solid fa-user"></i>
            </span>

            <div class="gofix-mobile-user-info">
                <small>Logged in as</small>
                <strong>
                    <a href="profile.php"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></a>
                </strong>
            </div>

        </div>


        <!-- MOBILE LOGOUT -->

        <a
            href="logout.php"
            class="gofix-mobile-logout"
            onclick="return confirm('Are you sure you want to logout?');"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</header>


<!-- =======================================================
     NAVBAR JAVASCRIPT
======================================================= -->

<script>

(function () {

    var menuBtn   = document.getElementById("gofixMenuBtn");
    var mobileNav = document.getElementById("gofixMobileNav");
    var header    = document.getElementById("gofixHeader");

    if (!menuBtn || !mobileNav) {
        return;
    }

    var icon = menuBtn.querySelector("i");

    /* मोबाइल मेन्यू खोलना / बंद करना */
    function setOpen(open) {

        mobileNav.classList.toggle("open", open);

        icon.classList.toggle("fa-bars", !open);
        icon.classList.toggle("fa-xmark", open);

        menuBtn.setAttribute("aria-expanded", open ? "true" : "false");
        menuBtn.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    }

    /* बटन दबाने पर टॉगल */
    menuBtn.addEventListener("click", function () {
        setOpen(!mobileNav.classList.contains("open"));
    });

    /* किसी लिंक पर क्लिक करने पर बंद */
    mobileNav.addEventListener("click", function (event) {
        if (event.target.closest("a")) {
            setOpen(false);
        }
    });

    /* बाहर क्लिक करने पर बंद */
    document.addEventListener("click", function (event) {
        if (header && !header.contains(event.target)) {
            setOpen(false);
        }
    });

    /* Escape दबाने पर बंद */
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            setOpen(false);
        }
    });

})();

</script>