<?php
session_start();
include "db.php";

/* =========================================
   LOGIN CHECK
========================================= */
if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$success = "";
$error   = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================
   UPDATE PROFILE
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = "Session expired or invalid token. Please refresh and try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? "");
        $mobile    = trim($_POST['mobile'] ?? "");
        $email     = trim($_POST['email'] ?? "");
        $city      = trim($_POST['city'] ?? "");

        if ($full_name === "" || $mobile === "") {
            $error = "Full name and mobile number are required.";
        } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $error = "Please enter a valid 10-digit mobile number.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            $sql = "SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $error = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("si", $mobile, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = "This mobile number is already linked to another account.";
                }
                $stmt->close();
            }

            if ($error === "") {
                $sql = "UPDATE users SET full_name = ?, mobile = ?, email = ?, city = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssi", $full_name, $mobile, $email, $city, $user_id);

                    if ($stmt->execute()) {
                        $_SESSION['user']        = $full_name;
                        $_SESSION['user_name']   = $full_name;
                        $_SESSION['user_mobile'] = $mobile;
                        $_SESSION['user_email']  = $email;
                        $_SESSION['user_city']   = $city;

                        $success = "Profile updated successfully!";
                    } else {
                        $error = "Unable to save your details.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

/* =========================================
   GET CURRENT USER
========================================= */
$user = [];
$sql  = "SELECT id, full_name, mobile, email, city, profile_photo, status FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}
$stmt->close();

/* =========================================
   STATISTICS
========================================= */
$total_orders     = 0;
$active_orders    = 0;
$completed_orders = 0;

$sql = "SELECT
            COUNT(*) AS total,
            SUM(booking_status IN ('pending', 'provider_notified', 'accepted', 'rescheduled', 'on_the_way', 'work_started')) AS active,
            SUM(booking_status = 'completed') AS completed
        FROM bookings
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    $total_orders     = (int)($stats['total'] ?? 0);
    $active_orders    = (int)($stats['active'] ?? 0);
    $completed_orders = (int)($stats['completed'] ?? 0);
    $stmt->close();
}

$profile_photo = "";
if (!empty($user['profile_photo'])) {
    $photo_file = "uploads/profile/" . $user['profile_photo'];
    if (file_exists(__DIR__ . "/uploads/profile/" . $user['profile_photo'])) {
        $profile_photo = $photo_file;
    }
}

