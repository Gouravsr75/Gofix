
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan summary ke liye database counts nikale jaate hain.


/* =========================================
   HELPER FUNCTION
========================================= */

function getCount($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int)($row['total'] ?? 0);
}


/* =========================================
   BOOKING COUNTS
========================================= */

$total_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings"
);


$pending_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status IN
     ('pending','provider_notified')"
);


$accepted_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status = 'accepted'"
);


$rescheduled_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status = 'rescheduled'"
);


$in_progress_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status IN
     ('on_the_way','work_started')"
);


$completed_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status = 'completed'"
);


$cancelled_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status = 'cancelled'"
);


$rejected_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_status = 'rejected'"
);


/* =========================================
   USER / PROVIDER / SERVICE COUNTS
========================================= */

$total_customers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);


$active_customers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE status = 'active'"
);


$total_providers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM service_providers"
);


$active_providers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM service_providers
     WHERE status = 'active'"
);


$pending_providers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM service_providers
     WHERE status = 'pending'"
);


$total_services = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM services"
);


$active_services = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM services
     WHERE status = 1"
);


/* =========================================
   PAYMENT / REVENUE
========================================= */

$total_revenue = 0;

$sql = "SELECT
            COALESCE(SUM(price), 0) AS revenue
        FROM bookings
        WHERE booking_status = 'completed'";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $total_revenue =
        (float)($row['revenue'] ?? 0);
}


/* =========================================
   TODAY'S BOOKINGS
========================================= */

$today_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_date = CURDATE()"
);


/* =========================================
   RECENT BOOKINGS
========================================= */

$recent_bookings = [];

$sql = "SELECT

            b.id,
            b.booking_id,
            b.customer_name,
            b.booking_date,
            b.booking_time,
            b.price,
            b.booking_status,

            s.service_name,

            sp.provider_name

        FROM bookings b

        LEFT JOIN services s
            ON b.service_id = s.id

        LEFT JOIN service_providers sp
            ON b.provider_id = sp.id

        ORDER BY b.id DESC

        LIMIT 8";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recent_bookings[] = $row;
    }
}


/* =========================================
   PENDING PROVIDERS
========================================= */

$pending_provider_list = [];

$sql = "SELECT
            id,
            provider_name,
            mobile,
            area,
            city,
            experience,
            created_at

        FROM service_providers

        WHERE status = 'pending'

        ORDER BY id DESC

        LIMIT 5";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $pending_provider_list[] = $row;
    }
}


/* =========================================
   BOOKING STATUS TEXT
========================================= */

function bookingStatus($status)
{
    $status_list = [

        'pending' =>
            'Pending',

        'provider_notified' =>
            'Provider Notified',

        'accepted' =>
            'Accepted',

        'rejected' =>
            'Rejected',

        'rescheduled' =>
            'Rescheduled',

        'on_the_way' =>
            'On The Way',

        'work_started' =>
            'Work Started',

        'completed' =>
            'Completed',

        'cancelled' =>
            'Cancelled'
    ];

    return
        $status_list[$status]
        ?? ucfirst($status);
}


/* =========================================
   ADMIN NAME
========================================= */

$admin_name =
    $_SESSION['admin_name']
    ?? "Administrator";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Dashboard</title>
<script src="history-lock.js?v=<?= (string) filemtime(__DIR__ . '/history-lock.js') ?>"></script>


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
   SIDEBAR
========================================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    width: 240px;

    background: #ffffff;

    border-right: 1px solid #e5e7eb;

    padding: 20px 15px;

    z-index: 1000;
}


.logo {

    color: #1264d6;

    font-size: 23px;

    font-weight: bold;

    padding: 5px 12px 25px;
}


.admin-info {

    background: #f5f8ff;

    border-radius: 10px;

    padding: 13px;

    margin-bottom: 20px;
}


.admin-label {

    color: #777;

    font-size: 11px;

    margin-bottom: 4px;
}


.admin-name {

    color: #222;

    font-size: 14px;

    font-weight: bold;
}


.menu {

    display: flex;

    flex-direction: column;

    gap: 5px;
}


.menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 12px;

    border-radius: 8px;

    text-decoration: none;

    color: #555;

    font-size: 14px;
}


.menu a:hover,
.menu a.active {

    background: #eaf2ff;

    color: #1264d6;
}


.menu-icon {

    width: 22px;

    text-align: center;

    font-size: 16px;
}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 240px;

    min-height: 100vh;
}


/* =========================================
   TOP BAR
========================================= */

.topbar {

    height: 70px;

    background: #ffffff;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;
}


.page-title {

    font-size: 22px;

    font-weight: bold;
}


.top-right {

    display: flex;

    align-items: center;

    gap: 15px;
}


