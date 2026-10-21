<?php
session_start();

/*
|--------------------------------------------------------------------------
| NO CACHE
|--------------------------------------------------------------------------
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* Small no-cache endpoint used when the browser restores this page from history. */
if (isset($_GET["session_check"]) && $_GET["session_check"] === "1") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["authenticated" => !empty($_SESSION["user_id"])]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SERVICE REDIRECT PARAMETER
|--------------------------------------------------------------------------
*/
$service_id = intval($_GET["service_id"] ?? $_POST["service_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| IF USER ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/
if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
    $target = ($service_id > 0) ? "booking.php?service_id=" . $service_id : "dashboard.php";
    header("Location: " . $target);
    exit;
}

require_once "db.php";

$error = "";
$success = $_SESSION["flash_success"] ?? "";
unset($_SESSION["flash_success"]);

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, mobile, email, city, password, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | CHECK ACCOUNT STATUS
                |--------------------------------------------------------------------------
                */
                if (
                    isset($user["status"]) &&
                    strtolower($user["status"]) !== "active"
                ) {

                    $error = "Your account is not active.";

                } elseif (password_verify($password, $user["password"])) {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION ID
                    |--------------------------------------------------------------------------
                    */
                    session_regenerate_id(true);

                    /*
                    |--------------------------------------------------------------------------
                    | STORE USER DATA
                    |--------------------------------------------------------------------------
                    */
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["mobile"] = $user["mobile"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["city"] = $user["city"];
                    $_SESSION["logged_in"] = true;
                    $_SESSION["login_time"] = time();

                    setcookie("user_logged_in", "1", time() + 86400 * 30, "/");

                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT TO DASHBOARD (REPLACE HISTORY)
                    |--------------------------------------------------------------------------
                    | location.replace() removes login.php from the browser history stack,
                    | so the Back button cannot go back to login.php while logged in.
                    |--------------------------------------------------------------------------
                    */
                    $redirectTarget = ($service_id > 0) ? "booking.php?service_id=" . $service_id : "dashboard.php";
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title>';
                    echo '<script>';
                    echo 'document.cookie = "user_logged_in=1; path=/; max-age=2592000";';
                    echo 'try { sessionStorage.setItem("user_logged_in", "1"); localStorage.setItem("user_logged_in", "1"); } catch(e){}';
                    echo 'window.location.replace(' . json_encode($redirectTarget) . ');';
                    echo '</script></head><body>';
                    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectTarget) . '"></noscript>';
                    echo '<script>window.location.replace(' . json_encode($redirectTarget) . ');</script>';
                    echo '</body></html>';
                    exit;

                } else {

                    $error = "Invalid email or password.";
                }

            } else {

                $error = "Invalid email or password.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Gofix</title>
    <script>
    (function () {
        "use strict";

        // Check if explicitly logged out or session expired
        var isExplicitLogout = (window.location.search.indexOf("logged_out=1") !== -1) ||
                               (window.location.search.indexOf("session_expired=1") !== -1);

        if (isExplicitLogout) {
            try { sessionStorage.removeItem("user_logged_in"); } catch (e) {}
            try { localStorage.removeItem("user_logged_in"); } catch (e) {}
            document.cookie = "user_logged_in=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            return;
        }

        // If authenticated user visits or hits Back button into login.php, bounce back to dashboard immediately!
        var isAuth = (document.cookie.indexOf("user_logged_in=1") !== -1) ||
                     (sessionStorage.getItem("user_logged_in") === "1") ||
                     (localStorage.getItem("user_logged_in") === "1");

        if (isAuth) {
            window.location.replace("dashboard.php");
        }

        window.addEventListener("pageshow", function (e) {
            var isStillAuth = (document.cookie.indexOf("user_logged_in=1") !== -1) ||
                              (sessionStorage.getItem("user_logged_in") === "1") ||
                              (localStorage.getItem("user_logged_in") === "1");
            if (!isExplicitLogout && (isStillAuth || e.persisted)) {
                window.location.replace("dashboard.php");
            }
        });
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --surface: #ffffff;
            --bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-focus: #3b82f6;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #b91c1c;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #166534;
            --radius-md: 10px;
            --radius-lg: 18px;
            --shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* =========================================
           AUTH CONTAINER — split panel
           ========================================= */
        .auth-container {
            width: 100%;
            max-width: 960px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1.15fr 1.35fr;
            overflow: hidden;
            min-height: 560px;
        }

        /* =========================================
           LEFT HERO
           ========================================= */
        .auth-hero {
            background: linear-gradient(145deg, #1e3a8a, #0f172a);
            color: #ffffff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-body h1 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.25;
            margin: 32px 0 16px;
        }

        .hero-body p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .features-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: #e2e8f0;
        }

        .check-badge {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.3);
            color: #60a5fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.7rem;
        }

        .hero-footer {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 32px;
        }

        /* =========================================
           RIGHT FORM
           ========================================= */
        .auth-form-wrapper {
            padding: 52px 48px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 6px;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .alert-icon {
            flex-shrink: 0;
            display: flex;
        }

        /* Form */
        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            height: 46px;
            padding: 0 46px 0 42px;
            font-size: 0.9rem;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            outline: none;
            transition: all 0.15s ease-in-out;
            font-family: inherit;
        }

        .input-wrapper input::placeholder {
            color: #94a3b8;
        }

        .input-wrapper input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color 0.15s;
        }

        .toggle-btn:hover {
            color: var(--text-main);
        }

        .forgot-row {
            display: flex;
            justify-content: flex-end;
            margin-top: -8px;
        }

        .forgot-row a {
            font-size: 0.82rem;
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s;
        }

        .forgot-row a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .submit-btn {
            height: 46px;
            background: var(--primary);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, transform 0.1s ease;
            font-family: inherit;
            margin-top: 4px;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        .submit-btn:active {
            transform: scale(0.99);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 4px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 24px;
        }

        .footer-note a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        /* =========================================
           RESPONSIVE
           ========================================= */
        @media (max-width: 860px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .auth-hero {
                display: none;
            }

            .auth-form-wrapper {
                padding: 36px 24px;
            }
        }

        @media (max-width: 400px) {
            body {
                padding: 12px;
            }

            .auth-form-wrapper {
                padding: 28px 20px;
            }

            .form-header h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">

    <!-- =========================================
         LEFT HERO
         ========================================= -->
    <div class="auth-hero">
        <div class="brand-badge">
            <div class="brand-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span>Gofix</span>
        </div>

        <div class="hero-body">
            <h1>Welcome back.</h1>
            <p>Sign in to manage your bookings, track service requests, and connect with trusted professionals — all in one place.</p>

            <ul class="features-list">
                <li>
                    <span class="check-badge">✓</span>
                    100% Verified & background-checked pros
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Instant booking & real-time job tracking
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Transparent pricing with zero hidden fees
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Satisfaction guarantee on every job
                </li>
            </ul>
        </div>

        <div class="hero-footer">
            &copy; <?= date('Y') ?> Gofix. All rights reserved.
        </div>
    </div>

    <!-- =========================================
         RIGHT FORM
         ========================================= -->
    <div class="auth-form-wrapper">
        <div class="form-header">
            <h2>Sign In</h2>
            <p>Enter your credentials to access your account</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <span class="alert-icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <span class="alert-icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="form-grid" autocomplete="off" novalidate>
            <?php if ($service_id > 0): ?>
                <input type="hidden" name="service_id" value="<?= (int)$service_id ?>">
            <?php endif; ?>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="name@example.com"
                        required
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="toggle-btn" id="togglePassword" aria-label="Toggle password visibility">
                        <svg id="eyeIcon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Forgot Password -->
            <div class="forgot-row">
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">Sign In</button>

        </form>

        <div class="divider">
            <span>New to Gofix?</span>
        </div>

        <p class="footer-note">
            Don't have an account? <a href="reg.php">Create Account</a>
        </p>
    </div>

</div>

<script>
/*
|--------------------------------------------------------------------------
| PASSWORD TOGGLE
|--------------------------------------------------------------------------
*/
(function () {
    var btn = document.getElementById('togglePassword');
    var input = document.getElementById('password');
    if (!btn || !input) return;

    btn.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
})();

/*
|--------------------------------------------------------------------------
| LOGIN PAGE — BACK-BUTTON PROTECTION
|--------------------------------------------------------------------------
| Agar user login kar chuka hai aur back button dabata hai to ye script
| usse turant dashboard par wapas bhej deti hai. Ye har baar page load,
| pageshow (BFCache), aur popstate (back/forward) par check karti hai.
|--------------------------------------------------------------------------
*/
(function () {
    "use strict";

    var checking = false;

    function redirectIfLoggedIn() {
        if (checking) return;
        checking = true;

        fetch("login.php?session_check=1&_=" + Date.now(), {
            cache: "no-store",
            credentials: "same-origin"
        })
            .then(function (r) { return r.json(); })
            .then(function (s) {
                if (s.authenticated) {
                    /* Replace so login page is removed from history */
                    window.location.replace("dashboard.php");
                } else {
                    checking = false;
                }
            })
            .catch(function () { checking = false; });
    }

    /* Check immediately when page loads */
    redirectIfLoggedIn();

    /* Check when page is restored from BFCache */
    window.addEventListener("pageshow", function (e) {
        if (e.persisted) {
            redirectIfLoggedIn();
        }
    });

    /* Check when user navigates back/forward to this page */
    window.addEventListener("popstate", function () {
        redirectIfLoggedIn();
    });
})();
</script>

</body>
</html>
