
<?php

// Hinglish: Login sahi ho to session banti hai aur admin dashboard par jaata hai.
session_start();
include "../db.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET["session_check"]) && $_GET["session_check"] === "1") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["authenticated" => !empty($_SESSION["admin_id"])]);
    exit;
}

// If already logged in
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $error = "Please enter email and password.";
    } else {

        $stmt = $conn->prepare("
            SELECT id, admin_name, email, password, status
            FROM admins
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $admin = $result->fetch_assoc();

                // Check account status
                if ($admin['status'] !== 'active') {

                    $error = "Your admin account is blocked.";

                } elseif (password_verify($password, $admin['password'])) {

                    // Login successful
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['admin_name'];
                    $_SESSION['admin_email'] = $admin['email'];

                    setcookie("admin_logged_in", "1", time() + 86400 * 30, "/");

                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title><script>document.cookie = "admin_logged_in=1; path=/; max-age=2592000"; window.location.replace("dashboard.php");</script></head><body><script>window.location.replace("dashboard.php");</script></body></html>';
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

    <title>Admin Login</title>
    <script>
    (function () {
        var isExplicitLogout = (window.location.search.indexOf("logged_out=1") !== -1);
        if (isExplicitLogout) {
            try { sessionStorage.removeItem("admin_logged_in"); localStorage.removeItem("admin_logged_in"); } catch (e) {}
            document.cookie = "admin_logged_in=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            return;
        }
        var isAuth = (document.cookie.indexOf("admin_logged_in=1") !== -1) ||
                     (sessionStorage.getItem("admin_logged_in") === "1") ||
                     (localStorage.getItem("admin_logged_in") === "1");
        if (isAuth) {
            window.location.replace("dashboard.php");
        }
        window.addEventListener("pageshow", function (e) {
            var isStillAuth = (document.cookie.indexOf("admin_logged_in=1") !== -1) ||
                              (sessionStorage.getItem("admin_logged_in") === "1") ||
                              (localStorage.getItem("admin_logged_in") === "1");
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
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.10);
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: #1264d6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            font-weight: bold;
        }

        .logo h2 {
            color: #222;
            margin-bottom: 5px;
        }

        .logo p {
            color: #777;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d7dce3;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #1264d6;
            box-shadow: 0 0 0 2px rgba(18, 100, 214, 0.08);
        }

        .login-btn {
            width: 100%;
            border: none;
            background: #1264d6;
            color: white;
            padding: 13px;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .login-btn:hover {
            background: #0d55b8;
        }

        .error {
            background: #ffe8e8;
            color: #c62828;
            border: 1px solid #ffcaca;
            padding: 11px 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            margin-top: 22px;
            color: #888;
            font-size: 13px;
        }

        @media (max-width: 480px) {

            .login-box {
                padding: 25px 20px;
            }

        }

    </style>

    <link rel="stylesheet" href="admin-ui.css">

</head>

<body>

    <div class="login-box">

        <div class="logo">

            <div class="logo-icon">
                A
            </div>

            <h2>Admin Login</h2>

            <p>Home Service Booking Platform</p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter admin email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>


            <button type="submit" class="login-btn">
                Login
            </button>

        </form>


        <div class="footer">
            Admin Panel &copy; <?php echo date("Y"); ?>
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
