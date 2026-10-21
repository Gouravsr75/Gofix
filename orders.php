<?php

session_start();

include "db.php";

/* =========================================
   LOGIN CHECK
========================================= */

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$success = "";
$error = "";


/* =========================================
   HELPER FUNCTIONS
========================================= */

/*
   Convert booking status into readable text
*/

function statusText($status)
{
    $status_names = [
        'pending'            => 'Pending',
        'provider_notified'  => 'Provider Notified',
        'accepted'           => 'Accepted',
        'rejected'           => 'Rejected',
        'rescheduled'        => 'Rescheduled',
        'on_the_way'         => 'On The Way',
        'work_started'       => 'Work Started',
        'completed'          => 'Completed',
        'cancelled'          => 'Cancelled'
    ];

    return $status_names[$status] ?? ucfirst($status);
}


/*
   Status CSS class
*/

function statusClass($status)
{
    return 'status-' .
        strtolower(
            str_replace(
                '_',
                '-',
                $status
            )
        );
}


/*
   Check whether provider has EVER accepted
   this booking.
*/

function providerAccepted($conn, $booking_id)
{
    $sql = "SELECT id
            FROM booking_status_history
            WHERE booking_id = ?
            AND new_status = 'accepted'
            AND changed_by = 'provider'
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "i",
        $booking_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $accepted =
        ($result->num_rows > 0);

    $stmt->close();

    return $accepted;
}


/*
   Get booking history
*/

function getStatusHistory($conn, $booking_id)
{
    $history = [];

    $sql = "SELECT
                old_status,
                new_status,
                changed_by,
                note,
                created_at
            FROM booking_status_history
            WHERE booking_id = ?
            ORDER BY created_at ASC, id ASC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return $history;
    }

    $stmt->bind_param(
        "i",
        $booking_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();

    return $history;
}


/* =========================================
   CANCEL ORDER
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['cancel_booking'])
) {

    $booking_db_id =
        (int) ($_POST['booking_db_id'] ?? 0);

    if ($booking_db_id <= 0) {

        $error = "Invalid booking.";

    } else {

        /* -----------------------------------------
           Get booking belonging to current user
        ----------------------------------------- */

        $sql = "SELECT
                    id,
                    booking_id,
                    provider_id,
                    booking_status
                FROM bookings
                WHERE id = ?
                AND user_id = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error =
                "Database error: " .
                $conn->error;

        } else {

            $stmt->bind_param(
                "ii",
                $booking_db_id,
                $user_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if ($result->num_rows !== 1) {

                $error =
                    "Booking not found.";

            } else {

                $booking =
                    $result->fetch_assoc();


                /* -----------------------------------------
                   Check provider accepted before
                ----------------------------------------- */

                $has_provider_accepted =
                    providerAccepted(
                        $conn,
                        $booking_db_id
                    );


                /* -----------------------------------------
                   Cancellation Rules
                ----------------------------------------- */

                if ($has_provider_accepted) {

                    $error =
                        "This order cannot be cancelled because the service provider has already accepted it.";

                } elseif (
                    !in_array(
                        $booking['booking_status'],
                        [
                            'pending',
                            'provider_notified'
                        ],
                        true
                    )
                ) {

                    $error =
                        "This order cannot be cancelled at this stage.";

                } else {


                    /* -----------------------------------------
                       Update Booking
                    ----------------------------------------- */

                    $old_status =
                        $booking['booking_status'];

                    $new_status =
                        'cancelled';


                    $update_sql =
                        "UPDATE bookings
                         SET booking_status = ?
                         WHERE id = ?
                         AND user_id = ?";

                    $update =
                        $conn->prepare(
                            $update_sql
                        );


                    if (!$update) {

                        $error =
                            "Unable to cancel booking.";

                    } else {

                        $update->bind_param(
                            "sii",
                            $new_status,
                            $booking_db_id,
                            $user_id
                        );


                        if ($update->execute()) {


                            /* -----------------------------------------
                               Add History
                            ----------------------------------------- */

                            $history_sql =
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
                                (
                                    ?,
                                    ?,
                                    ?,
                                    'customer',
                                    ?,
                                    ?
                                )";


                            $history =
                                $conn->prepare(
                                    $history_sql
                                );


                            if ($history) {

                                $note =
                                    "Booking cancelled by customer.";

                                $history->bind_param(
                                    "issis",
                                    $booking_db_id,
                                    $old_status,
                                    $new_status,
                                    $user_id,
                                    $note
                                );

                                $history->execute();

                                $history->close();
                            }


                            /* -----------------------------------------
                               Notify Provider
                            ----------------------------------------- */

                            if (
                                !empty(
                                    $booking['provider_id']
                                )
                            ) {

                                $notification_sql =
                                    "INSERT INTO provider_notifications
                                    (
                                        provider_id,
                                        booking_id,
                                        notification_type,
                                        message
                                    )
                                    VALUES
                                    (
                                        ?,
                                        ?,
                                        'booking_cancelled',
                                        ?
                                    )";


                                $notification =
                                    $conn->prepare(
                                        $notification_sql
                                    );


                                if ($notification) {

                                    $message =
                                        "Booking " .
                                        $booking['booking_id'] .
                                        " has been cancelled by the customer.";

                                    $notification->bind_param(
                                        "iis",
                                        $booking['provider_id'],
                                        $booking_db_id,
                                        $message
                                    );

                                    $notification->execute();

                                    $notification->close();
                                }
                            }


                            $success =
                                "Booking " .
                                $booking['booking_id'] .
                                " cancelled successfully.";

                        } else {

                            $error =
                                "Unable to cancel booking.";
                        }


                        $update->close();
                    }
                }
            }

            $stmt->close();
        }
    }
}


