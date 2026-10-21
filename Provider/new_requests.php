
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/request_notifications.php';

$provider_id = (int) $_SESSION['provider_id'];
syncProviderBookingNotifications($conn, $provider_id);

$success = "";
$error = "";


/* -----------------------------------------
   POST ACTION
----------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";
    $booking_db_id = (int)($_POST['booking_id'] ?? 0);
    $notification_id = (int)($_POST['notification_id'] ?? 0);


    if ($booking_db_id <= 0 || $notification_id <= 0) {

        $error = "Invalid booking request.";

    } else {

        /* -----------------------------------------
           Get Notification + Booking
        ----------------------------------------- */

        $sql = "SELECT
                    pn.id AS notification_id,
                    pn.provider_id,
                    pn.booking_id,
                    pn.response_status,

                    b.*,

                    s.service_name,
                    s.category

                FROM provider_notifications pn

                INNER JOIN bookings b
                    ON pn.booking_id = b.id

                LEFT JOIN services s
                    ON b.service_id = s.id

                WHERE pn.id = ?
                AND pn.provider_id = ?
                AND pn.booking_id = ?
                AND pn.notification_type = 'new_booking'
                AND (b.provider_id IS NULL OR b.provider_id = pn.provider_id)

                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "iii",
                $notification_id,
                $provider_id,
                $booking_db_id
            );

            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows !== 1) {

                $error = "Booking request not found.";

            } else {

                $booking = $result->fetch_assoc();

                $old_status =
                    $booking['booking_status'];

                $response_status =
                    $booking['response_status'] ?? "pending";


                /* -----------------------------------------
                   ACCEPT
                ----------------------------------------- */

                if ($action === "accept") {

                    if ($response_status !== "pending") {

                        $error =
                            "You have already responded to this request.";

                    } elseif (
                        $old_status !== "pending" &&
                        $old_status !== "provider_notified"
                    ) {

                        $error =
                            "This booking is no longer available.";

                    } else {

                        /*
                         * Check whether another provider
                         * already accepted the booking.
                         */

                        $check = $conn->prepare(
                            "SELECT provider_id
                             FROM bookings
                             WHERE id = ?
                             LIMIT 1"
                        );

                        $check->bind_param(
                            "i",
                            $booking_db_id
                        );

                        $check->execute();

                        $check_result =
                            $check->get_result();

                        $current_booking =
                            $check_result->fetch_assoc();

                        $check->close();


                        if (
                            !empty($current_booking['provider_id']) &&
                            (int)$current_booking['provider_id'] !== $provider_id
                        ) {

                            $error =
                                "This booking has already been accepted by another provider.";

                        } else {

                            /*
                             * Assign this provider
                             */

                            $new_status = "accepted";


                            // Hinglish: Booking abhi khaali ho tabhi claim hoti hai; pehla accept jeetta hai.
                            $update = $conn->prepare(
                                "UPDATE bookings
                                 SET
                                    provider_id = ?,
                                    booking_status = ?
                                 WHERE id = ?
                                 AND
                                 (
                                    provider_id IS NULL
                                    OR provider_id = ?
                                 )
                                 AND booking_status IN
                                 ('pending','provider_notified')"
                            );


                            $update->bind_param(
                                "isii",
                                $provider_id,
                                $new_status,
                                $booking_db_id,
                                $provider_id
                            );


                            if ($update->execute() &&
                                $update->affected_rows > 0) {


                                /* ---------------------------------
                                   Update Provider Notification
                                --------------------------------- */

                                $notification = $conn->prepare(
                                    "UPDATE provider_notifications
                                     SET
                                        response_status = 'accepted',
                                        is_read = 1
                                     WHERE id = ?
                                     AND provider_id = ?"
                                );

                                $notification->bind_param(
                                    "ii",
                                    $notification_id,
                                    $provider_id
                                );

                                $notification->execute();

                                $notification->close();


                                /* ---------------------------------
                                   Reject Other Providers
                                --------------------------------- */

                                $other = $conn->prepare(
                                    "UPDATE provider_notifications
                                     SET
                                        response_status = 'rejected',
                                        is_read = 1
                                     WHERE booking_id = ?
                                     AND provider_id != ?
                                     AND notification_type = 'new_booking'
                                     AND response_status = 'pending'"
                                );

                                $other->bind_param(
                                    "ii",
                                    $booking_db_id,
                                    $provider_id
                                );

                                $other->execute();

                                $other->close();


                                /* ---------------------------------
                                   Status History
                                --------------------------------- */

                                $history = $conn->prepare(
                                    "INSERT INTO booking_status_history
                                    (
                                        booking_id,
                                        old_status,
                                        new_status,
                                        changed_by,
                                        changed_by_id,
                                        note
                                    )
                                    VALUES
                                    (?, ?, ?, 'provider', ?, ?)"
                                );


                                $note =
                                    "Booking accepted by service provider.";


                                $history->bind_param(
                                    "issis",
                                    $booking_db_id,
                                    $old_status,
                                    $new_status,
                                    $provider_id,
                                    $note
                                );

                                $history->execute();

                                $history->close();


                                $success =
                                    "Booking accepted successfully.";

                            } else {

                                $error =
                                    "This booking is no longer available.";
                            }

                            $update->close();
                        }
                    }
                }


                /* -----------------------------------------
                   REJECT
                ----------------------------------------- */

                elseif ($action === "reject") {

                    if ($response_status !== "pending") {

                        $error =
                            "You have already responded to this request.";

                    } else {

                        /*
                         * Only reject THIS provider's request.
                         * Do NOT reject the booking globally.
                         */

                        $notification = $conn->prepare(
                            "UPDATE provider_notifications
                             SET
                                response_status = 'rejected',
                                is_read = 1
                             WHERE id = ?
                             AND provider_id = ?
                             AND response_status = 'pending'"
                        );


                        $notification->bind_param(
                            "ii",
                            $notification_id,
                            $provider_id
                        );


                        if ($notification->execute() &&
                            $notification->affected_rows > 0) {


                            /* Status History */

                            $new_status = "rejected";

                            $history = $conn->prepare(
                                "INSERT INTO booking_status_history
                                (
                                    booking_id,
                                    old_status,
                                    new_status,
                                    changed_by,
                                    changed_by_id,
                                    note
                                )
                                VALUES
                                (?, ?, ?, 'provider', ?, ?)"
                            );


                            $note =
                                "Provider rejected this booking request.";


                            $history->bind_param(
                                "issis",
                                $booking_db_id,
                                $old_status,
                                $new_status,
                                $provider_id,
                                $note
                            );


                            $history->execute();

                            $history->close();


                            $success =
                                "Booking request rejected.";

                        } else {

                            $error =
                                "Unable to reject request.";
                        }

                        $notification->close();
                    }
                }


                /* -----------------------------------------
                   RESCHEDULE
                ----------------------------------------- */

                elseif ($action === "reschedule") {

                    $new_date =
                        trim(
                            $_POST['reschedule_date'] ?? ""
                        );

                    $new_time =
                        trim(
                            $_POST['reschedule_time'] ?? ""
                        );


                    if (
                        $new_date === "" ||
                        $new_time === ""
                    ) {

                        $error =
                            "Please select new date and time.";

                    } elseif (
                        $response_status !== "pending"
                    ) {

                        $error =
                            "You have already responded to this request.";

                    } else {


                        /*
                         * Save suggested reschedule
                         */

                        $new_status =
                            "rescheduled";


                        $update = $conn->prepare(
                            "UPDATE bookings

                             SET
                                booking_status = ?,
                                rescheduled_date = ?,
                                rescheduled_time = ?,
                                provider_id = ?

                             WHERE id = ?

                             AND
                             (
                                provider_id IS NULL
                                OR provider_id = ?
                             )

                             AND booking_status IN
                             ('pending','provider_notified')"
                        );


                        $update->bind_param(
                            "sssiii",
                            $new_status,
                            $new_date,
                            $new_time,
                            $provider_id,
                            $booking_db_id,
                            $provider_id
                        );


                        if ($update->execute() &&
                            $update->affected_rows > 0) {


                            /* Notification */

                            $notification = $conn->prepare(
                                "UPDATE provider_notifications
                                 SET
                                    response_status = 'rescheduled',
                                    is_read = 1
                                 WHERE id = ?
                                 AND provider_id = ?"
                            );


                            $notification->bind_param(
                                "ii",
                                $notification_id,
                                $provider_id
                            );


                            $notification->execute();

                            $notification->close();


                            /* History */

                            $history = $conn->prepare(
                                "INSERT INTO booking_status_history
                                (
                                    booking_id,
                                    old_status,
                                    new_status,
                                    changed_by,
                                    changed_by_id,
                                    note
                                )
                                VALUES
                                (?, ?, ?, 'provider', ?, ?)"
                            );


                            $note =
                                "Provider requested reschedule to " .
                                $new_date .
                                " at " .
                                $new_time . ".";


                            $history->bind_param(
                                "issis",
                                $booking_db_id,
                                $old_status,
                                $new_status,
                                $provider_id,
                                $note
                            );


                            $history->execute();

                            $history->close();


                            $success =
                                "Reschedule request submitted.";

                        } else {

                            $error =
                                "Unable to reschedule this booking.";
                        }


                        $update->close();
                    }
                }
            }

            $stmt->close();
        }
    }
}


