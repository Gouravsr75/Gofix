<?php
session_start();
require_once 'db.php';

$errors = [];
$old = [
    'full_name' => '',
    'mobile'    => '',
    'email'     => '',
    'city'      => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize & Trim
    $full_name        = trim($_POST['full_name'] ?? '');
    $mobile           = trim($_POST['mobile'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $city             = trim($_POST['city'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Cache inputs for form repopulation
    $old['full_name'] = $full_name;
    $old['mobile']    = $mobile;
    $old['email']     = $email;
    $old['city']      = $city;

    // Validation
    if (empty($full_name) || empty($mobile) || empty($email) || empty($city) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be exactly 10 digits.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check for existing User (Email or Mobile)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE email = ? OR mobile = ? LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $mobile);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "An account with this email or mobile number already exists.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Insert Record
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (full_name, mobile, email, city, password) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $insert_sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", $full_name, $mobile, $email, $city, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                
                $_SESSION['flash_success'] = "Account created successfully! Please log in.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Database error: Could not complete registration.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Gofix</title>
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

        .auth-container {
            width: 100%;
            max-width: 1080px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1.15fr 1.35fr;
            overflow: hidden;
        }

        /* Hero / Benefits Section */
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
        }

        .hero-footer {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 32px;
        }

        /* Form Section */
        .auth-form-wrapper {
            padding: 44px;
            background: #ffffff;
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
            margin-top: 4px;
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .alert-error ul {
            list-style-position: inside;
        }

        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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

        .input-wrapper input,
        .input-wrapper select {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 42px;
            font-size: 0.9rem;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            outline: none;
            transition: all 0.15s ease-in-out;
        }

        .input-wrapper select {
            appearance: none;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            right: 14px;
            pointer-events: none;
            color: var(--text-muted);
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus {
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

        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 4px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .terms-row input {
            accent-color: var(--primary);
            margin-top: 2px;
            width: 15px;
            height: 15px;
            cursor: pointer;
        }

        .terms-row a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .terms-row a:hover {
            text-decoration: underline;
        }

        .submit-btn {
            height: 44px;
            background: var(--primary);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
            margin-top: 8px;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        .footer-note {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 20px;
        }

        .footer-note a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media (max-width: 860px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            .auth-hero {
                display: none;
            }
            .auth-form-wrapper {
                padding: 32px 24px;
            }
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Left Hero -->
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
            <h1>Reliable services at your doorstep.</h1>
            <p>Join thousands of happy homeowners who hire background-checked, top-rated local professionals through Gofix.</p>

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
            &copy; <?= date('Y') ?> Gofix Inc. All rights reserved.
        </div>
    </div>

    <!-- Right Form -->
    <div class="auth-form-wrapper">
        <div class="form-header">
            <h2>Create Account</h2>
            <p>Enter your details below to set up your account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="form-grid" novalidate>
            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <input type="text" id="full_name" name="full_name" placeholder="John Doe" value="<?= htmlspecialchars($old['full_name']) ?>" required>
                </div>
            </div>

            <!-- Mobile & Email -->
            <div class="grid-2">
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </span>
                        <input type="tel" id="mobile" name="mobile" placeholder="10-digit number" maxlength="10" pattern="[0-9]{10}" value="<?= htmlspecialchars($old['mobile']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <input type="email" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($old['email']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- City -->
            <div class="form-group">
                <label for="city">City</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <select id="city" name="city" required>
                        <option value="">Select your city</option>
                        <?php
                        $cities = ["Udhwa", "Rajmahal", "Sahibganj", "Barharwa", "Dumka", "Jamshedpur", "Other"];
                        foreach ($cities as $c) {
                            $selected = ($old['city'] === $c) ? 'selected' : '';
                            echo "<option value=\"$c\" $selected>$c</option>";
                        }
                        ?>
                    </select>
                    <span class="select-arrow">▼</span>
                </div>
            </div>

            <!-- Passwords -->
            <div class="grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Min. 8 chars" minlength="8" required>
                        <button type="button" class="toggle-btn" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" minlength="8" required>
                        <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')" aria-label="Toggle password visibility">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="terms-row">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</label>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <p class="footer-note">
            Already have an account? <a href="login.php">Log In</a>
        </p>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>