.view-site {

    text-decoration: none;

    color: #1264d6;

    font-size: 13px;

    font-weight: bold;
}


.logout {

    text-decoration: none;

    background: #1264d6;

    color: #ffffff;

    padding: 9px 14px;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;
}


/* =========================================
   CONTENT
========================================= */

.content {

    padding: 25px 30px;
}


.welcome {

    margin-bottom: 22px;
}


.welcome h1 {

    font-size: 24px;

    margin-bottom: 5px;
}


.welcome p {

    color: #777;

    font-size: 14px;
}


/* =========================================
   STAT CARDS
========================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.stat-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    padding: 18px;

    position: relative;
}


.stat-label {

    color: #777;

    font-size: 12px;

    margin-bottom: 8px;
}


.stat-number {

    color: #1264d6;

    font-size: 26px;

    font-weight: bold;
}


.stat-small {

    color: #999;

    font-size: 11px;

    margin-top: 5px;
}


/* =========================================
   SECTION
========================================= */

.section {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    margin-bottom: 20px;

    overflow: hidden;
}


.section-header {

    padding: 17px 20px;

    border-bottom: 1px solid #eee;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.section-header h2 {

    font-size: 17px;
}


.view-all {

    color: #1264d6;

    text-decoration: none;

    font-size: 12px;

    font-weight: bold;
}


/* =========================================
   QUICK STATS
========================================= */

.quick-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    padding: 20px;
}


.quick-box {

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    padding: 15px;
}


.quick-box h3 {

    font-size: 13px;

    color: #555;

    margin-bottom: 8px;
}


.quick-number {

    color: #1264d6;

    font-size: 23px;

    font-weight: bold;
}


/* =========================================
   RECENT TABLE
========================================= */

.table-wrapper {

    width: 100%;

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 700px;
}


th {

    text-align: left;

    background: #f8fafc;

    color: #666;

    font-size: 12px;

    font-weight: bold;

    padding: 12px 15px;

    border-bottom: 1px solid #e5e7eb;
}


td {

    padding: 13px 15px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 13px;

    vertical-align: middle;
}


tbody tr:hover {

    background: #fafcff;
}


.booking-id {

    color: #1264d6;

    font-weight: bold;

    font-size: 12px;
}


.customer-name {

    font-weight: bold;

    margin-bottom: 3px;
}


.provider {

    color: #666;

    font-size: 12px;
}


/* =========================================
   STATUS BADGES
========================================= */

.status {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

    white-space: nowrap;
}


.status-pending,
.status-provider_notified {

    background: #fff4d6;

    color: #9a6700;
}


.status-accepted {

    background: #e7f7ed;

    color: #15803d;
}


.status-rescheduled {

    background: #f0e9ff;

    color: #6941c6;
}


.status-on_the_way,
.status-work_started {

    background: #e8f7ff;

    color: #087ea4;
}


.status-completed {

    background: #e7f7ed;

    color: #15803d;
}


.status-cancelled,
.status-rejected {

    background: #ffe8e8;

    color: #c00000;
}


/* =========================================
   PROVIDER LIST
========================================= */

.provider-list {

    padding: 5px 20px 10px;
}


.provider-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 14px 0;

    border-bottom: 1px solid #eee;
}


.provider-item:last-child {

    border-bottom: none;
}


.provider-left {

    display: flex;

    align-items: center;

    gap: 12px;
}


.provider-avatar {

    width: 40px;

    height: 40px;

    border-radius: 50%;

    background: #eaf2ff;

    color: #1264d6;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}


.provider-name {

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 3px;
}


.provider-details {

    color: #777;

    font-size: 11px;
}


.review-btn {

    background: #1264d6;

    color: #ffffff;

    text-decoration: none;

    padding: 7px 11px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: bold;
}


/* =========================================
   TWO COLUMN
========================================= */

.two-column {

    display: grid;

    grid-template-columns:
        2fr 1fr;

    gap: 20px;
}


/* =========================================
   EMPTY
========================================= */

.empty {

    padding: 30px 20px;

    text-align: center;

    color: #888;

    font-size: 13px;
}


/* =========================================
   MOBILE MENU BUTTON
========================================= */

.mobile-menu {

    display: none;

    border: none;

    background: #1264d6;

    color: #ffffff;

    width: 38px;

    height: 38px;

    border-radius: 7px;

    font-size: 20px;

    cursor: pointer;
}


/* =========================================
   OVERLAY
========================================= */

.overlay {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.35);

    z-index: 999;
}


/* =========================================
   MOBILE
========================================= */

@media(max-width:1000px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .quick-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .two-column {

        grid-template-columns: 1fr;
    }

}