/* =========================================
   GET USER BOOKINGS
========================================= */

$orders = [];

$sql = "SELECT
            b.id AS booking_db_id,
            b.booking_id,
            b.provider_id,
            b.service_id,
            b.customer_name,
            b.mobile,
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
            b.provider_note,
            b.rescheduled_date,
            b.rescheduled_time,
            b.created_at,

            s.service_name,
            s.category,
            s.image,

            sp.provider_name,
            sp.mobile AS provider_mobile

        FROM bookings b

        LEFT JOIN services s
            ON b.service_id = s.id

        LEFT JOIN service_providers sp
            ON b.provider_id = sp.id

        WHERE b.user_id = ?

        ORDER BY b.id DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Error: " .
        $conn->error
    );
}


$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result =
    $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $orders[] = $row;
}


$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Orders - Fixlify</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================
   BODY
========================================= */

body {
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #222;
}


/* =========================================
   HEADER
========================================= */

.header {
    height: 65px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 25px;
}

.logo {
    color: #1264d6;
    font-size: 21px;
    font-weight: bold;
}

.header-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-links a {
    text-decoration: none;
    color: #444;
    font-size: 14px;
    font-weight: 500;
}

.header-links a:hover {
    color: #1264d6;
}


/* =========================================
   CONTAINER
========================================= */

.container {
    width: 100%;
    max-width: 1200px;
    margin: auto;
    padding: 30px 20px;
}

.title {
    font-size: 28px;
    margin-bottom: 6px;
}

.subtitle {
    color: #777;
    font-size: 14px;
    margin-bottom: 25px;
}


/* =========================================
   MESSAGE
========================================= */

