<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET["session_check"]) && $_GET["session_check"] === "1") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["authenticated" => !empty($_SESSION["provider_id"])]);
    exit;
}

include "../db.php";

/* If provider is already logged in */
if (isset($_SESSION['provider_id']) && $_SESSION['provider_id'] > 0) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = $_SESSION["provider_flash_success"] ?? "";
unset($_SESSION["provider_flash_success"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST['login'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($login === "" || $password === "") {
        $error = "Please enter mobile/email and password.";
    } else {

        $sql = "SELECT * FROM service_providers
                WHERE mobile = ? OR email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {

            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $provider = $result->fetch_assoc();

                /* Check provider status */

                if ($provider['status'] === 'pending') {

                    $error = "Your provider account is still pending approval.";

                } elseif ($provider['status'] === 'inactive') {

                    $error = "Your provider account is inactive.";

                } elseif ($provider['status'] === 'blocked') {

                    $error = "Your provider account has been blocked.";

                } elseif ($provider['status'] !== 'active') {

                    $error = "Your account is not active.";

                } elseif (!password_verify($password, $provider['password'])) {

                    $error = "Invalid mobile/email or password.";

                } else {

                    /* Successful login */

                    session_regenerate_id(true);

                    $_SESSION['provider_id']       = $provider['id'];
                    $_SESSION['provider_name']     = $provider['provider_name'];
                    $_SESSION['provider_mobile']   = $provider['mobile'];
                    $_SESSION['provider_email']    = $provider['email'];
                    $_SESSION['provider_area']     = $provider['area'];
                    $_SESSION['provider_city']     = $provider['city'];
                    $_SESSION['provider_district'] = $provider['district'];
                    $_SESSION['provider_services'] = $provider['service_categories'];
                    $_SESSION['provider_photo_available'] = !empty($provider['provider_photo_file']);

                    setcookie("provider_logged_in", "1", time() + 86400 * 30, "/");

                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title><script>document.cookie = "provider_logged_in=1; path=/; max-age=2592000; SameSite=Lax"; try { sessionStorage.setItem("provider_logged_in", "1"); localStorage.setItem("provider_logged_in", "1"); } catch(e){} window.location.replace("dashboard.php");</script></head><body><noscript><meta http-equiv="refresh" content="0;url=dashboard.php"></noscript><script>window.location.replace("dashboard.php");</script></body></html>';
                    exit;
                }

            } else {

                $error = "Invalid mobile/email or password.";
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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Provider Login</title>
    <script>
    (function () {
        "use strict";

        // Check if explicitly logged out or session expired
        var isExplicitLogout = (window.location.search.indexOf("logged_out=1") !== -1) ||
                               (window.location.search.indexOf("session_expired=1") !== -1);

        if (isExplicitLogout) {
            try { sessionStorage.removeItem("provider_logged_in"); } catch (e) {}
            try { localStorage.removeItem("provider_logged_in"); } catch (e) {}
            document.cookie = "provider_logged_in=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            return;
        }

        // If authenticated provider visits or hits Back button into login.php, bounce back to dashboard immediately!
        var isAuth = (document.cookie.indexOf("provider_logged_in=1") !== -1) ||
                     (sessionStorage.getItem("provider_logged_in") === "1") ||
                     (localStorage.getItem("provider_logged_in") === "1");

        if (isAuth) {
            window.location.replace("dashboard.php");
        }

        window.addEventListener("pageshow", function (e) {
            var isStillAuth = (document.cookie.indexOf("provider_logged_in=1") !== -1) ||
                              (sessionStorage.getItem("provider_logged_in") === "1") ||
                              (localStorage.getItem("provider_logged_in") === "1");
            if (!isExplicitLogout && (isStillAuth || e.persisted)) {
                window.location.replace("dashboard.php");
            }
        });
    })();
    </script>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-box {
            background: #ffffff;
            padding: 35px 30px;

            border-radius: 15px;

            box-shadow:
                0 8px 30px rgba(0,0,0,0.10);
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-icon {
            width: 65px;
            height: 65px;

            margin: auto;

            background: #1264d6;
            color: white;

            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 28px;
            font-weight: bold;
        }

        h2 {
            text-align: center;
            color: #222;
            margin-top: 15px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-top: 7px;
            margin-bottom: 25px;
        }

        .error {
            background: #ffe8e8;
            color: #d00000;

            padding: 12px;

            border-radius: 8px;

            font-size: 14px;

            margin-bottom: 18px;

            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;

            font-size: 14px;
            font-weight: bold;

            color: #333;

            margin-bottom: 7px;
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d6dbe3;

            border-radius: 8px;

            font-size: 15px;

            outline: none;

            transition: 0.2s;
        }

        input:focus {
            border-color: #1264d6;

            box-shadow:
                0 0 0 3px rgba(18,100,214,0.10);
        }

        .login-btn {
            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #1264d6;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            margin-top: 5px;
        }

        .login-btn:hover {
            background: #0d55b8;
        }

        .bottom-text {
            text-align: center;

            margin-top: 22px;

            font-size: 14px;

            color: #777;
        }

        .back-link {
            display: block;

            text-align: center;

            margin-top: 12px;

            color: #1264d6;

            text-decoration: none;

            font-weight: bold;
        }

        @media (max-width: 480px) {

            body {
                padding: 15px;
            }

            .login-box {
                padding: 28px 20px;
            }

        }

    </style>
<link rel="stylesheet" href="provider-theme.css?v=<?= filemtime(__DIR__ . '/provider-theme.css') ?>">
</head>
<body class="provider-auth-page provider-login-page">

<div class="login-container">

    <div class="login-box">

        <div class="logo">

            <div class="logo-icon">
                G
            </div>

            <h2>Provider Login</h2>

            <p class="subtitle">
                Login to manage your service requests
            </p>

        </div>

        <?php if ($success !== ""): ?>

            <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:8px;font-size:14px;margin-bottom:18px;text-align:center;">
                <?php echo htmlspecialchars($success); ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label>
                    Mobile Number / Email
                </label>

                <input
                    type="text"
                    name="login"
                    placeholder="Enter mobile number or email"
                    value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>

            <div style="text-align:right;margin-bottom:5px;">
                <a href="forgot_password.php" style="font-size:13px;color:#1264d6;text-decoration:none;font-weight:500;">Forgot password?</a>
            </div>

            <button
                type="submit"
                class="login-btn"
            >
                Login
            </button>

        </form>


        <div class="bottom-text">
            Are you a service provider?
        </div>

        <a href="../index.php" class="back-link">
            ← Back to Home
        </a>

    </div>

</div>

<script>
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
                    window.location.replace("dashboard.php");
                } else {
                    checking = false;
                }
            })
            .catch(function () { checking = false; });
    }
    redirectIfLoggedIn();
    window.addEventListener("pageshow", function (e) {
        if (e.persisted) { redirectIfLoggedIn(); }
    });
    window.addEventListener("popstate", function () {
        redirectIfLoggedIn();
    });
})();
</script>

</body>
</html>