$is_active = strtolower($user['status'] ?? 'active') === 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings &bull;</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.25);
            --primary-dark: #4338ca;
            --primary-light: #eef2ff;
            --bg-base: #f8fafc;
            --surface: #ffffff;
            --text-title: #0f172a;
            --text-body: #334155;
            --text-subtle: #64748b;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --radius-xl: 20px;
            --radius-lg: 14px;
            --radius-md: 10px;
            --shadow-subtle: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            --shadow-float: 0 16px 32px -8px rgba(15, 23, 42, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-body);
            min-height: 100vh;
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.06) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(14, 165, 233, 0.06) 0px, transparent 50%);
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }

        /* Glassmorphism Header */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 10px var(--primary-glow);
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.03em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-subtle);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--text-title);
            background: #f1f5f9;
        }

        .nav-link.logout {
            color: #ef4444;
        }

        .nav-link.logout:hover {
            background: #fef2f2;
        }

        /* Container Layout */
        .wrapper {
            max-width: 1200px;
            margin: 40px auto 60px;
            padding: 0 28px;
        }

        .dashboard-header {
            margin-bottom: 32px;
        }

        .dashboard-header h1 {
            font-size: 30px;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.03em;
        }

        .dashboard-header p {
            font-size: 15px;
            color: var(--text-subtle);
            margin-top: 4px;
        }

        /* Notifications */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-lg);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Grid Setup */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 28px;
            align-items: start;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-subtle);
            overflow: hidden;
            position: relative;
        }

        /* Profile Left Card */
        .profile-card {
            padding: 32px 24px;
            text-align: center;
        }

        .avatar-container {
            width: 104px;
            height: 104px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 5px #fff, 0 8px 18px rgba(79, 70, 229, 0.15);
            position: relative;
            overflow: hidden;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-text {
            font-size: 38px;
            font-weight: 800;
            color: var(--primary);
        }

        .profile-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-title);
        }

        .profile-card .meta {
            font-size: 14px;
            color: var(--text-subtle);
            margin-top: 4px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 14px;
            letter-spacing: 0.02em;
        }

        .status-pill.active {
            background: #dcfce7;
            color: #166534;
        }

        .status-pill.inactive {
            background: #f1f5f9;
            color: #475569;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
        }

        .quick-metrics-stack {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .metric-row span:first-child {
            color: var(--text-subtle);
        }

        .metric-row span:last-child {
            font-weight: 700;
            color: var(--text-title);
        }

        /* Stats Cards */
        .stats-deck {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px 20px;
            position: relative;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-subtle);
        }

        .stat-box:hover {
            transform: translateY(-2px);
            border-color: var(--border-hover);
            box-shadow: var(--shadow-float);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-amber { background: #fffbeb; color: #d97706; }
        .icon-emerald { background: #ecfdf5; color: #059669; }

        .stat-heading {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-subtle);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 16px;
        }

        .stat-digit {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-title);
            line-height: 1.1;
            margin-top: 4px;
        }

        /* Form Styling */
        .main-form-card {
            padding: 28px 32px;
            margin-bottom: 28px;
        }

        .section-header {
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-title);
        }

        .section-header p {
            font-size: 13px;
            color: var(--text-subtle);
            margin-top: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-body);
        }

        .input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrap svg {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            height: 46px;
            padding: 0 16px 0 42px;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--text-title);
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-control:focus + svg {
            color: var(--primary);
        }

        .btn-glow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 46px;
            padding: 0 28px;
            margin-top: 24px;
            border: none;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px var(--primary-glow);
            transition: all 0.2s ease;
        }

        .btn-glow:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
        }

        .btn-glow:active {
            transform: translateY(0);
        }

        /* Quick Portals */
        .portals-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .portal-card {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .portal-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .portal-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .portal-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-title);
        }

        .portal-sub {
            font-size: 13px;
            color: var(--text-subtle);
            margin-top: 2px;
        }

        .portal-arrow {
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .portal-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-float);
        }

        .portal-card:hover .portal-icon {
            background: var(--primary);
            color: #fff;
        }

        .portal-card:hover .portal-arrow {
            color: var(--primary);
            transform: translateX(4px);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .stats-deck,
            .form-grid,
            .portals-grid {
                grid-template-columns: 1fr;
            }
            .wrapper {
                padding: 0 16px;
                margin-top: 20px;
            }
            .main-form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <header class="navbar">
        <div class="nav-inner">
            <a href="dashboard.php" class="logo-wrap">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <span class="brand-name">Gofix</span>
            </a>

            <nav class="nav-links">
                <a href="dashboard.php" class="nav-link">Home</a>
                <a href="orders.php" class="nav-link">My Bookings</a>
                <a href="logout.php" class="nav-link logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="wrapper">
        <div class="dashboard-header">
            <h1>Account Overview</h1>
            <p>Review your booking volume and update your contact credentials</p>
        </div>

        <!-- System Alerts -->
        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Left Info Panel -->
            <aside>
                <div class="card profile-card">
                    <div class="avatar-container">
                        <?php if ($profile_photo !== ""): ?>
                            <img src="<?= htmlspecialchars($profile_photo); ?>" alt="Avatar">
                        <?php else: ?>
                            <span class="avatar-text"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h3><?= htmlspecialchars($user['full_name'] ?? 'User'); ?></h3>
                    <div class="meta"><?= htmlspecialchars($user['mobile'] ?? ''); ?></div>

                    <div>
                        <span class="status-pill <?= $is_active ? 'active' : 'inactive'; ?>">
                            <span class="pulse-dot"></span>
                            <?= htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?> Account
                        </span>
                    </div>

                    <div class="quick-metrics-stack">
                        <div class="metric-row">
                            <span>Account ID</span>
                            <span>#GFX-<?= str_pad((string)$user_id, 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>City</span>
                            <span><?= !empty($user['city']) ? htmlspecialchars($user['city']) : 'Not Specified'; ?></span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Panel -->
            <section>
                <!-- Numeric Statistics Deck -->
                <div class="stats-deck">
                    <div class="stat-box">
                        <div class="stat-top">
                            <div class="stat-icon icon-blue">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </div>
                        </div>
                        <div class="stat-heading">Total Bookings</div>
                        <div class="stat-digit"><?= $total_orders; ?></div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-top">
                            <div class="stat-icon icon-amber">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                        </div>
                        <div class="stat-heading">In Progress</div>
                        <div class="stat-digit"><?= $active_orders; ?></div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-top">
                            <div class="stat-icon icon-emerald">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                        </div>
                        <div class="stat-heading">Completed</div>
                        <div class="stat-digit"><?= $completed_orders; ?></div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="card main-form-card">
                    <div class="section-header">
                        <h2>Personal Details</h2>
                        <p>Keep your contact information updated to get booking notifications</p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-grid">
                            <div class="field-group">
                                <label for="full_name">Full Name</label>
                                <div class="input-icon-wrap">
                                    <input class="form-control" id="full_name" type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" required placeholder="John Doe">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="mobile">Mobile Number</label>
                                <div class="input-icon-wrap">
                                    <input class="form-control" id="mobile" type="tel" name="mobile" maxlength="10" inputmode="numeric" value="<?= htmlspecialchars($user['mobile'] ?? ''); ?>" required placeholder="10-digit number">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="email">Email Address</label>
                                <div class="input-icon-wrap">
                                    <input class="form-control" id="email" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" placeholder="name@example.com">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="city">City</label>
                                <div class="input-icon-wrap">
                                    <input class="form-control" id="city" type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Your city">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-glow">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Action Portals -->
                <div class="portals-grid">
                    <a href="orders.php" class="portal-card">
                        <div class="portal-left">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                            </div>
                            <div>
                                <div class="portal-title">My Orders</div>
                                <div class="portal-sub">Track active tasks & past history</div>
                            </div>
                        </div>
                        <svg class="portal-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>

                    <a href="dashboard.php" class="portal-card">
                        <div class="portal-left">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>
                            <div>
                                <div class="portal-title">Book a Service</div>
                                <div class="portal-sub">Hire trusted professionals</div>
                            </div>
                        </div>
                        <svg class="portal-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            </section>
        </div>
    </main>

</body>
</html>