.message {
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.success {
    background: #e7f7ed;
    color: #15803d;
    border: 1px solid #b7e4c7;
}

.error {
    background: #ffe8e8;
    color: #c00000;
    border: 1px solid #f3b5b5;
}


/* =========================================
   NO ORDERS
========================================= */

.no-orders {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 50px 20px;
    text-align: center;
}

.no-orders h2 {
    font-size: 20px;
    margin-bottom: 8px;
}

.no-orders p {
    color: #777;
    font-size: 14px;
    margin-bottom: 20px;
}

.book-btn {
    display: inline-block;
    background: #1264d6;
    color: #fff;
    text-decoration: none;
    padding: 11px 20px;
    border-radius: 7px;
    font-size: 14px;
    font-weight: bold;
}


/* =========================================
   ORDERS CONTAINER
   HORIZONTAL CARDS
========================================= */

.orders-container {
    width: 100%;

    display: flex;
    flex-wrap: wrap;

    align-items: flex-start;

    gap: 20px;
}


/* =========================================
   ORDER CARD
========================================= */

.order-card {
    width: calc(40% - 10px);

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    overflow: hidden;

    margin: 0;
}


/* =========================================
   ORDER TOP
========================================= */

.order-top {
    padding: 18px 20px;

    border-bottom: 1px solid #eee;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;
}

.service-name {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 5px;
}

.booking-id {
    color: #777;
    font-size: 12px;
}


/* =========================================
   STATUS
========================================= */

.status-badge {
    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    white-space: nowrap;
}

.status-pending {
    background: #fff4d6;
    color: #9a6700;
}

.status-provider-notified {
    background: #e8f2ff;
    color: #1264d6;
}

.status-accepted {
    background: #e7f7ed;
    color: #15803d;
}

.status-rescheduled {
    background: #f0e9ff;
    color: #6941c6;
}

.status-on-the-way {
    background: #e8f7ff;
    color: #087ea4;
}

.status-work-started {
    background: #e8f7ff;
    color: #087ea4;
}

.status-completed {
    background: #e7f7ed;
    color: #15803d;
}

.status-rejected {
    background: #ffe8e8;
    color: #c00000;
}

.status-cancelled {
    background: #eeeeee;
    color: #666;
}


/* =========================================
   ORDER BODY
========================================= */

.order-body {
    padding: 20px;
}


/* =========================================
   SERVICE IMAGE
========================================= */

.service-image-box {
    width: 100%;

    margin-bottom: 18px;

    border-radius: 8px;

    overflow: hidden;

    background: #f5f7fb;
}

.service-image-box img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
}


/* =========================================
   ORDER INFORMATION
========================================= */

.order-info {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 15px;

    margin-bottom: 20px;
}

.info-box {
    background: #f8fafc;

    border-radius: 8px;

    padding: 13px;
}

.info-label {
    color: #777;

    font-size: 12px;

    margin-bottom: 5px;
}

.info-value {
    font-size: 14px;

    font-weight: bold;
}


/* =========================================
   PROVIDER
========================================= */

.provider-box {
    background: #f8fafc;

    border-radius: 8px;

    padding: 15px;

    margin-bottom: 20px;
}

.provider-title {
    color: #1264d6;

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 8px;
}

.provider-info {
    font-size: 13px;

    color: #555;

    line-height: 1.7;
}


/* =========================================
   RESCHEDULE
========================================= */

.reschedule-box {
    background: #f0e9ff;

    border: 1px solid #ddd0ff;

    border-radius: 8px;

    padding: 14px;

    margin-bottom: 20px;
}

.reschedule-box strong {
    color: #6941c6;

    font-size: 14px;
}

.reschedule-box p {
    color: #555;

    font-size: 13px;

    margin-top: 5px;
}


/* =========================================
   TRACKING
========================================= */

.track-btn {
    width: 100%;

    padding: 11px;

    border: 1px solid #1264d6;

    background: #fff;

    color: #1264d6;

    border-radius: 7px;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    margin-bottom: 15px;
}

.track-btn:hover {
    background: #f0f6ff;
}

.tracking {
    display: none;

    border-top: 1px solid #eee;

    padding-top: 20px;

    margin-top: 5px;
}

.timeline {
    position: relative;

    margin-left: 8px;
}

.timeline::before {
    content: "";

    position: absolute;

    left: 8px;

    top: 5px;

    bottom: 5px;

    width: 2px;

    background: #d9dee7;
}

.timeline-item {
    position: relative;

    padding-left: 32px;

    margin-bottom: 20px;
}

.timeline-dot {
    position: absolute;

    left: 2px;

    top: 2px;

    width: 14px;

    height: 14px;

    border-radius: 50%;

    background: #d9dee7;

    border: 2px solid #fff;

    box-shadow: 0 0 0 1px #d9dee7;

    z-index: 2;
}

.timeline-item.active .timeline-dot {
    background: #1264d6;

    box-shadow:
        0 0 0 1px #1264d6;
}

.timeline-title {
    font-size: 14px;

    font-weight: bold;

    margin-bottom: 3px;
}

.timeline-time {
    color: #888;

    font-size: 12px;
}

