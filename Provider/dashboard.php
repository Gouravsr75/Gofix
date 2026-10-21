
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/request_notifications.php';

$provider_id = (int) $_SESSION['provider_id'];
$provider_name = $_SESSION['provider_name'] ?? "Provider";
syncProviderBookingNotifications($conn, $provider_id);

/* -------------------------------------------------
   Booking Counts
------------------------------------------------- */

function getCount($conn, $provider_id, $status)
{
    $sql = "SELECT COUNT(*) AS total
            FROM bookings
            WHERE provider_id = ?
            AND booking_status = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("is", $provider_id, $status);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row['total'] ?? 0);
}


/* Counts */

$new_requests = 0;
$new_requests_latest_id = 0;
$new_request_count_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total, COALESCE(MAX(pn.id), 0) AS latest_id
     FROM provider_notifications pn
     INNER JOIN bookings b ON b.id = pn.booking_id
     WHERE pn.provider_id = ?
       AND pn.notification_type = 'new_booking'
       AND pn.response_status = 'pending'
       AND (b.provider_id IS NULL OR b.provider_id = pn.provider_id)
       AND b.booking_status IN ('pending', 'provider_notified')"
);

if ($new_request_count_stmt) {
    $new_request_count_stmt->bind_param("i", $provider_id);
    $new_request_count_stmt->execute();
    $new_request_count_result = $new_request_count_stmt->get_result();
    $new_request_count_row = $new_request_count_result->fetch_assoc();
    $new_requests = (int)($new_request_count_row['total'] ?? 0);
    $new_requests_latest_id = (int)($new_request_count_row['latest_id'] ?? 0);
    $new_request_count_stmt->close();
}

$accepted = getCount(
    $conn,
    $provider_id,
    "accepted"
);

$rescheduled = getCount(
    $conn,
    $provider_id,
    "rescheduled"
);

$on_the_way = getCount(
    $conn,
    $provider_id,
    "on_the_way"
);

$work_started = getCount(
    $conn,
    $provider_id,
    "work_started"
);

$completed = getCount(
    $conn,
    $provider_id,
    "completed"
);

$rejected = getCount(
    $conn,
    $provider_id,
    "rejected"
);


/* Total jobs */

$total_jobs = 0;

$sql = "SELECT COUNT(*) AS total
        FROM bookings
        WHERE provider_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $provider_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_jobs = (int)($row['total'] ?? 0);

    $stmt->close();
}


/* -------------------------------------------------
   Recent Booking Requests
------------------------------------------------- */

$recent_requests = [];

$sql = "SELECT
            b.id,
            b.booking_id,
            b.customer_name,
            b.mobile,
            b.area,
            b.city,
            b.booking_date,
            b.booking_time,
            b.price,
            b.booking_status,
            b.created_at,
            s.service_name

        FROM provider_notifications pn

        INNER JOIN bookings b
            ON pn.booking_id = b.id

        LEFT JOIN services s
            ON b.service_id = s.id

        WHERE pn.provider_id = ?
          AND pn.notification_type = 'new_booking'
          AND pn.response_status = 'pending'
          AND (b.provider_id IS NULL OR b.provider_id = pn.provider_id)
          AND b.booking_status IN ('pending', 'provider_notified')

        ORDER BY pn.created_at DESC

        LIMIT 8";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $provider_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recent_requests[] = $row;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Provider Dashboard</title>
<script src="history-lock.js?v=<?= (string) filemtime(__DIR__ . '/history-lock.js') ?>"></script>

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


/* -------------------------
   Header
------------------------- */

.header {
    height: 65px;

    background: #ffffff;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 25px;

    position: sticky;

    top: 0;

    z-index: 1000;
}

.logo {
    font-size: 21px;

    font-weight: bold;

    color: #1264d6;
}

.provider-info {
    display: flex;

    align-items: center;

    gap: 12px;
}

.provider-avatar {
    width: 40px;

    height: 40px;

    border-radius: 50%;

    overflow: hidden;

    background: #1264d6;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}

.provider-avatar-photo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.provider-name {
    font-size: 14px;

    font-weight: bold;
}


/* -------------------------
   Layout
------------------------- */

.layout {
    display: flex;

    min-height: calc(100vh - 65px);
}


/* -------------------------
   Sidebar
------------------------- */

.sidebar {
    width: 230px;

    background: #ffffff;

    border-right: 1px solid #e5e7eb;

    padding: 20px 12px;
}

.sidebar a {
    display: block;

    padding: 12px 14px;

    margin-bottom: 5px;

    color: #444;

    text-decoration: none;

    border-radius: 8px;

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


/* -------------------------
   Main
------------------------- */

.main {
    flex: 1;

    padding: 25px;

    overflow-x: hidden;
}

.page-title {
    margin-bottom: 5px;

    font-size: 25px;
}

.welcome {
    color: #777;

    font-size: 14px;

    margin-bottom: 25px;
}


/* -------------------------
   Stats
------------------------- */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 25px;
}

.stat-card {
    background: #ffffff;

    border: 1px solid #e8ebef;

    border-radius: 12px;

    padding: 20px;
}

.stat-title {
    font-size: 13px;

    color: #777;

    margin-bottom: 8px;
}

.stat-number {
    font-size: 28px;

    font-weight: bold;

    color: #1264d6;
}


/* -------------------------
   Quick Actions
------------------------- */

.section-title {
    font-size: 19px;

    margin-bottom: 15px;
}

.quick-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 12px;

    margin-bottom: 30px;
}