@media(max-width:700px) {

    .sidebar {

        left: -250px;

        transition: .25s;
    }


    .sidebar.show {

        left: 0;
    }


    .overlay.show {

        display: block;
    }


    .main {

        margin-left: 0;
    }


    .topbar {

        padding: 0 15px;

        height: 62px;
    }


    .mobile-menu {

        display: block;
    }


    .page-title {

        font-size: 18px;
    }


    .view-site {

        display: none;
    }


    .content {

        padding: 20px 12px;
    }


    .stats {

        grid-template-columns: 1fr 1fr;

        gap: 10px;
    }


    .stat-card {

        padding: 14px;
    }


    .stat-number {

        font-size: 22px;
    }


    .quick-grid {

        grid-template-columns: 1fr 1fr;

        padding: 15px;

        gap: 10px;
    }


    .section-header {

        padding: 15px;
    }

}


@media(max-width:450px) {

    .stats {

        grid-template-columns: 1fr;
    }


    .quick-grid {

        grid-template-columns: 1fr;
    }


    .welcome h1 {

        font-size: 21px;
    }

}

</style>

    <link rel="stylesheet" href="admin-ui.css">
</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<?php require __DIR__ . '/sidebar.php'; ?>






<!-- =========================================
     MAIN
========================================= -->

