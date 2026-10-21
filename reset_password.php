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
| IF USER ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/
if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GUARD — must come from forgot_password.php
|--------------------------------------------------------------------------
*/
if (
    empty($_SESSION["reset_token"]) ||
    empty($_SESSION["reset_email"]) ||
    empty($_SESSION["reset_otp"])
) {
    header("Location: forgot_password.php");
    exit;
}

require_once "db.php";

$error   = "";
$success = "";
$otp     = $_SESSION["reset_otp"];   // shown on-screen since no mail server
$email   = $_SESSION["reset_email"];
$token   = $_SESSION["reset_token"];

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
             WHERE token = ? AND email = ? AND used = 0
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Invalid or expired reset link. Please try again.";
            $stmt->close();
            /* Clear session data */
            unset($_SESSION["reset_token"], $_SESSION["reset_email"], $_SESSION["reset_otp"]);
        } else {
            $row = $result->fetch_assoc();
            $stmt->close();

            if (strtotime($row["expires_at"]) < time()) {
                $error = "The verification code has expired. Please request a new one.";
                /* Mark as used */
                $upd = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $upd->bind_param("i", $row["id"]);
                $upd->execute();
                $upd->close();
                unset($_SESSION["reset_token"], $_SESSION["reset_email"], $_SESSION["reset_otp"]);

            } elseif ($submittedOtp !== $row["otp"]) {
                $error = "Invalid verification code. Please try again.";

            } else {
                /*
                |--------------------------------------------------------------------------
                | UPDATE PASSWORD
                |--------------------------------------------------------------------------
                */
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $upd->bind_param("ss", $hashedPassword, $email);

                if ($upd->execute()) {
                    /* Mark token as used */
                    $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                    $mark->bind_param("s", $token);
                    $mark->execute();
                    $mark->close();

                    /* Clear session data */
                    unset($_SESSION["reset_token"], $_SESSION["reset_email"], $_SESSION["reset_otp"]);

                    /* Flash success and redirect to login */
                    $_SESSION["flash_success"] = "Password reset successfully! Please log in with your new password.";
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

/* Mask email for display: j***@example.com */
$emailParts  = explode("@", $email);
$maskedEmail = substr($emailParts[0], 0, 1) . "***@" . ($emailParts[1] ?? "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Gofix</title>
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
            --info-bg: #eff6ff;
            --info-border: #bfdbfe;
            --info-text: #1e40af;
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
            min-height: 580px;
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

        .security-tips {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .security-tips li {
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
            margin-bottom: 24px;
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
            line-height: 1.5;
        }

        /* Shield icon */
        .shield-illustration {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .shield-illustration svg {
            color: var(--primary);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .alert-info {
            background: var(--info-bg);
            border: 1px solid var(--info-border);
            color: var(--info-text);
        }

        .alert-icon {
            flex-shrink: 0;
            display: flex;
            margin-top: 1px;
        }

        /* OTP Display Box */
        .otp-display {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px dashed #86efac;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }

        .otp-display .otp-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .otp-display .otp-code {
            font-size: 2rem;
            font-weight: 700;
            color: #15803d;
            letter-spacing: 0.3em;
            font-family: 'Courier New', monospace;
        }

        .otp-display .otp-note {
            font-size: 0.75rem;
            color: #166534;
            margin-top: 8px;
            opacity: 0.8;
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

        .input-wrapper input.otp-input {
            font-size: 1.2rem;
            letter-spacing: 0.25em;
            font-weight: 600;
            text-align: center;
            padding: 0 14px;
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

        .password-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-top: 24px;
            transition: color 0.15s;
        }

        .back-link:hover {
            color: var(--primary);
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

            .password-row {
                grid-template-columns: 1fr;
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

            .otp-display .otp-code {
                font-size: 1.5rem;
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
            <h1>Set a new password.</h1>
            <p>Choose a strong password to keep your account safe. Here are some tips for a secure password:</p>

            <ul class="security-tips">
                <li>
                    <span class="check-badge">✓</span>
                    Use at least 8 characters
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Mix uppercase and lowercase letters
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Include numbers and special characters
                </li>
                <li>
                    <span class="check-badge">✓</span>
                    Avoid using personal information
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

        <div class="shield-illustration">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>

        <div class="form-header">
            <h2>Reset Password</h2>
            <p>A verification code has been generated for <strong><?= htmlspecialchars($maskedEmail) ?></strong>. Enter the code below along with your new password.</p>
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

        <!-- OTP Display (since no email server is configured) -->
        <div class="otp-display">
            <div class="otp-label">Your Verification Code</div>
            <div class="otp-code"><?= htmlspecialchars($otp) ?></div>
            <div class="otp-note">This code expires in 15 minutes</div>
        </div>

        <form action="reset_password.php" method="POST" class="form-grid" autocomplete="off" novalidate>
            <input type="hidden" name="action" value="reset">

            <!-- OTP -->
            <div class="form-group">
                <label for="otp">Verification Code</label>
                <div class="input-wrapper">
                    <input
                        type="text"
                        id="otp"
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
            </div>

            <!-- New Password + Confirm -->
            <div class="password-row">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            placeholder="Min. 8 chars"
                            minlength="8"
                            required
                        >
                        <button type="button" class="toggle-btn" onclick="togglePassword('new_password')" aria-label="Toggle password visibility">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Repeat password"
                            minlength="8"
                            required
                        >
                        <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')" aria-label="Toggle password visibility">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>

        <a href="forgot_password.php" class="back-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Try a different email
        </a>
    </div>

</div>

<script>
function togglePassword(fieldId) {
    var input = document.getElementById(fieldId);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