.timeline-note {
    color: #555;

    font-size: 12px;

    margin-top: 5px;
}


/* =========================================
   BOTTOM ACTIONS
========================================= */

.order-actions {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-top: 20px;
}

.cancel-btn {
    padding: 10px 17px;

    border: none;

    border-radius: 7px;

    background: #dc2626;

    color: #fff;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;
}

.cancel-btn:hover {
    background: #b91c1c;
}

.cancel-btn:disabled {
    background: #d1d5db;

    color: #777;

    cursor: not-allowed;
}

.cancel-info {
    color: #888;

    font-size: 12px;

    text-align: right;
}


/* =========================================
   PRICE
========================================= */

.price {
    color: #1264d6;

    font-size: 17px;

    font-weight: bold;
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 900px) {

    .order-card {
        width: calc(50% - 10px);
    }

}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 700px) {

    .header {
        height: 60px;

        padding: 0 12px;
    }

    .logo {
        font-size: 19px;
    }

    .header-links {
        gap: 10px;
    }

    .header-links a {
        font-size: 11px;
    }

    .container {
        padding: 20px 12px;
    }

    .title {
        font-size: 24px;
    }

    /*
       One card per row on mobile
    */

    .orders-container {
        flex-direction: column;

        gap: 15px;
    }

    .order-card {
        width: 100%;
    }

    .order-top {
        align-items: flex-start;

        flex-direction: column;

        padding: 15px;
    }

    .order-body {
        padding: 15px;
    }

    .order-info {
        grid-template-columns: 1fr 1fr;

        gap: 10px;
    }

    .order-actions {
        align-items: stretch;

        flex-direction: column;
    }

    .cancel-info {
        text-align: left;
    }

}


/* =========================================
   SMALL MOBILE
========================================= */

@media (max-width: 450px) {

    .order-info {
        grid-template-columns: 1fr;
    }

    .service-name {
        font-size: 16px;
    }

    .header-links a:nth-child(1) {
        display: none;
    }

}

</style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="header">

    <div class="logo">
        Fixlify
    </div>

    <div class="header-links">

        <a href="dashboard.php">
            Home
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</header>



<!-- =========================================
     MAIN
========================================= -->

