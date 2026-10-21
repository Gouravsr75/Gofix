<?php
/*
|--------------------------------------------------------------------------
| GUEST NAVBAR  (top.php)
|--------------------------------------------------------------------------
| Included on public pages when the user is NOT logged in.
| Mirrors the structure of nav.php (logged-in navbar).
|
| Shows:  Logo  ·  Links  ·  Login / Sign Up
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER["PHP_SELF"]);

$gofixGuestNav = [
    ["index.php",          "Home",         "fa-house"],
    ["more.php",           "Services",     "fa-table-cells-large"],
    ["howitis work.php",   "How It Works", "fa-circle-info"],
    ["aboutas.php",        "About Us",     "fa-users"],
    ["contact.php",        "Contact",      "fa-phone"],
];
?>

<!-- =======================================================
     GOFIX GUEST NAVBAR
======================================================= -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

/* =========================================================
   GUEST HEADER SHELL
========================================================= */

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
    transition: box-shadow .2s ease;
}

.gofix-header.scrolled {
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
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
   DESKTOP LINKS (CENTRE)
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
   RIGHT SIDE — AUTH ACTIONS
========================================================= */

.gofix-auth-actions {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 10px;
}

.gofix-btn-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 18px;
    background: #ffffff;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
}

.gofix-btn-login:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.gofix-btn-login i {
    font-size: 13px;
}

.gofix-btn-signup {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 18px;
    background: #2563eb;
    color: #ffffff;
    border: 1px solid transparent;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    box-shadow: 0 2px 8px rgba(37,99,235,.25);
}

.gofix-btn-signup:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 14px rgba(37,99,235,.35);
    transform: translateY(-1px);
}

.gofix-btn-signup i {
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
   MOBILE PANEL
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
   MOBILE AUTH BUTTONS
========================================================= */

.gofix-mobile-auth {
    display: flex;
    gap: 10px;
    margin-top: 16px;
}

.gofix-mobile-auth .gofix-btn-login,
.gofix-mobile-auth .gofix-btn-signup {
    flex: 1;
    justify-content: center;
    padding: 13px 16px;
    font-size: 14px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .gofix-nav-container {
        height: 64px;
        display: flex;
        padding: 0 18px;
    }

    .gofix-desktop-nav,
    .gofix-auth-actions {
        display: none;
    }

    .gofix-menu-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

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

<header class="gofix-header" id="gofixHeader">

    <div class="gofix-nav-container">

        <!-- LOGO -->
        <a
            href="index.php"
            class="gofix-logo"
            aria-label="Gofix Home"
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
        <nav class="gofix-desktop-nav" aria-label="Main Navigation">
            <ul>
                <?php foreach ($gofixGuestNav as $item): ?>
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


        <!-- DESKTOP AUTH ACTIONS -->
        <div class="gofix-auth-actions">
            <a href="login.php" class="gofix-btn-login">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login
            </a>
            <a href="reg.php" class="gofix-btn-signup">
                <i class="fa-solid fa-user-plus"></i>
                Sign Up
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
            <?php foreach ($gofixGuestNav as $item): ?>
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

        <!-- MOBILE AUTH BUTTONS -->
        <div class="gofix-mobile-auth">
            <a href="login.php" class="gofix-btn-login">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login
            </a>
            <a href="reg.php" class="gofix-btn-signup">
                <i class="fa-solid fa-user-plus"></i>
                Sign Up
            </a>
        </div>

    </div>

</header>


<!-- =======================================================
     GUEST NAVBAR JAVASCRIPT
======================================================= -->

<script>
(function () {

    var menuBtn   = document.getElementById("gofixMenuBtn");
    var mobileNav = document.getElementById("gofixMobileNav");
    var header    = document.getElementById("gofixHeader");

    if (!menuBtn || !mobileNav) return;

    var icon = menuBtn.querySelector("i");

    function setOpen(open) {
        mobileNav.classList.toggle("open", open);
        icon.classList.toggle("fa-bars",  !open);
        icon.classList.toggle("fa-xmark",  open);
        menuBtn.setAttribute("aria-expanded", open ? "true" : "false");
        menuBtn.setAttribute("aria-label",    open ? "Close menu" : "Open menu");
    }

    /* Toggle on button click */
    menuBtn.addEventListener("click", function () {
        setOpen(!mobileNav.classList.contains("open"));
    });

    /* Close on link click */
    mobileNav.addEventListener("click", function (e) {
        if (e.target.closest("a")) setOpen(false);
    });

    /* Close on outside click */
    document.addEventListener("click", function (e) {
        if (header && !header.contains(e.target)) setOpen(false);
    });

    /* Close on Escape */
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") setOpen(false);
    });

    /* Scroll shadow */
    function onScroll() {
        header.classList.toggle("scrolled", window.scrollY > 4);
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

})();
</script>