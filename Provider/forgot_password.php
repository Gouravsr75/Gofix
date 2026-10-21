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

/*
|--------------------------------------------------------------------------
| IF PROVIDER ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/
if (isset($_SESSION["provider_id"]) && $_SESSION["provider_id"] > 0) {
    header("Location: dashboard.php");
    exit;
}

include "../db.php";

/*
|--------------------------------------------------------------------------
| CREATE password_resets TABLE IF NOT EXISTS
|--------------------------------------------------------------------------
*/
$conn->query("
    CREATE TABLE IF NOT EXISTS password_resets (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        email       VARCHAR(255)   NOT NULL,
        token       VARCHAR(64)    NOT NULL,
        otp         VARCHAR(6)     NOT NULL,
        user_type   VARCHAR(20)    NOT NULL DEFAULT 'customer',
        expires_at  DATETIME       NOT NULL,
        used        TINYINT(1)     DEFAULT 0,
        created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* Add user_type column if table already exists but column is missing */
$colCheck = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'user_type'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE password_resets ADD COLUMN user_type VARCHAR(20) NOT NULL DEFAULT 'customer' AFTER otp");
}

$error   = "";
$success = "";

/*
|--------------------------------------------------------------------------
| STEP 1 — PROVIDER SUBMITS EMAIL
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "send_otp") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        /* Check if email exists in service_providers table */
        $stmt = $conn->prepare("SELECT id, provider_name FROM service_providers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No provider account found with that email address.";
            $stmt->close();
        } else {
            $provider = $result->fetch_assoc();
            $stmt->close();

            /* Invalidate any previous unused tokens for this email + provider */
            $upd = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND user_type = 'provider' AND used = 0");
            $upd->bind_param("s", $email);
            $upd->execute();
            $upd->close();

            /* Generate OTP & token */
            $otp       = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date("Y-m-d H:i:s", strtotime("+15 minutes"));
            $userType  = "provider";

            $ins = $conn->prepare(
                "INSERT INTO password_resets (email, token, otp, user_type, expires_at) VALUES (?, ?, ?, ?, ?)"
            );
            $ins->bind_param("sssss", $email, $token, $otp, $userType, $expiresAt);

            if ($ins->execute()) {
                /* Store token in session so the next page can verify */
                $_SESSION["provider_reset_token"] = $token;
                $_SESSION["provider_reset_email"] = $email;
                $_SESSION["provider_reset_otp"]   = $otp;

                $ins->close();
                header("Location: reset_password.php");
                exit;
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $ins->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Provider - Gofix</title>
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
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
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

        .logo-icon svg {
            width: 32px;
            height: 32px;
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
            line-height: 1.5;
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
            box-shadow: 0 0 0 3px rgba(18,100,214,0.10);
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #1264d6;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
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
<body class="provider-auth-page">

<div class="login-container">

    <div class="login-box">

        <div class="logo">

            <div class="logo-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <h2>Forgot Password</h2>

            <p class="subtitle">
                Enter the email address associated with your provider account and we'll send you a verification code.
            </p>

        </div>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="forgot_password.php">

            <input type="hidden" name="action" value="send_otp">

            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your registered email"
                    required
                    autofocus
                >

            </div>

            <button type="submit" class="login-btn">
                Send Verification Code
            </button>

        </form>

        <a href="login.php" class="back-link">
            ← Back to Login
        </a>

    </div>

</div>

</body>
</html>