/* -----------------------------------------
   GET NEW REQUESTS
----------------------------------------- */

$requests = [];


/*
 * IMPORTANT:
 *
 * Do NOT use:
 *
 * WHERE b.provider_id = ?
 *
 * because provider_id is NULL before
 * a provider accepts the request.
 *
 * Instead use provider_notifications.
 */

$sql = "SELECT

            pn.id AS notification_id,
            pn.provider_id AS notified_provider_id,
            pn.response_status,
            pn.is_read,
            pn.created_at AS notification_created_at,

            b.id,
            b.booking_id,
            b.user_id,
            b.provider_id,

            b.customer_name,
            b.mobile,
            b.email,
            b.alternate_mobile,

            b.address,
            b.village,
            b.area,
            b.city,
            b.district,
            b.pincode,
            b.landmark,

            b.booking_date,
            b.booking_time,

            b.problem_description,
            b.special_instruction,

            b.price,
            b.payment_mode,
            b.payment_status,
            b.booking_status,

            s.service_name,
            s.category,
            s.image AS service_image

        FROM provider_notifications pn

        INNER JOIN bookings b
            ON pn.booking_id = b.id

        LEFT JOIN services s
            ON b.service_id = s.id

        WHERE pn.provider_id = ?

        AND pn.notification_type = 'new_booking'

        AND pn.response_status = 'pending'

        AND b.booking_status IN
        ('pending', 'provider_notified')

        AND (b.provider_id IS NULL OR b.provider_id = pn.provider_id)

        ORDER BY pn.created_at DESC";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    $error =
        "Unable to load booking requests: " .
        $conn->error;

} else {

    $stmt->bind_param(
        "i",
        $provider_id
    );

    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $requests[] = $row;
    }


    $stmt->close();
}