<div class="container">

    <h1 class="title">
        My Orders
    </h1>

    <p class="subtitle">
        Track your service bookings and check
        their current status.
    </p>



    <!-- =====================================
         SUCCESS MESSAGE
    ====================================== -->

    <?php if ($success !== ""): ?>

        <div class="message success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         ERROR MESSAGE
    ====================================== -->

    <?php if ($error !== ""): ?>

        <div class="message error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         NO ORDERS
    ====================================== -->

    <?php if (count($orders) === 0): ?>


        <div class="no-orders">

            <h2>
                No Orders Yet
            </h2>

            <p>
                You haven't booked any service yet.
            </p>

            <a
                href="index.php"
                class="book-btn"
            >
                Book a Service
            </a>

        </div>


    <?php else: ?>


        <!-- =================================
             ORDER CARDS CONTAINER
        ================================== -->

        <div class="orders-container">


        <?php foreach ($orders as $order): ?>


            <?php

            /*
               Check provider accepted
               at any point.
            */

            $has_provider_accepted =
                providerAccepted(
                    $conn,
                    $order['booking_db_id']
                );


            /*
               Get status history.
            */

            $history =
                getStatusHistory(
                    $conn,
                    $order['booking_db_id']
                );


            /*
               Cancellation allowed only when:

               1. Provider NEVER accepted
               2. Current status is pending OR
                  provider_notified
            */

            $can_cancel =
                !$has_provider_accepted
                &&
                in_array(
                    $order['booking_status'],
                    [
                        'pending',
                        'provider_notified'
                    ],
                    true
                );


            /*
               Service image
            */

            $service_image = getServiceImageUrl($order['image'] ?? '', $order['category'] ?? '');

            ?>


            <!-- =================================
                 ORDER CARD
            ================================== -->

            <div class="order-card">


                <!-- =========================
                     ORDER HEADER
                ========================== -->

                <div class="order-top">

                    <div>

                        <div class="service-name">

                            <?php
                            echo htmlspecialchars(
                                $order['service_name']
                                ?? 'Service'
                            );
                            ?>

                        </div>


                        <div class="booking-id">

                            Booking ID:

                            <?php
                            echo htmlspecialchars(
                                $order['booking_id']
                            );
                            ?>

                        </div>

                    </div>


                    <span
                        class="status-badge
                        <?php
                        echo statusClass(
                            $order['booking_status']
                        );
                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            statusText(
                                $order['booking_status']
                            )
                        );
                        ?>

                    </span>

                </div>



                <!-- =========================
                     ORDER BODY
                ========================== -->

                <div class="order-body">


                    <!-- SERVICE IMAGE -->

                    <?php if ($service_image !== ""): ?>

                        <div class="service-image-box">

                            <img
                                src="<?php
                                echo htmlspecialchars(
                                    $service_image
                                );
                                ?>"
                                alt="Service"
                            >

                        </div>

                    <?php endif; ?>



                    <!-- ORDER INFORMATION -->

                    <div class="order-info">


                        <div class="info-box">

                            <div class="info-label">
                                Booking Date
                            </div>

                            <div class="info-value">

                                <?php

                                echo htmlspecialchars(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $order['booking_date']
                                        )
                                    )
                                );

                                ?>

                            </div>

                        </div>



                        <div class="info-box">

                            <div class="info-label">
                                Booking Time
                            </div>

                            <div class="info-value">

                                <?php
                                echo htmlspecialchars(
                                    $order['booking_time']
                                );
                                ?>

                            </div>

                        </div>



                        <div class="info-box">

                            <div class="info-label">
                                Amount
                            </div>

                            <div class="price">

                                ₹<?php

                                echo number_format(
                                    (float)
                                    $order['price'],
                                    2
                                );

                                ?>

                            </div>

                        </div>



                        <div class="info-box">

                            <div class="info-label">
                                Payment
                            </div>

                            <div class="info-value">

                                <?php

                                echo htmlspecialchars(
                                    $order['payment_mode']
                                );

                                ?>

                                <br>

                                <small style="color:#777;">

                                    <?php

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $order[
                                                'payment_status'
                                            ]
                                        )
                                    );

                                    ?>

                                </small>

                            </div>

                        </div>



                        <div class="info-box">

                            <div class="info-label">
                                Category
                            </div>

                            <div class="info-value">

                                <?php

                                echo htmlspecialchars(
                                    $order['category']
                                    ?? '-'
                                );

                                ?>

                            </div>

                        </div>



                        <div class="info-box">

                            <div class="info-label">
                                Order Placed
                            </div>

                            <div class="info-value">

                                <?php

                                echo htmlspecialchars(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $order['created_at']
                                        )
                                    )
                                );

                                ?>

                            </div>

                        </div>


                    </div>



                    <!-- =========================
                         PROVIDER
                    ========================== -->

                    <?php

                    if (
                        !empty(
                            $order['provider_name']
                        )
                    ):

                    ?>

                        <div class="provider-box">

                            <div class="provider-title">
                                Service Provider
                            </div>

                            <div class="provider-info">

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order[
                                            'provider_name'
                                        ]
                                    );

                                    ?>

                                </strong>

                                <br>

                                Mobile:

                                <?php

                                echo htmlspecialchars(
                                    $order[
                                        'provider_mobile'
                                    ]
                                    ?? '-'
                                );

                                ?>

                            </div>

                        </div>


                    <?php else: ?>


                        <div class="provider-box">

                            <div class="provider-title">
                                Service Provider
                            </div>

                            <div class="provider-info">

                                Provider has not been assigned yet.

                            </div>

                        </div>


                    <?php endif; ?>



                    <!-- =========================
                         RESCHEDULE
                    ========================== -->

                    <?php

                    if (
                        $order['booking_status']
                        === 'rescheduled'
                        &&
                        !empty(
                            $order['rescheduled_date']
                        )
                    ):

                    ?>

                        <div class="reschedule-box">

                            <strong>
                                New Schedule Proposed
                            </strong>

                            <p>

                                Date:

                                <?php

                                echo htmlspecialchars(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $order[
                                                'rescheduled_date'
                                            ]
                                        )
                                    )
                                );

                                ?>

                                <br>

                                Time:

                                <?php

                                echo htmlspecialchars(
                                    $order[
                                        'rescheduled_time'
                                    ]
                                );

                                ?>

                            </p>

                        </div>

                    <?php endif; ?>



                    <!-- =========================
                         TRACK BUTTON
                    ========================== -->

                    <button
                        type="button"
                        class="track-btn"
                        onclick="toggleTracking(
                            <?php
                            echo (int)
                                $order['booking_db_id'];
                            ?>
                        )"
                    >

                        Track Order

                    </button>



                    <!-- =========================
                         TRACKING TIMELINE
                    ========================== -->

                    <div
                        class="tracking"
                        id="tracking-<?php
                        echo (int)
                            $order['booking_db_id'];
                        ?>"
                    >

                        <div class="timeline">


                            <?php if (
                                count($history) > 0
                            ): ?>


                                <?php

                                foreach (
                                    $history
                                    as $item
                                ):

                                ?>

                                    <div
                                        class="timeline-item active"
                                    >

                                        <div
                                            class="timeline-dot"
                                        ></div>


                                        <div
                                            class="timeline-title"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                statusText(
                                                    $item[
                                                        'new_status'
                                                    ]
                                                )
                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="timeline-time"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y, h:i A",
                                                    strtotime(
                                                        $item[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            );

                                            ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $item['note']
                                            )
                                        ): ?>

                                            <div
                                                class="timeline-note"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $item[
                                                        'note'
                                                    ]
                                                );

                                                ?>

                                            </div>

                                        <?php endif; ?>


                                    </div>


                                <?php
                                endforeach;
                                ?>


                            <?php else: ?>


                                <div
                                    class="
                                    timeline-item
                                    active
                                    "
                                >

                                    <div
                                        class="timeline-dot"
                                    ></div>


                                    <div
                                        class="timeline-title"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            statusText(
                                                $order[
                                                    'booking_status'
                                                ]
                                            )
                                        );

                                        ?>

                                    </div>

                                </div>


                            <?php endif; ?>


                        </div>

                    </div>



                    <!-- =========================
                         ACTIONS
                    ========================== -->

                    <div class="order-actions">


                        <?php if ($can_cancel): ?>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Are you sure you want to cancel this booking?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="booking_db_id"
                                    value="<?php
                                    echo (int)
                                        $order[
                                            'booking_db_id'
                                        ];
                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="cancel_booking"
                                    class="cancel-btn"
                                >

                                    Cancel Order

                                </button>

                            </form>


                            <div class="cancel-info">

                                You can cancel this order
                                before the provider accepts it.

                            </div>


                        <?php elseif (
                            $has_provider_accepted
                        ): ?>


                            <button
                                type="button"
                                class="cancel-btn"
                                disabled
                            >

                                Cancel Disabled

                            </button>


                            <div class="cancel-info">

                                Provider has already accepted
                                this booking.

                            </div>


                        <?php elseif (
                            $order['booking_status']
                            === 'completed'
                        ): ?>


                            <button
                                type="button"
                                class="cancel-btn"
                                disabled
                            >

                                Completed

                            </button>


                            <div class="cancel-info">

                                This order has been completed.

                            </div>


                        <?php elseif (
                            $order['booking_status']
                            === 'cancelled'
                        ): ?>


                            <button
                                type="button"
                                class="cancel-btn"
                                disabled
                            >

                                Cancelled

                            </button>


                        <?php elseif (
                            $order['booking_status']
                            === 'rejected'
                        ): ?>


                            <button
                                type="button"
                                class="cancel-btn"
                                disabled
                            >

                                Rejected

                            </button>


                        <?php else: ?>


                            <button
                                type="button"
                                class="cancel-btn"
                                disabled
                            >

                                Cancel Disabled

                            </button>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>



<script>

/* =========================================
   TOGGLE TRACKING
========================================= */

function toggleTracking(id) {

    const tracking =
        document.getElementById(
            "tracking-" + id
        );


    if (!tracking) {
        return;
    }


    if (
        tracking.style.display === "block"
    ) {

        tracking.style.display = "none";

    } else {

        tracking.style.display = "block";
    }

}

</script>


</body>

</html>