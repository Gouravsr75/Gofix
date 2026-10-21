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

require_once "db.php";

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
        expires_at  DATETIME       NOT NULL,
        used        TINYINT(1)     DEFAULT 0,
        created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$error   = "";
$success = "";
$step    = "email";  // "email" or "otp"

/*
|--------------------------------------------------------------------------
| STEP 1 — USER SUBMITS EMAIL
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "send_otp") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        /* Check if email exists in users table */
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with that email address.";
            $stmt->close();
        } else {
            $user = $result->fetch_assoc();
            $stmt->close();

            /* Invalidate any previous unused tokens for this email */
            $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0")
                 ->bind_param("s", $email);
            $upd = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
            $upd->bind_param("s", $email);
            $upd->execute();
            $upd->close();

            /* Generate OTP & token */
            $otp       = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            $ins = $conn->prepare(
                "INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("ssss", $email, $token, $otp, $expiresAt);

            if ($ins->execute()) {
                /* Store token in session so the next page can verify */
                $_SESSION["reset_token"] = $token;
                $_SESSION["reset_email"] = $email;
                $_SESSION["reset_otp"]   = $otp;     // shown on-screen (no mail server)

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
    <title>Forgot Password | Gofix</title>
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
            min-height: 520px;
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

        .steps-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 18px;
            counter-reset: step-counter;
        }

        .steps-list li {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.9rem;
            color: #e2e8f0;
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.3);
            color: #60a5fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.8rem;
            font-weight: 700;
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
            line-height: 1.5;
        }

        /* Lock illustration */
        .lock-illustration {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .lock-illustration svg {
            color: var(--primary);
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
            padding: 0 14px 0 42px;
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
            <h1>Reset your password.</h1>
            <p>Don't worry, it happens! Follow these simple steps to regain access to your account.</p>

            <ul class="steps-list">
                <li>
                    <span class="step-number">1</span>
                    Enter your registered email address
                </li>
                <li>
                    <span class="step-number">2</span>
                    Receive a 6-digit verification code
                </li>
                <li>
                    <span class="step-number">3</span>
                    Enter the code and set a new password
                </li>
                <li>
                    <span class="step-number">4</span>
                    Log in with your new password
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

        <div class="lock-illustration">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <div class="form-header">
            <h2>Forgot Password?</h2>
            <p>Enter the email address associated with your account and we'll send you a verification code to reset your password.</p>
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

        <form action="forgot_password.php" method="POST" class="form-grid" autocomplete="off" novalidate>
            <input type="hidden" name="action" value="send_otp">

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
                        autofocus
                    >
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">Send Verification Code</button>
        </form>

        <a href="login.php" class="back-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Sign In
        </a>
    </div>

</div>

</body>
</html>