$request_count = count($requests);
$request_latest_id = 0;
foreach ($requests as $request_row) {
    $request_latest_id = max(
        $request_latest_id,
        (int) ($request_row['notification_id'] ?? 0)
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>New Requests</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #222;
}


/* HEADER */

.header {
    height: 65px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 25px;

    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    color: #1264d6;
    font-size: 21px;
    font-weight: bold;
}

.provider-name {
    font-size: 14px;
    font-weight: bold;
}


/* LAYOUT */

.layout {
    display: flex;
    min-height: calc(100vh - 65px);
}


/* SIDEBAR */

.sidebar {
    width: 230px;
    background: #fff;
    border-right: 1px solid #e5e7eb;
    padding: 20px 12px;
}

.sidebar a {
    display: block;

    padding: 12px 14px;

    margin-bottom: 5px;

    border-radius: 8px;

    text-decoration: none;

    color: #444;

    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #eaf2ff;
    color: #1264d6;
    font-weight: bold;
}

.sidebar .logout {
    color: #d00000;
    margin-top: 25px;
}


/* MAIN */

.main {
    flex: 1;
    padding: 25px;
    max-width: 1300px;
}

.page-title {
    font-size: 25px;
    margin-bottom: 5px;
}

.page-subtitle {
    color: #777;
    font-size: 14px;
    margin-bottom: 25px;
}


/* MESSAGE */

.message {
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 14px;
}

.success {
    background: #e8f8ee;
    color: #15803d;
}

.error {
    background: #ffe8e8;
    color: #c00000;
}


/* REQUEST GRID */

.requests {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;
}


/* CARD */

.request-card {
    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 20px;

    box-shadow:
        0 3px 12px rgba(0,0,0,0.04);
}


/* CARD HEADER */

.card-header {
    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 15px;
}

.service-name {
    font-size: 18px;
    font-weight: bold;
}

.booking-id {
    color: #777;
    font-size: 12px;
    margin-top: 5px;
}

.badge {
    background: #fff3cd;
    color: #856404;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}


/* CUSTOMER */

.customer-box {
    background: #f8fafc;

    border-radius: 9px;

    padding: 14px;

    margin-bottom: 15px;
}

.customer-name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 8px;
}

.info {
    font-size: 13px;
    color: #555;

    margin-top: 5px;

    line-height: 1.5;
}


/* DETAILS */

.details {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 10px;

    margin-bottom: 15px;
}

.detail {
    border: 1px solid #eeeeee;

    padding: 10px;

    border-radius: 7px;
}

.detail-label {
    font-size: 11px;
    color: #888;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 13px;
    font-weight: bold;
}


/* PROBLEM */

.problem {
    padding: 12px;

    background: #f8fafc;

    border-radius: 8px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 16px;
}

.problem strong {
    display: block;
    margin-bottom: 5px;
}


/* BUTTONS */

.actions {
    display: flex;

    gap: 8px;

    flex-wrap: wrap;
}

.btn {
    border: none;

    border-radius: 7px;

    padding: 10px 14px;

    cursor: pointer;

    font-size: 13px;

    font-weight: bold;
}

.accept {
    background: #1264d6;
    color: white;
}

.reject {
    background: #ffe5e5;
    color: #c00000;
}

.reschedule {
    background: #eee9ff;
    color: #6d28d9;
}

.btn:hover {
    opacity: .9;
}


/* RESCHEDULE */

.reschedule-box {
    display: none;

    margin-top: 15px;

    padding: 15px;

    background: #f8fafc;

    border-radius: 8px;

    border: 1px solid #e5e7eb;
}

.reschedule-box.show {
    display: block;
}

.reschedule-grid {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 10px;

    margin-bottom: 10px;
}

.reschedule-box label {
    display: block;

    font-size: 12px;

    font-weight: bold;

    margin-bottom: 5px;
}

.reschedule-box input {
    width: 100%;

    padding: 10px;

    border: 1px solid #d7dce3;

    border-radius: 7px;

    outline: none;
}

.submit-reschedule {
    background: #1264d6;

    color: white;

    border: none;

    padding: 10px 15px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: bold;
}


/* EMPTY */

.empty {
    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 60px 20px;

    text-align: center;
}

.empty-icon {
    font-size: 40px;
    margin-bottom: 15px;
}

.empty h3 {
    margin-bottom: 7px;
}

.empty p {
    color: #777;
    font-size: 14px;
}


/* MOBILE */

@media (max-width: 950px) {

    .requests {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {

    .header {
        padding: 0 15px;
    }

    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;

        display: flex;

        overflow-x: auto;

        padding: 8px;

        border-right: none;

        border-bottom: 1px solid #e5e7eb;
    }

    .sidebar a {
        white-space: nowrap;
        margin: 0 3px;
        padding: 10px 12px;
    }

    .sidebar .logout {
        margin-top: 0;
    }

    .main {
        padding: 15px;
    }

    .page-title {
        font-size: 21px;
    }

    .details {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 400px) {

    .actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }

    .reschedule-grid {
        grid-template-columns: 1fr;
    }
}

</style>
<link rel="stylesheet" href="provider-theme.css?v=<?= filemtime(__DIR__ . '/provider-theme.css') ?>">
</head>
<body class="provider-app-page">


<!-- HEADER -->

<header class="header">

    <div class="logo">
        Gofix Provider
    </div>

    <div class="provider-name">

        <?php
        echo htmlspecialchars(
            $_SESSION['provider_name'] ?? 'Provider'
        );
        ?>

    </div>

</header>


<div class="layout">


<!-- SIDEBAR -->

<aside class="sidebar">

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="new_requests.php"
       class="active">
        New Requests
    </a>

    <a href="accepted_jobs.php">
        Accepted Jobs
    </a>

    <a href="rescheduled_jobs.php">
        Rescheduled
    </a>

    <a href="completed_jobs.php">
        Completed
    </a>

    <a href="rejected_jobs.php">
        Rejected
    </a>

    <a href="profile.php">
        My Profile
    </a>

    <a href="logout.php"
       class="logout">
        Logout
    </a>

</aside>


<!-- MAIN -->

<main class="main"
      data-request-inbox
      data-request-count="<?php echo $request_count; ?>"
      data-request-latest="<?php echo $request_latest_id; ?>">

    <h1 class="page-title">
        New Booking Requests
    </h1>

    <p class="page-subtitle">
        New service requests available for you.
    </p>


    <?php if ($success !== ""): ?>

        <div class="message success">
            <?php
            echo htmlspecialchars($success);
            ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">
            <?php
            echo htmlspecialchars($error);
            ?>
        </div>

    <?php endif; ?>


    <?php if (count($requests) > 0): ?>


        <div class="requests">


        <?php foreach ($requests as $request): ?>


            <div class="request-card">


                <!-- HEADER -->

                <div class="card-header">

                    <div>

                        <div class="service-name">

                            <?php
                            echo htmlspecialchars(
                                $request['service_name']
                                ?? 'Service'
                            );
                            ?>

                        </div>

                        <div class="booking-id">

                            Booking ID:

                            <?php
                            echo htmlspecialchars(
                                $request['booking_id']
                            );
                            ?>

                        </div>

                    </div>


                    <span class="badge">
                        New Request
                    </span>

                </div>


                <!-- CUSTOMER -->

                <div class="customer-box">

                    <div class="customer-name">

                        <?php
                        echo htmlspecialchars(
                            $request['customer_name']
                        );
                        ?>

                    </div>


                    <div class="info">

                        📱
                        <?php
                        echo htmlspecialchars(
                            $request['mobile']
                        );
                        ?>

                    </div>


                    <?php if (
                        !empty(
                            $request['alternate_mobile']
                        )
                    ): ?>

                        <div class="info">

                            Alternate:
                            <?php
                            echo htmlspecialchars(
                                $request['alternate_mobile']
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <div class="info">

                        📍
                        <?php
                        echo htmlspecialchars(
                            $request['address']
                        );
                        ?>

                    </div>


                    <div class="info">

                        <?php
                        echo htmlspecialchars(
                            $request['village']
                        );
                        ?>


                        <?php if (
                            !empty($request['area'])
                        ): ?>

                            ,
                            <?php
                            echo htmlspecialchars(
                                $request['area']
                            );
                            ?>

                        <?php endif; ?>


                        <?php if (
                            !empty($request['city'])
                        ): ?>

                            ,
                            <?php
                            echo htmlspecialchars(
                                $request['city']
                            );
                            ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- DETAILS -->

                <div class="details">


                    <div class="detail">

                        <div class="detail-label">
                            Booking Date
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $request['booking_date']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            Booking Time
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $request['booking_time']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            Service Price
                        </div>

                        <div class="detail-value">

                            ₹<?php
                            echo number_format(
                                (float)$request['price'],
                                2
                            );
                            ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            Payment
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $request['payment_mode']
                            );
                            ?>

                        </div>

                    </div>


                </div>


                <!-- PROBLEM -->

                <?php if (
                    !empty(
                        $request['problem_description']
                    )
                ): ?>

                    <div class="problem">

                        <strong>
                            Problem / Requirement
                        </strong>

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $request[
                                    'problem_description'
                                ]
                            )
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- SPECIAL INSTRUCTION -->

                <?php if (
                    !empty(
                        $request['special_instruction']
                    )
                ): ?>

                    <div class="problem">

                        <strong>
                            Special Instruction
                        </strong>

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $request[
                                    'special_instruction'
                                ]
                            )
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- ACTIONS -->

                <div class="actions">


                    <!-- ACCEPT -->

                    <form method="POST">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php
                            echo (int)$request['id'];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?php
                            echo (int)$request[
                                'notification_id'
                            ];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="accept"
                        >

                        <button
                            type="submit"
                            class="btn accept"
                            onclick="return confirm('Accept this booking?');"
                        >
                            Accept
                        </button>

                    </form>


                    <!-- REJECT -->

                    <form method="POST">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php
                            echo (int)$request['id'];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?php
                            echo (int)$request[
                                'notification_id'
                            ];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >

                        <button
                            type="submit"
                            class="btn reject"
                            onclick="return confirm('Reject this request?');"
                        >
                            Reject
                        </button>

                    </form>


                    <!-- RESCHEDULE -->

                    <button
                        type="button"
                        class="btn reschedule"
                        onclick="toggleReschedule(
                            <?php
                            echo (int)$request[
                                'notification_id'
                            ];
                            ?>
                        )"
                    >
                        Reschedule
                    </button>

                </div>


                <!-- RESCHEDULE -->

                <div
                    class="reschedule-box"
                    id="reschedule-<?php
                    echo (int)$request[
                        'notification_id'
                    ];
                    ?>"
                >

                    <form method="POST">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php
                            echo (int)$request['id'];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?php
                            echo (int)$request[
                                'notification_id'
                            ];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reschedule"
                        >


                        <div class="reschedule-grid">


                            <div>

                                <label>
                                    New Date
                                </label>

                                <input
                                    type="date"
                                    name="reschedule_date"
                                    min="<?php
                                    echo date('Y-m-d');
                                    ?>"
                                    required
                                >

                            </div>


                            <div>

                                <label>
                                    New Time
                                </label>

                                <input
                                    type="time"
                                    name="reschedule_time"
                                    required
                                >

                            </div>


                        </div>


                        <button
                            type="submit"
                            class="submit-reschedule"
                        >
                            Submit Reschedule
                        </button>

                    </form>

                </div>


            </div>


        <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                📋
            </div>

            <h3>
                No New Requests
            </h3>

            <p>
                You don't have any new booking requests right now.
            </p>

        </div>


    <?php endif; ?>


</main>

</div>


<script>

function toggleReschedule(id) {

    const box =
        document.getElementById(
            "reschedule-" + id
        );

    if (!box) {
        return;
    }

    box.classList.toggle("show");
}

</script>

<script src="request-polling.js?v=<?= (string) filemtime(__DIR__ . '/request-polling.js') ?>"></script>


</body>

</html>