<main class="main">


    <!-- =====================================
         TOP BAR
    ====================================== -->

    <header class="topbar">


        <div style="
            display:flex;
            align-items:center;
            gap:12px;
        ">


            <button
                class="mobile-menu"
                onclick="openMenu()"
            >
                ☰
            </button>


            <div class="page-title">
                Dashboard
            </div>


        </div>


        <div class="top-right">


            <a
                href="../index.php"
                class="view-site"
            >
                View Website
            </a>


            <a
                href="logout.php"
                class="logout"
            >
                Logout
            </a>


        </div>


    </header>



    <!-- =====================================
         CONTENT
    ====================================== -->

    <div class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h1>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $admin_name
                );
                ?>

            </h1>

            <p>
                Here's what's happening on Gofix today.
            </p>

        </div>



        <!-- =================================
             MAIN STATISTICS
        ================================== -->

        <div class="stats">


            <!-- Total Bookings -->

            <div class="stat-card">

                <div class="stat-label">
                    Total Bookings
                </div>

                <div class="stat-number">

                    <?php
                    echo $total_bookings;
                    ?>

                </div>

                <div class="stat-small">
                    All bookings
                </div>

            </div>



            <!-- Pending -->

            <div class="stat-card">

                <div class="stat-label">
                    Pending Bookings
                </div>

                <div class="stat-number">

                    <?php
                    echo $pending_bookings;
                    ?>

                </div>

                <div class="stat-small">
                    Need attention
                </div>

            </div>



            <!-- Completed -->

            <div class="stat-card">

                <div class="stat-label">
                    Completed
                </div>

                <div class="stat-number">

                    <?php
                    echo $completed_bookings;
                    ?>

                </div>

                <div class="stat-small">
                    Successfully completed
                </div>

            </div>



            <!-- Revenue -->

            <div class="stat-card">

                <div class="stat-label">
                    Completed Revenue
                </div>

                <div class="stat-number">

                    ₹<?php
                    echo number_format(
                        $total_revenue,
                        0
                    );
                    ?>

                </div>

                <div class="stat-small">
                    Completed bookings
                </div>

            </div>


        </div>



        <!-- =================================
             QUICK STATISTICS
        ================================== -->

        <div class="section">


            <div class="section-header">

                <h2>
                    Quick Overview
                </h2>

            </div>


            <div class="quick-grid">


                <div class="quick-box">

                    <h3>
                        Today's Bookings
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $today_bookings;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Active Providers
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $active_providers;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Customers
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $active_customers;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Active Services
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $active_services;
                        ?>

                    </div>

                </div>


            </div>


        </div>



        <!-- =================================
             BOOKING STATUS
        ================================== -->

        <div class="section">


            <div class="section-header">

                <h2>
                    Booking Status
                </h2>

                <a
                    href="bookings.php"
                    class="view-all"
                >
                    View All
                </a>

            </div>


            <div class="quick-grid">


                <div class="quick-box">

                    <h3>
                        Accepted
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $accepted_bookings;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Rescheduled
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $rescheduled_bookings;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        In Progress
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $in_progress_bookings;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Cancelled
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $cancelled_bookings;
                        ?>

                    </div>

                </div>


            </div>


        </div>



        <!-- =================================
             TWO COLUMN
        ================================== -->

        <div class="two-column">


            <!-- =================================
                 RECENT BOOKINGS
            ================================== -->

            <div class="section">


                <div class="section-header">

                    <h2>
                        Recent Bookings
                    </h2>

                    <a
                        href="bookings.php"
                        class="view-all"
                    >
                        View All
                    </a>

                </div>


                <?php if (
                    count($recent_bookings) > 0
                ): ?>


                    <div class="table-wrapper">


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        Booking
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php
                                foreach (
                                    $recent_bookings
                                    as $booking
                                ):
                                ?>


                                    <tr>


                                        <td>

                                            <div
                                                class="booking-id"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'booking_id'
                                                    ]
                                                );
                                                ?>

                                            </div>

                                        </td>


                                        <td>

                                            <div
                                                class="customer-name"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'customer_name'
                                                    ]
                                                );
                                                ?>

                                            </div>


                                            <div
                                                class="provider"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'provider_name'
                                                    ]
                                                    ?? 'No provider'
                                                );
                                                ?>

                                            </div>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'service_name'
                                                ]
                                                ?? '-'
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y",
                                                    strtotime(
                                                        $booking[
                                                            'booking_date'
                                                        ]
                                                    )
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <strong>
                                                ₹<?php
                                                echo number_format(
                                                    (float)
                                                    $booking[
                                                        'price'
                                                    ],
                                                    2
                                                );
                                                ?>
                                            </strong>

                                        </td>


                                        <td>


                                            <span
                                                class="
                                                status
                                                status-<?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'booking_status'
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    bookingStatus(
                                                        $booking[
                                                            'booking_status'
                                                        ]
                                                    )
                                                );
                                                ?>

                                            </span>


                                        </td>


                                    </tr>


                                <?php
                                endforeach;
                                ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty">

                        No bookings found.

                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================
                 PENDING PROVIDERS
            ================================== -->

            <div class="section">


                <div class="section-header">

                    <h2>
                        Pending Providers
                    </h2>

                    <a
                        href="providers.php"
                        class="view-all"
                    >
                        View All
                    </a>

                </div>


                <?php if (
                    count(
                        $pending_provider_list
                    ) > 0
                ): ?>


                    <div class="provider-list">


                        <?php
                        foreach (
                            $pending_provider_list
                            as $provider
                        ):
                        ?>


                            <div
                                class="provider-item"
                            >


                                <div
                                    class="provider-left"
                                >


                                    <div
                                        class="provider-avatar"
                                    >

                                        <?php
                                        echo strtoupper(
                                            substr(
                                                $provider[
                                                    'provider_name'
                                                ],
                                                0,
                                                1
                                            )
                                        );
                                        ?>

                                    </div>


                                    <div>


                                        <div
                                            class="provider-name"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $provider[
                                                    'provider_name'
                                                ]
                                            );
                                            ?>

                                        </div>


                                        <div
                                            class="provider-details"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $provider[
                                                    'area'
                                                ]
                                                ?? '-'
                                            );
                                            ?>

                                            ,

                                            <?php
                                            echo htmlspecialchars(
                                                $provider[
                                                    'city'
                                                ]
                                                ?? '-'
                                            );
                                            ?>

                                        </div>


                                    </div>


                                </div>


                                <a
                                    href="providers.php?id=<?php
                                    echo (int)
                                        $provider['id'];
                                    ?>"
                                    class="review-btn"
                                >

                                    Review

                                </a>


                            </div>


                        <?php
                        endforeach;
                        ?>


                    </div>


                <?php else: ?>


                    <div class="empty">

                        No pending providers.

                    </div>


                <?php endif; ?>


            </div>


        </div>



        <!-- =================================
             SYSTEM OVERVIEW
        ================================== -->

        <div class="section">


            <div class="section-header">

                <h2>
                    System Overview
                </h2>

            </div>


            <div class="quick-grid">


                <div class="quick-box">

                    <h3>
                        Total Customers
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $total_customers;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Total Providers
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $total_providers;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Pending Providers
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $pending_providers;
                        ?>

                    </div>

                </div>


                <div class="quick-box">

                    <h3>
                        Total Services
                    </h3>

                    <div class="quick-number">

                        <?php
                        echo $total_services;
                        ?>

                    </div>

                </div>


            </div>


        </div>


    </div>


</main>



<script>

/* =========================================
   MOBILE SIDEBAR
========================================= */

function openMenu() {

    document
        .getElementById("sidebar")
        .classList
        .add("show");

    document
        .getElementById("overlay")
        .classList
        .add("show");
}


function closeMenu() {

    document
        .getElementById("sidebar")
        .classList
        .remove("show");

    document
        .getElementById("overlay")
        .classList
        .remove("show");
}

</script>


<script src="admin-ui.js"></script>
</body>

</html>