.quick-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    padding: 17px;

    border-radius: 10px;

    text-decoration: none;

    color: #222;

    font-size: 14px;
}

.quick-card:hover {
    border-color: #1264d6;

    color: #1264d6;
}


/* -------------------------
   Table
------------------------- */

.table-box {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 800px;
}

th,
td {
    padding: 13px 15px;

    border-bottom: 1px solid #eeeeee;

    text-align: left;

    font-size: 13px;
}

th {
    background: #f8fafc;

    color: #555;
}

tr:last-child td {
    border-bottom: none;
}


/* -------------------------
   Status
------------------------- */

.status {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;
}

.status-pending,
.status-provider_notified {
    background: #fff4d6;

    color: #9a6800;
}

.status-accepted {
    background: #e7f7ed;

    color: #15803d;
}

.status-rescheduled {
    background: #eee9ff;

    color: #6d28d9;
}

.status-on_the_way {
    background: #e5f3ff;

    color: #1264d6;
}

.status-work_started {
    background: #e5f3ff;

    color: #1264d6;
}

.status-completed {
    background: #dcfce7;

    color: #166534;
}

.status-rejected,
.status-cancelled {
    background: #ffe5e5;

    color: #c00000;
}


/* -------------------------
   Mobile
------------------------- */

@media (max-width: 900px) {

    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .quick-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .sidebar {
        width: 190px;
    }

}

@media (max-width: 650px) {

    .header {
        padding: 0 15px;
    }

    .provider-name {
        display: none;
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

    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);

        gap: 10px;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-number {
        font-size: 23px;
    }

    .quick-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

</style>
<link rel="stylesheet" href="provider-theme.css?v=<?= filemtime(__DIR__ . '/provider-theme.css') ?>">
</head>
<body class="provider-app-page">


<!-- Header -->

<header class="header">

    <div class="logo">
        Gofix Provider
    </div>

    <div class="provider-info">

        <div class="provider-avatar">
            <?php if (!empty($_SESSION['provider_photo_available'])): ?>
                <img
                    class="provider-avatar-photo"
                    src="profile_photo.php"
                    alt="Provider profile photo"
                >
            <?php else: ?>
                <?php echo htmlspecialchars(strtoupper(substr($provider_name, 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
        </div>

        <div class="provider-name">
            <?php
            echo htmlspecialchars($provider_name);
            ?>
        </div>

    </div>

</header>


<div class="layout">


<!-- Sidebar -->

<aside class="sidebar">

    <a href="dashboard.php"
       class="active">
        Dashboard
    </a>

    <a href="new_requests.php">
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


<!-- Main Content -->

<main class="main">

    <h1 class="page-title">
        Dashboard
    </h1>

    <p class="welcome">
        Welcome,
        <?php echo htmlspecialchars($provider_name); ?>
    </p>


    <!-- Statistics -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-title">
                New Requests
            </div>

            <div class="stat-number" id="newRequestCount"
                 data-request-count="<?php echo $new_requests; ?>"
                 data-request-latest="<?php echo $new_requests_latest_id; ?>">
                <?php echo $new_requests; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Accepted Jobs
            </div>

            <div class="stat-number">
                <?php echo $accepted; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Rescheduled
            </div>

            <div class="stat-number">
                <?php echo $rescheduled; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Completed
            </div>

            <div class="stat-number">
                <?php echo $completed; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                On The Way
            </div>

            <div class="stat-number">
                <?php echo $on_the_way; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Work Started
            </div>

            <div class="stat-number">
                <?php echo $work_started; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Rejected
            </div>

            <div class="stat-number">
                <?php echo $rejected; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Total Jobs
            </div>

            <div class="stat-number">
                <?php echo $total_jobs; ?>
            </div>

        </div>

    </div>


    <!-- Quick Actions -->

    <h2 class="section-title">
        Quick Actions
    </h2>

    <div class="quick-grid">

        <a href="new_requests.php"
           class="quick-card">

            View New Requests

        </a>

        <a href="accepted_jobs.php"
           class="quick-card">

            Accepted Jobs

        </a>

        <a href="rescheduled_jobs.php"
           class="quick-card">

            Rescheduled Jobs

        </a>

        <a href="completed_jobs.php"
           class="quick-card">

            Completed Jobs

        </a>

    </div>


    <!-- Recent Bookings -->

    <h2 class="section-title">
        Recent Bookings
    </h2>

    <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>Booking ID</th>

                    <th>Service</th>

                    <th>Customer</th>

                    <th>Mobile</th>

                    <th>Date</th>

                    <th>Time</th>

                    <th>Price</th>

                    <th>Status</th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($recent_requests) > 0): ?>

                <?php foreach ($recent_requests as $booking): ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['booking_id']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['service_name'] ?? 'Service'
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['customer_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['mobile']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['booking_date']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking['booking_time']
                            );
                            ?>
                        </td>

                        <td>
                            ₹<?php
                            echo number_format(
                                (float)$booking['price'],
                                2
                            );
                            ?>
                        </td>

                        <td>

                            <span class="status status-<?php
                                echo htmlspecialchars(
                                    $booking['booking_status']
                                );
                            ?>">

                                <?php
                                echo ucwords(
                                    str_replace(
                                        "_",
                                        " ",
                                        $booking['booking_status']
                                    )
                                );
                                ?>

                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8"
                        style="text-align:center;padding:30px;color:#777;">

                        No bookings available.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</div>

<script src="request-polling.js?v=<?= (string) filemtime(__DIR__ . '/request-polling.js') ?>"></script>
</body>
</html>
