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

/*
|--------------------------------------------------------------------------
| GUARD — must come from forgot_password.php
|--------------------------------------------------------------------------
*/
if (
    empty($_SESSION["provider_reset_token"]) ||
    empty($_SESSION["provider_reset_email"]) ||
    empty($_SESSION["provider_reset_otp"])
) {
    header("Location: forgot_password.php");
    exit;
}

include "../db.php";

$error   = "";
$success = "";
$otp     = $_SESSION["provider_reset_otp"];
$email   = $_SESSION["provider_reset_email"];
$token   = $_SESSION["provider_reset_token"];

/*
|--------------------------------------------------------------------------
| HANDLE OTP + NEW PASSWORD SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "reset") {

    $submittedOtp     = trim($_POST["otp"] ?? "");
    $newPassword      = $_POST["new_password"] ?? "";
    $confirmPassword  = $_POST["confirm_password"] ?? "";

    if ($submittedOtp === "") {
        $error = "Please enter the verification code.";
    } elseif ($newPassword === "" || $confirmPassword === "") {
        $error = "Please enter and confirm your new password.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {

        /* Verify token + OTP from database */
        $stmt = $conn->prepare(
            "SELECT id, otp, expires_at
             FROM password_resets
             WHERE token = ? AND email = ? AND user_type = 'provider' AND used = 0
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Invalid or expired reset link. Please try again.";
            $stmt->close();
            unset($_SESSION["provider_reset_token"], $_SESSION["provider_reset_email"], $_SESSION["provider_reset_otp"]);
        } else {
            $row = $result->fetch_assoc();
            $stmt->close();

            if (strtotime($row["expires_at"]) < time()) {
                $error = "The verification code has expired. Please request a new one.";
                $upd = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $upd->bind_param("i", $row["id"]);
                $upd->execute();
                $upd->close();
                unset($_SESSION["provider_reset_token"], $_SESSION["provider_reset_email"], $_SESSION["provider_reset_otp"]);

            } elseif ($submittedOtp !== $row["otp"]) {
                $error = "Invalid verification code. Please try again.";

            } else {
                /*
                |--------------------------------------------------------------------------
                | UPDATE PASSWORD in service_providers TABLE
                |--------------------------------------------------------------------------
                */
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE service_providers SET password = ? WHERE email = ?");
                $upd->bind_param("ss", $hashedPassword, $email);

                if ($upd->execute()) {
                    /* Mark token as used */
                    $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                    $mark->bind_param("s", $token);
                    $mark->execute();
                    $mark->close();

                    /* Clear session data */
                    unset($_SESSION["provider_reset_token"], $_SESSION["provider_reset_email"], $_SESSION["provider_reset_otp"]);

                    /* Flash success and redirect to login */
                    $_SESSION["provider_flash_success"] = "Password reset successfully! Please log in with your new password.";
                    $upd->close();
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                $upd->close();
            }
        }
    }
}

/* Mask email for display */
$emailParts  = explode("@", $email);
$maskedEmail = substr($emailParts[0], 0, 1) . "***@" . ($emailParts[1] ?? "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Provider - Gofix</title>
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
            margin-bottom: 20px;
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
            margin-bottom: 20px;
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

        /* OTP Display Box */
        .otp-display {
            background: #f0fdf4;
            border: 2px dashed #86efac;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            margin-bottom: 22px;
        }

        .otp-display .otp-label {
            font-size: 11px;
            font-weight: bold;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .otp-display .otp-code {
            font-size: 2rem;
            font-weight: 700;
            color: #15803d;
            letter-spacing: 0.3em;
            font-family: 'Courier New', monospace;
        }

        .otp-display .otp-note {
            font-size: 11px;
            color: #166534;
            margin-top: 6px;
            opacity: 0.8;
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

        .otp-input {
            font-size: 1.3rem;
            letter-spacing: 0.25em;
            font-weight: 600;
            text-align: center;
        }

        .password-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
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

            .password-row {
                grid-template-columns: 1fr;
            }

            .otp-display .otp-code {
                font-size: 1.5rem;
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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>

            <h2>Reset Password</h2>

            <p class="subtitle">
                A verification code has been generated for <strong><?= htmlspecialchars($maskedEmail) ?></strong>. Enter the code below with your new password.
            </p>

        </div>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <!-- OTP Display (since no email server is configured) -->
        <div class="otp-display">
            <div class="otp-label">Your Verification Code</div>
            <div class="otp-code"><?= htmlspecialchars($otp) ?></div>
            <div class="otp-note">This code expires in 15 minutes</div>
        </div>

        <form method="POST" action="reset_password.php">

            <input type="hidden" name="action" value="reset">

            <div class="form-group">

                <label>Verification Code</label>

                <input
                    type="text"
                    name="otp"
                    class="otp-input"
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    required
                    autofocus
                >

            </div>

            <div class="password-row">

                <div class="form-group">
                    <label>New Password</label>
                    <input
                        type="password"
                        name="new_password"
                        placeholder="Min. 8 chars"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Repeat password"
                        minlength="8"
                        required
                    >
                </div>

            </div>

            <button type="submit" class="login-btn">
                Reset Password
            </button>

        </form>

        <a href="forgot_password.php" class="back-link">
            ← Try a different email
        </a>

    </div>

</div>

</body>
</html>
