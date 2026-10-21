<?php

session_start();

include 'db.php';

date_default_timezone_set('Asia/Kolkata');

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) &&
    !isset($_SESSION['user']) &&
    !isset($_SESSION['email'])
) {
    header("Location: login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| USER ID + SERVICE ID
|--------------------------------------------------------------------------
*/

$user_id = (int)($_SESSION['user_id'] ?? 0);

$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($service_id <= 0) {
    die("Invalid service selected.");
}

/*
|--------------------------------------------------------------------------
| SERVICE DETAILS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT * FROM services WHERE id = ? AND status = 1 LIMIT 1"
);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Service not found.");
}

$service = $result->fetch_assoc();
$stmt->close();

/*
|--------------------------------------------------------------------------
| USER DETAILS (used to prefill the form)
|--------------------------------------------------------------------------
*/

$customer_name = "";
$mobile = "";
$email = "";

if ($user_id > 0) {

    $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");

    if ($user_stmt) {

        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $customer_name = $user_data['full_name'] ?? $user_data['name'] ?? "";
            $mobile = $user_data['mobile'] ?? "";
            $email = $user_data['email'] ?? "";
        }

        $user_stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| PERSONALISATION (based on the user id)
|--------------------------------------------------------------------------
| Every user gets their own accent colour, chosen from the user id, plus
| their name and initials in the header, splash and sidebar.
*/

$palettes = [
    ['#1264d6', '#0b4fae', '#e8f1fe'], // blue
    ['#0f766e', '#0b5a54', '#e2f4f2'], // teal
    ['#6d3fd1', '#5529ad', '#f0eafc'], // violet
    ['#c02659', '#9b1c47', '#fde8f0'], // rose
    ['#237a3b', '#185c2b', '#e5f4e9'], // green
    ['#a15c07', '#7d4605', '#fdf0da'], // amber
];

$palette = $palettes[$user_id % count($palettes)];

$display_name = trim($customer_name) !== "" ? trim($customer_name) : "there";
$first_name = explode(" ", $display_name)[0];

$initials = "";
foreach (array_slice(preg_split('/\s+/', $display_name), 0, 2) as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
}
if ($initials === "") {
    $initials = "U";
}

/*
|--------------------------------------------------------------------------
| FORM SUBMIT
|--------------------------------------------------------------------------
*/

$time_slots = [
    "09:00 AM" => 9,  "10:00 AM" => 10, "11:00 AM" => 11, "12:00 PM" => 12,
    "01:00 PM" => 13, "02:00 PM" => 14, "03:00 PM" => 15, "04:00 PM" => 16,
    "05:00 PM" => 17, "06:00 PM" => 18, "07:00 PM" => 19, "08:00 PM" => 20,
];

$alternate_mobile = $address = $village = $area = $city = "";
$district = $pincode = $landmark = $booking_date = $booking_time = "";
$problem_description = $special_instruction = "";

$error = "";
$success = false;
$booking_summary = null;
$notified_provider_count = 0;

$redirect_url = "dashboard.php";
$redirect_seconds = 10;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $customer_name = trim($_POST['customer_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alternate_mobile = trim($_POST['alternate_mobile'] ?? '');

    $address = trim($_POST['address'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');

    $booking_date = trim($_POST['booking_date'] ?? '');
    $booking_time = trim($_POST['booking_time'] ?? '');

    $problem_description = trim($_POST['problem_description'] ?? '');
    $special_instruction = trim($_POST['special_instruction'] ?? '');

    $price = (float)$service['price'];
    $payment_mode = "COD";

    /* VALIDATION */

    if ($customer_name === "") {
        $error = "Please enter your name.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Please enter a valid 10 digit mobile number.";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address, or leave it empty.";
    } elseif ($alternate_mobile !== "" && !preg_match('/^[0-9]{10}$/', $alternate_mobile)) {
        $error = "Alternate mobile must be 10 digits, or leave it empty.";
    } elseif ($address === "") {
        $error = "Please enter your complete address.";
    } elseif ($village === "") {
        $error = "Please enter village/locality.";
    } elseif ($area === "") {
        $error = "Please enter your area.";
    } elseif ($city === "") {
        $error = "Please enter city.";
    } elseif ($district === "") {
        $error = "Please enter district.";
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $error = "Please enter a valid 6 digit PIN code.";
    } elseif ($booking_date === "") {
        $error = "Please select booking date.";
    } elseif (!isset($time_slots[$booking_time])) {
        $error = "Please select a booking time.";
    }

    if ($error === "") {

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
            $error = "Please select a valid booking date.";
        } elseif ($booking_date < date('Y-m-d')) {
            $error = "Please select today or a future date.";
        } elseif (
            $booking_date === date('Y-m-d') &&
            $time_slots[$booking_time] <= (int)date('G')
        ) {
            $error = "That time slot has already passed today. Please pick a later slot.";
        }
    }

    /* CREATE BOOKING */

    if ($error === "") {

        $booking_id = "BOOK-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -6));
        $booking_status = "pending";
        $payment_status = "pending";

        $insert_sql = "INSERT INTO bookings (
            booking_id, user_id, provider_id, service_id,
            customer_name, mobile, email, alternate_mobile,
            address, village, area, city, district, pincode, landmark,
            booking_date, booking_time,
            problem_description, special_instruction,
            price,
            payment_mode, payment_status, booking_status
        ) VALUES (
            ?, ?, NULL, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?,
            ?,
            ?, ?, ?
        )";

        $insert_stmt = $conn->prepare($insert_sql);

        if (!$insert_stmt) {

            $error = "Booking SQL Error: " . $conn->error;

        } else {

            // 22 placeholders: s i i | 15 x s | d (price) | s s s
            $insert_stmt->bind_param(
                "siisssssssssssssssdsss",
                $booking_id,
                $user_id,
                $service_id,
                $customer_name,
                $mobile,
                $email,
                $alternate_mobile,
                $address,
                $village,
                $area,
                $city,
                $district,
                $pincode,
                $landmark,
                $booking_date,
                $booking_time,
                $problem_description,
                $special_instruction,
                $price,
                $payment_mode,
                $payment_status,
                $booking_status
            );

            if ($insert_stmt->execute()) {

                $booking_db_id = $conn->insert_id;

                /* STATUS HISTORY */

                $history_stmt = $conn->prepare(
                    "INSERT INTO booking_status_history (
                        booking_id, old_status, new_status,
                        changed_by, changed_by_id, note
                    ) VALUES (
                        ?, NULL, 'pending', 'customer', ?, 'Booking created by customer'
                    )"
                );

                if ($history_stmt) {
                    $history_stmt->bind_param("ii", $booking_db_id, $user_id);
                    $history_stmt->execute();
                    $history_stmt->close();
                }

                /* PROVIDER NOTIFICATIONS */

                // Hinglish: Service aur location match karne wale sabhi active providers ko request bhejo.
                $provider_stmt = $conn->prepare(
                    "SELECT id FROM service_providers
                     WHERE status = 'active'
                     AND (
                        LOWER(TRIM(COALESCE(area, ''))) = LOWER(TRIM(?))
                        OR LOWER(TRIM(COALESCE(village, ''))) = LOWER(TRIM(?))
                        OR LOWER(TRIM(COALESCE(city, ''))) = LOWER(TRIM(?))
                        OR TRIM(COALESCE(pincode, '')) = TRIM(?)
                     )
                     AND FIND_IN_SET(
                        LOWER(TRIM(?)),
                        LOWER(REPLACE(COALESCE(service_categories, ''), ', ', ','))
                     ) > 0"
                );

                if ($provider_stmt) {

                    // Appliance service ka catalogue label registration label se alag hai.
                    $provider_category = trim($service['category']);
                    if (strcasecmp($provider_category, 'Appliance') === 0) {
                        $provider_category = 'Appliance Repair';
                    }

                    $provider_stmt->bind_param(
                        "sssss",
                        $area,
                        $village,
                        $city,
                        $pincode,
                        $provider_category
                    );
                    $provider_stmt->execute();
                    $provider_result = $provider_stmt->get_result();

                    while ($provider = $provider_result->fetch_assoc()) {

                        $provider_id = (int)$provider['id'];

                        $message = "New " . $service['service_name'] .
                            " booking available in " . $area .
                            ". Booking ID: " . $booking_id;

                        $notification_stmt = $conn->prepare(
                            "INSERT INTO provider_notifications (
                                provider_id, booking_id, notification_type, message, is_read
                            ) VALUES (?, ?, 'new_booking', ?, 0)"
                        );

                        if ($notification_stmt) {
                            $notification_stmt->bind_param("iis", $provider_id, $booking_db_id, $message);
                            if ($notification_stmt->execute() && $notification_stmt->affected_rows > 0) {
                                $notified_provider_count++;
                            }
                            $notification_stmt->close();
                        }
                    }

                    $provider_stmt->close();
                }

                /* PAYMENT RECORD */

                $payment_stmt = $conn->prepare(
                    "INSERT INTO payments (
                        booking_id, payment_method, amount, payment_status
                    ) VALUES (?, 'COD', ?, 'pending')"
                );

                if ($payment_stmt) {
                    $payment_stmt->bind_param("id", $booking_db_id, $price);
                    $payment_stmt->execute();
                    $payment_stmt->close();
                }

                /* SUCCESS SUMMARY */

                $success = true;

                $booking_summary = [
                    'booking_id'          => $booking_id,
                    'service_name'        => $service['service_name'],
                    'category'            => $service['category'],
                    'price'               => $price,
                    'customer_name'       => $customer_name,
                    'mobile'              => $mobile,
                    'email'               => $email,
                    'alternate_mobile'    => $alternate_mobile,
                    'address'             => $address,
                    'village'             => $village,
                    'area'                => $area,
                    'city'                => $city,
                    'district'            => $district,
                    'pincode'             => $pincode,
                    'landmark'            => $landmark,
                    'booking_date'        => $booking_date,
                    'booking_time'        => $booking_time,
                    'problem_description' => $problem_description,
                    'special_instruction' => $special_instruction,
                    'payment_status'      => $payment_status,
                    'booking_status'      => $booking_status,
                    'notified_provider_count' => $notified_provider_count,
                ];

            } else {
                $error = "Booking failed: " . $insert_stmt->error;
            }

            $insert_stmt->close();
        }
    }
}

// Splash only on the first visit, not after a form submit
$show_splash = ($_SERVER["REQUEST_METHOD"] !== "POST");

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <title><?php echo $success ? "Booking confirmed" : "Book " . e($service['service_name']); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        :root {
            --brand: <?php echo $palette[0]; ?>;
            --brand-dark: <?php echo $palette[1]; ?>;
            --brand-soft: <?php echo $palette[2]; ?>;
            --ink: #14202e;
            --muted: #607086;
            --line: #dbe3ec;
            --bg: #f4f6fa;
            --card: #ffffff;
            --danger: #b3261e;
            --danger-bg: #fdeceb;
            --ok: #167a3d;
            --ok-bg: #e8f6ed;
            --radius: 12px;
        }

        * { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            font-family: 'Manrope', 'Segoe UI', Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
            overflow-x: hidden;
            min-height: 100vh;
        }

        :focus-visible {
            outline: 3px solid var(--brand);
            outline-offset: 2px;
        }

        /* ---------------------------------------------------------- */
        /* SPLASH — the one intro moment, ~1 second                    */
        /* Fades out by CSS alone, so it never gets stuck without JS.  */
        /* ---------------------------------------------------------- */

        #splash {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            background: #ffffff;
            animation: splashOut .4s ease .8s forwards;
            pointer-events: none;
        }

        .splash-logo {
            font-size: 44px;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--ink);
            animation: splashPop .55s cubic-bezier(.2, 1.1, .4, 1) both;
        }

        .splash-logo span { color: var(--brand); }

        .splash-bar {
            width: 110px;
            height: 3px;
            border-radius: 3px;
            background: var(--line);
            overflow: hidden;
        }

        .splash-bar::after {
            content: "";
            display: block;
            height: 100%;
            background: var(--brand);
            transform-origin: left;
            animation: splashBar .8s ease-out both;
        }

        @keyframes splashPop {
            from { transform: scale(.85); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }

        @keyframes splashBar {
            from { transform: scaleX(0); }
            to   { transform: scaleX(1); }
        }

        @keyframes splashOut {
            to { opacity: 0; visibility: hidden; }
        }

        /* ---------------------------------------------------------- */
        /* TOP BAR                                                     */
        /* ---------------------------------------------------------- */

        .topbar {
            background: var(--card);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
        }

        .topbar-inner {
            max-width: 1080px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .back-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            margin-left: -12px;
            border-radius: 8px;
            min-height: 40px;
            transition: background .15s, color .15s;
            -webkit-tap-highlight-color: transparent;
        }

        .back-link:hover,
        .back-link:active {
            color: var(--brand);
            background: var(--brand-soft);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* ---------------------------------------------------------- */
        /* LAYOUT                                                      */
        /* ---------------------------------------------------------- */

        .shell {
            max-width: 1080px;
            margin: 28px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            align-items: start;
        }

        .sidebar {
            position: sticky;
            top: 60px;
            display: grid;
            gap: 16px;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            word-break: break-word;
        }

        .service-name {
            margin: 0 0 6px;
            font-size: 19px;
            line-height: 1.35;
        }

        .tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .service-desc {
            margin: 0 0 14px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .price-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            border-top: 1px solid var(--line);
            padding-top: 14px;
        }

        .price-row span { color: var(--muted); font-size: 13px; }

        .price-row strong {
            font-size: 24px;
            color: var(--brand-dark);
        }

        .steps {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
            font-size: 14px;
        }

        .steps li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .steps b {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 12px;
            background: var(--brand-soft);
            color: var(--brand-dark);
        }

        .panel h2.side-title {
            margin: 0 0 14px;
            font-size: 15px;
        }

        /* ---------------------------------------------------------- */
        /* FORM                                                        */
        /* ---------------------------------------------------------- */

        .form-panel { padding: 28px; }

        .form-panel > h2 {
            margin: 0 0 4px;
            font-size: 24px;
        }

        .form-panel > .lead {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 14px;
        }

        fieldset {
            border: 0;
            padding: 0;
            margin: 0 0 8px;
        }

        legend {
            width: 100%;
            padding: 18px 0 12px;
            margin-top: 8px;
            border-top: 1px solid var(--line);
            font-size: 16px;
            font-weight: 800;
        }

        fieldset:first-of-type legend {
            border-top: 0;
            margin-top: 0;
            padding-top: 0;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #33415a;
        }

        .req { color: var(--danger); }

        .field input,
        .field textarea {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #fff;
            font: inherit;
            font-size: 15px;
            color: var(--ink);
            transition: border-color .15s, box-shadow .15s;
            -webkit-appearance: none;
            appearance: none;
        }

        .field input[type="date"] {
            min-height: 48px;
            background-color: #fff;
            line-height: normal;
        }

        .field input[type="date"]::-webkit-date-and-time-value {
            text-align: left;
            min-height: 1.4em;
        }

        .field textarea {
            height: 92px;
            resize: vertical;
            min-height: 70px;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-soft);
        }

        .hint {
            margin: 6px 0 0;
            font-size: 12px;
            color: var(--muted);
        }

        /* time slots */

        .slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .slot {
            margin: 0;
            display: block;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .slot input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .slot span {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 11px 6px;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            background: #fff;
            transition: background .12s, border-color .12s, color .12s, transform .1s;
            user-select: none;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .slot span:hover { border-color: var(--brand); }

        .slot:active span {
            transform: scale(0.97);
        }

        .slot input:checked + span {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .slot input:focus-visible + span {
            outline: 3px solid var(--brand);
            outline-offset: 2px;
        }

        .slot input:disabled + span {
            background: #f0f2f6;
            color: #a3adbc;
            text-decoration: line-through;
            cursor: not-allowed;
            border-color: var(--line);
        }

        .cod-box {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border: 1px solid var(--brand);
            background: var(--brand-soft);
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .cod-box small { color: var(--muted); }

        .error-message {
            background: var(--danger-bg);
            border: 1px solid #f4c2be;
            color: var(--danger);
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 15px;
            min-height: 50px;
            background: var(--brand);
            color: #fff;
            font: inherit;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }

        .submit-btn:hover { background: var(--brand-dark); }
        .submit-btn:active { transform: scale(0.99); }
        .submit-btn:disabled { opacity: .7; cursor: wait; }

        /* ---------------------------------------------------------- */
        /* CONFIRMATION                                                */
        /* ---------------------------------------------------------- */

        .confirm-shell {
            max-width: 760px;
            margin: 28px auto 60px;
            padding: 0 20px;
        }

        .confirm-head {
            text-align: center;
            padding: 30px 22px;
        }

        .check {
            width: 68px;
            height: 68px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: var(--ok-bg);
            display: grid;
            place-items: center;
        }

        .check svg { width: 34px; height: 34px; }

        .check path {
            stroke-dasharray: 26;
            stroke-dashoffset: 26;
            animation: draw .5s ease .1s forwards;
        }

        @keyframes draw { to { stroke-dashoffset: 0; } }

        .confirm-head h1 { margin: 0 0 6px; font-size: 26px; }

        .confirm-head p { margin: 0 auto 18px; color: var(--muted); font-size: 14px; max-width: 460px; }

        .id-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            padding: 8px 8px 8px 18px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: .3px;
            max-width: 100%;
            box-sizing: border-box;
        }

        .copy-btn {
            border: 0;
            background: var(--brand);
            color: #fff;
            border-radius: 999px;
            padding: 8px 16px;
            min-height: 36px;
            font: inherit;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s, transform .1s;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }

        .copy-btn:hover { background: var(--brand-dark); }
        .copy-btn:active { transform: scale(0.96); }

        .summary { display: grid; gap: 14px; margin-top: 16px; }

        .summary .panel { padding: 18px 20px; }

        .summary h3 {
            margin: 0 0 12px;
            font-size: 15px;
            color: var(--brand-dark);
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
        }

        .item .label { font-size: 12px; color: var(--muted); }

        .item .value { font-size: 14px; font-weight: 600; word-break: break-word; }

        .item.full { grid-column: 1 / -1; }

        .status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: #fff4d6;
            color: #8a5a00;
            font-size: 12px;
            font-weight: 700;
        }

        .redirect {
            margin-top: 16px;
            text-align: center;
        }

        .redirect p { margin: 0 0 10px; font-size: 14px; color: var(--muted); }

        .redirect strong { color: var(--brand-dark); }

        .progress {
            height: 4px;
            border-radius: 4px;
            background: var(--line);
            overflow: hidden;
            margin-bottom: 16px;
        }

        .progress::after {
            content: "";
            display: block;
            height: 100%;
            background: var(--brand);
            transform-origin: left;
            animation: fill <?php echo (int)$redirect_seconds; ?>s linear forwards;
        }

        @keyframes fill {
            from { transform: scaleX(0); }
            to   { transform: scaleX(1); }
        }

        .actions {
            display: grid;
            gap: 10px;
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .btn-primary,
        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 13px 18px;
            min-height: 48px;
            border-radius: 10px;
            font: inherit;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
            transition: background .15s, transform .1s;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary { background: var(--brand); color: #fff; border: 0; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-primary:active { transform: scale(0.99); }
        .btn-secondary { background: #fff; color: var(--brand-dark); border: 1px solid var(--line); }
        .btn-secondary:hover { background: var(--brand-soft); }
        .btn-secondary:active { transform: scale(0.99); }

        /* ---------------------------------------------------------- */
        /* RESPONSIVE MEDIA QUERIES                                   */
        /* ---------------------------------------------------------- */

        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
                margin: 20px auto 44px;
                padding: 0 16px;
                gap: 18px;
            }

            .sidebar {
                position: static;
                gap: 14px;
            }

            .hide-mobile {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .topbar-inner {
                padding: 10px 16px;
            }

            /* Prevents iOS Safari from auto-zooming into inputs when tapped */
            .field input,
            .field textarea {
                font-size: 16px;
            }

            .slots {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
        }

        @media (max-width: 640px) {
            .shell {
                margin: 14px auto 36px;
                padding: 0 14px;
                padding-left: max(14px, env(safe-area-inset-left));
                padding-right: max(14px, env(safe-area-inset-right));
                gap: 14px;
            }

            .panel {
                padding: 16px 14px;
                border-radius: 10px;
            }

            .form-panel {
                padding: 18px 14px;
            }

            .form-panel > h2 {
                font-size: 21px;
            }

            .form-panel > .lead {
                margin-bottom: 16px;
                font-size: 13.5px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .grid2 {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .sidebar .panel {
                padding: 14px 14px;
            }

            .service-name {
                font-size: 18px;
            }

            .price-row strong {
                font-size: 21px;
            }

            legend {
                padding: 14px 0 8px;
                font-size: 15px;
            }

            .cod-box {
                padding: 12px 14px;
                font-size: 13.5px;
            }

            .submit-btn {
                padding: 14px;
                font-size: 16px;
            }

            /* Confirmation screen mobile adjustments */
            .confirm-shell {
                margin: 14px auto 36px;
                padding: 0 14px;
                padding-left: max(14px, env(safe-area-inset-left));
                padding-right: max(14px, env(safe-area-inset-right));
            }

            .confirm-head {
                padding: 24px 14px;
            }

            .confirm-head h1 {
                font-size: 22px;
            }

            .confirm-head p {
                font-size: 13.5px;
            }

            .check {
                width: 58px;
                height: 58px;
                margin-bottom: 12px;
            }

            .check svg {
                width: 28px;
                height: 28px;
            }

            .summary .panel {
                padding: 16px 14px;
            }

            .user-chip .name {
                display: none;
            }
        }

        @media (max-width: 460px) {
            .shell,
            .confirm-shell {
                padding: 0 12px;
                padding-left: max(12px, env(safe-area-inset-left));
                padding-right: max(12px, env(safe-area-inset-right));
            }

            .panel,
            .form-panel {
                padding: 15px 12px;
            }

            .slots {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .slot span {
                padding: 12px 8px;
                font-size: 13px;
                min-height: 44px;
            }

            .id-pill {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 12px 14px;
                border-radius: 12px;
                width: 100%;
                max-width: 100%;
                font-size: 15px;
            }

            .id-pill span {
                word-break: break-all;
                text-align: center;
            }

            .copy-btn {
                width: 100%;
                padding: 9px 16px;
                font-size: 13px;
                min-height: 38px;
            }

            .splash-logo {
                font-size: 36px;
            }
        }

        @media (max-width: 340px) {
            .shell,
            .confirm-shell {
                padding: 0 8px;
            }

            .panel,
            .form-panel {
                padding: 12px 10px;
            }

            .slot span {
                font-size: 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #splash { animation-duration: .01s; animation-delay: .2s; }
            .splash-logo, .splash-bar::after, .check path { animation: none; }
            .check path { stroke-dashoffset: 0; }
            html { scroll-behavior: auto; }
        }

    </style>

</head>

<body>

<?php if ($show_splash): ?>
<div id="splash" aria-hidden="true">
    <div class="splash-logo">Go<span>Fix</span></div>
    <div class="splash-bar"></div>
</div>
<script>
    // Remove the splash from the DOM once it has faded out.
    setTimeout(function () {
        var s = document.getElementById("splash");
        if (s) s.remove();
    }, 1400);
</script>
<?php endif; ?>

<header class="topbar">
    <div class="topbar-inner">
        <a class="back-link" href="more.php">&larr; All services</a>
        <!-- <div class="user-chip">
            <span class="name"><?php echo e($display_name); ?></span>
            <span class="avatar"><?php echo e($initials); ?></span>
        </div> -->
    </div>
</header>

<?php if ($success && $booking_summary): ?>

<!-- ================================================================ -->
<!-- CONFIRMATION                                                     -->
<!-- ================================================================ -->

<main class="confirm-shell">

    <section class="panel confirm-head">

        <div class="check">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 13l4 4L19 7" stroke="#167a3d" stroke-width="2.6"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1>Booking confirmed</h1>

        <p>
            Thank you, <?php echo e($booking_summary['customer_name']); ?>.
            <?php if ($booking_summary['notified_provider_count'] > 0): ?>
                <?php echo (int)$booking_summary['notified_provider_count']; ?> nearby provider(s) have been notified.
                The first provider to accept will get this job.
            <?php else: ?>
                Your request is saved. No active provider matched this area and service yet;
                the admin can assign a technician.
            <?php endif; ?>
        </p>

        <div class="id-pill">
            <span id="bookingId"><?php echo e($booking_summary['booking_id']); ?></span>
            <button type="button" class="copy-btn" id="copyBtn">Copy ID</button>
        </div>

    </section>

    <div class="summary">

        <section class="panel">
            <h3>Service</h3>
            <div class="grid2">
                <div class="item">
                    <div class="label">Service</div>
                    <div class="value"><?php echo e($booking_summary['service_name']); ?></div>
                </div>
                <div class="item">
                    <div class="label">Category</div>
                    <div class="value"><?php echo e($booking_summary['category']); ?></div>
                </div>
                <div class="item">
                    <div class="label">Estimated price</div>
                    <div class="value">₹<?php echo number_format($booking_summary['price'], 0); ?> onwards</div>
                </div>
                <div class="item">
                    <div class="label">Status</div>
                    <div class="value"><span class="status"><?php echo e(ucfirst($booking_summary['booking_status'])); ?></span></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <h3>Schedule</h3>
            <div class="grid2">
                <div class="item">
                    <div class="label">Date</div>
                    <div class="value"><?php echo e(date("d M Y", strtotime($booking_summary['booking_date']))); ?></div>
                </div>
                <div class="item">
                    <div class="label">Time</div>
                    <div class="value"><?php echo e($booking_summary['booking_time']); ?></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <h3>Contact</h3>
            <div class="grid2">
                <div class="item">
                    <div class="label">Name</div>
                    <div class="value"><?php echo e($booking_summary['customer_name']); ?></div>
                </div>
                <div class="item">
                    <div class="label">Mobile</div>
                    <div class="value"><?php echo e($booking_summary['mobile']); ?></div>
                </div>
                <?php if ($booking_summary['email'] !== ""): ?>
                <div class="item">
                    <div class="label">Email</div>
                    <div class="value"><?php echo e($booking_summary['email']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($booking_summary['alternate_mobile'] !== ""): ?>
                <div class="item">
                    <div class="label">Alternate mobile</div>
                    <div class="value"><?php echo e($booking_summary['alternate_mobile']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <h3>Service address</h3>
            <div class="grid2">
                <div class="item full">
                    <div class="label">Address</div>
                    <div class="value">
                        <?php echo e($booking_summary['address']); ?>,
                        <?php echo e($booking_summary['village']); ?>,
                        <?php echo e($booking_summary['area']); ?>
                    </div>
                </div>
                <div class="item">
                    <div class="label">City</div>
                    <div class="value"><?php echo e($booking_summary['city']); ?></div>
                </div>
                <div class="item">
                    <div class="label">District</div>
                    <div class="value"><?php echo e($booking_summary['district']); ?></div>
                </div>
                <div class="item">
                    <div class="label">PIN code</div>
                    <div class="value"><?php echo e($booking_summary['pincode']); ?></div>
                </div>
                <?php if ($booking_summary['landmark'] !== ""): ?>
                <div class="item">
                    <div class="label">Landmark</div>
                    <div class="value"><?php echo e($booking_summary['landmark']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($booking_summary['problem_description'] !== "" || $booking_summary['special_instruction'] !== ""): ?>
        <section class="panel">
            <h3>Notes</h3>
            <div class="grid2">
                <?php if ($booking_summary['problem_description'] !== ""): ?>
                <div class="item full">
                    <div class="label">Problem / requirement</div>
                    <div class="value"><?php echo nl2br(e($booking_summary['problem_description'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($booking_summary['special_instruction'] !== ""): ?>
                <div class="item full">
                    <div class="label">Special instructions</div>
                    <div class="value"><?php echo nl2br(e($booking_summary['special_instruction'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="panel">
            <h3>Payment</h3>
            <div class="grid2">
                <div class="item">
                    <div class="label">Mode</div>
                    <div class="value">Cash on service (COD)</div>
                </div>
                <div class="item">
                    <div class="label">Payment status</div>
                    <div class="value"><span class="status"><?php echo e(ucfirst($booking_summary['payment_status'])); ?></span></div>
                </div>
            </div>
        </section>

    </div>

    <div class="redirect">
        <div class="progress"></div>
        <p>
            Taking you to your dashboard in
            <strong id="countdown"><?php echo (int)$redirect_seconds; ?></strong>
            <span id="plural">seconds</span>
        </p>
        <div class="actions">
            <button type="button" class="btn-primary" onclick="goToDashboardNow()">Go to dashboard now</button>
            <a class="btn-secondary" href="more.php">Book another service</a>
        </div>
    </div>

</main>

<script>
(function () {

    var seconds = <?php echo (int)$redirect_seconds; ?>;
    var redirectUrl = <?php echo json_encode($redirect_url); ?>;
    var countdownEl = document.getElementById("countdown");
    var pluralEl = document.getElementById("plural");

    var timer = setInterval(function () {
        seconds -= 1;
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = redirectUrl;
            return;
        }
        countdownEl.textContent = seconds;
        pluralEl.textContent = seconds === 1 ? "second" : "seconds";
    }, 1000);

    window.goToDashboardNow = function () {
        clearInterval(timer);
        window.location.href = redirectUrl;
    };

    var copyBtn = document.getElementById("copyBtn");
    copyBtn.addEventListener("click", function () {
        var id = document.getElementById("bookingId").textContent.trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(id).then(function () {
                copyBtn.textContent = "Copied";
                setTimeout(function () { copyBtn.textContent = "Copy ID"; }, 1800);
            });
        }
    });

})();
</script>

<?php else: ?>

<!-- ================================================================ -->
<!-- BOOKING FORM                                                     -->
<!-- ================================================================ -->

<main class="shell">

    <aside class="sidebar">

        <section class="panel">
            <span class="tag"><?php echo e($service['category']); ?></span>
            <h2 class="service-name"><?php echo e($service['service_name']); ?></h2>
            <p class="service-desc"><?php echo e($service['description']); ?></p>
            <div class="price-row">
                <span>Estimated price</span>
                <strong>₹<?php echo number_format((float)$service['price'], 0); ?>+</strong>
            </div>
        </section>

        <section class="panel hide-mobile">
            <h2 class="side-title">What happens next</h2>
            <ol class="steps">
                <li><b>1</b><span>You confirm the booking.</span></li>
                <li><b>2</b><span>Providers in your area are notified.</span></li>
                <li><b>3</b><span>A provider accepts and visits at your time.</span></li>
                <li><b>4</b><span>You pay in cash after the service.</span></li>
            </ol>
        </section>

    </aside>

    <section class="panel form-panel">

        <h2>Book your service</h2>
        <p class="lead">Fields marked <span class="req">*</span> are required.</p>

        <?php if ($error !== ""): ?>
            <div class="error-message" id="formError" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="bookingForm" novalidate>

            <fieldset>
                <legend>Your details</legend>

                <div class="row">
                    <div class="field">
                        <label for="customer_name">Full name <span class="req">*</span></label>
                        <input type="text" id="customer_name" name="customer_name"
                               value="<?php echo e($customer_name); ?>"
                               placeholder="Your full name" autocomplete="name" required>
                    </div>
                    <div class="field">
                        <label for="mobile">Mobile number <span class="req">*</span></label>
                        <input type="tel" id="mobile" name="mobile"
                               value="<?php echo e($mobile); ?>"
                               inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                               placeholder="10 digit mobile number" autocomplete="tel" required>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo e($email); ?>"
                               placeholder="Optional" autocomplete="email">
                    </div>
                    <div class="field">
                        <label for="alternate_mobile">Alternate mobile</label>
                        <input type="tel" id="alternate_mobile" name="alternate_mobile"
                               value="<?php echo e($alternate_mobile); ?>"
                               inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                               placeholder="Optional">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Service address</legend>

                <div class="field">
                    <label for="address">Complete address <span class="req">*</span></label>
                    <textarea id="address" name="address"
                              placeholder="House number, street, building" required><?php echo e($address); ?></textarea>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="village">Village / locality <span class="req">*</span></label>
                        <input type="text" id="village" name="village"
                               value="<?php echo e($village); ?>" required>
                    </div>
                    <div class="field">
                        <label for="area">Area <span class="req">*</span></label>
                        <input type="text" id="area" name="area"
                               value="<?php echo e($area); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="city">City <span class="req">*</span></label>
                        <input type="text" id="city" name="city"
                               value="<?php echo e($city); ?>" required>
                    </div>
                    <div class="field">
                        <label for="district">District <span class="req">*</span></label>
                        <input type="text" id="district" name="district"
                               value="<?php echo e($district); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="pincode">PIN code <span class="req">*</span></label>
                        <input type="text" id="pincode" name="pincode"
                               value="<?php echo e($pincode); ?>"
                               inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                               placeholder="6 digit PIN" required>
                    </div>
                    <div class="field">
                        <label for="landmark">Landmark</label>
                        <input type="text" id="landmark" name="landmark"
                               value="<?php echo e($landmark); ?>"
                               placeholder="Near a school, shop, temple...">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Date and time</legend>

                <div class="field">
                    <label for="booking_date">Preferred date <span class="req">*</span></label>
                    <input type="date" id="booking_date" name="booking_date"
                           value="<?php echo e($booking_date); ?>" required>
                </div>

                <div class="field">
                    <label id="slotLabel">Preferred time <span class="req">*</span></label>
                    <div class="slots" role="radiogroup" aria-labelledby="slotLabel">
                        <?php foreach ($time_slots as $label => $h): ?>
                            <label class="slot">
                                <input type="radio" name="booking_time"
                                       value="<?php echo e($label); ?>"
                                       data-hour="<?php echo (int)$h; ?>"
                                       <?php echo $booking_time === $label ? "checked" : ""; ?>>
                                <span><?php echo e($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="hint">Slots that have already passed today are disabled.</p>
                </div>
            </fieldset>

            <fieldset>
                <legend>About the job</legend>

                <div class="field">
                    <label for="problem_description">Problem or requirement</label>
                    <textarea id="problem_description" name="problem_description"
                              placeholder="What do you need done, or what is not working?"><?php echo e($problem_description); ?></textarea>
                </div>

                <div class="field">
                    <label for="special_instruction">Special instructions</label>
                    <textarea id="special_instruction" name="special_instruction"
                              placeholder="Anything the provider should know before arriving"><?php echo e($special_instruction); ?></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Payment</legend>
                <div class="cod-box">
                    <span aria-hidden="true">&#8377;</span>
                    <div>
                        <strong>Cash on service (COD)</strong><br>
                        <small>Pay the provider directly after the work is done.</small>
                    </div>
                </div>
            </fieldset>

            <button type="submit" class="submit-btn" id="submitBtn">Confirm booking</button>

        </form>

    </section>

</main>

<script>
(function () {

    var dateEl = document.getElementById("booking_date");
    var slots = document.querySelectorAll('input[name="booking_time"]');
    var form = document.getElementById("bookingForm");

    // Date limits: today up to 60 days ahead (local time)
    function ymd(d) {
        var m = ("0" + (d.getMonth() + 1)).slice(-2);
        var day = ("0" + d.getDate()).slice(-2);
        return d.getFullYear() + "-" + m + "-" + day;
    }

    var now = new Date();
    var max = new Date();
    max.setDate(max.getDate() + 60);
    dateEl.min = ymd(now);
    dateEl.max = ymd(max);

    // Disable time slots that already passed when "today" is selected
    function refreshSlots() {
        var isToday = dateEl.value === ymd(new Date());
        var hourNow = new Date().getHours();

        slots.forEach(function (s) {
            var passed = isToday && parseInt(s.dataset.hour, 10) <= hourNow;
            s.disabled = passed;
            if (passed && s.checked) s.checked = false;
        });
    }

    dateEl.addEventListener("change", refreshSlots);
    refreshSlots();

    // Digits only for mobile and PIN fields
    ["mobile", "alternate_mobile", "pincode"].forEach(function (id) {
        var el = document.getElementById(id);
        el.addEventListener("input", function () {
            el.value = el.value.replace(/\D/g, "");
        });
    });

    // Require a time slot (radios can't use the native "required" message nicely)
    form.addEventListener("submit", function (ev) {

        if (!form.checkValidity()) {
            ev.preventDefault();
            form.reportValidity();
            return;
        }

        var picked = document.querySelector('input[name="booking_time"]:checked');
        if (!picked) {
            ev.preventDefault();
            alert("Please choose a time slot.");
            return;
        }

        var btn = document.getElementById("submitBtn");
        btn.disabled = true;
        btn.textContent = "Confirming...";
    });

    // Bring a server-side error into view
    var err = document.getElementById("formError");
    if (err) err.scrollIntoView({ block: "center" });

})();
</script>

<?php endif; ?>

</body>

</html>
