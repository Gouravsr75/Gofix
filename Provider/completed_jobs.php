
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$provider_id = (int) $_SESSION['provider_id'];


/* -----------------------------------------
   Get Completed Jobs
----------------------------------------- */

$jobs = [];

$sql = "SELECT
            b.*,
            s.service_name,
            s.category

        FROM bookings b

        LEFT JOIN services s
        ON b.service_id = s.id

        WHERE b.provider_id = ?

        AND b.booking_status = 'completed'

        ORDER BY b.updated_at DESC,
                 b.id DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "i",
        $provider_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    $stmt->close();
}


/* -----------------------------------------
   Statistics
----------------------------------------- */

$total_completed = count($jobs);

$total_amount = 0;

foreach ($jobs as $job) {
    $total_amount += (float)$job['price'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Completed Jobs</title>


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


/* -----------------------------------------
   Header
----------------------------------------- */

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

    color: #1264d6;

    font-size: 21px;

    font-weight: bold;
}

.provider-name {

    font-size: 14px;

    font-weight: bold;
}


/* -----------------------------------------
   Layout
----------------------------------------- */

.layout {

    display: flex;

    min-height: calc(100vh - 65px);
}


/* -----------------------------------------
   Sidebar
----------------------------------------- */

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


/* -----------------------------------------
   Main
----------------------------------------- */

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


/* -----------------------------------------
   Statistics
----------------------------------------- */

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}

.stat-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 18px;
}

.stat-label {

    color: #777;

    font-size: 13px;

    margin-bottom: 8px;
}

.stat-value {

    color: #1264d6;

    font-size: 27px;

    font-weight: bold;
}


/* -----------------------------------------
   Jobs Grid
----------------------------------------- */

.jobs {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;
}


/* -----------------------------------------
   Job Card
----------------------------------------- */

.job-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 20px;

    box-shadow:
        0 3px 12px rgba(0,0,0,0.04);
}


/* -----------------------------------------
   Card Header
----------------------------------------- */

.card-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 16px;
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

.completed-badge {

    background: #dcfce7;

    color: #166534;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}


/* -----------------------------------------
   Customer
----------------------------------------- */

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

    line-height: 1.6;
}


/* -----------------------------------------
   Details
----------------------------------------- */

.details {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 10px;

    margin-bottom: 15px;
}

.detail {

    border: 1px solid #eeeeee;

    padding: 11px;

    border-radius: 7px;
}

.detail-label {

    font-size: 11px;

    color: #888;

    margin-bottom: 5px;
}

.detail-value {

    font-size: 13px;

    font-weight: bold;
}


/* -----------------------------------------
   Problem
----------------------------------------- */

.problem {

    background: #f8fafc;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 15px;

    font-size: 13px;

    line-height: 1.5;
}

.problem strong {

    display: block;

    margin-bottom: 5px;
}


/* -----------------------------------------
   Payment
----------------------------------------- */

.payment-status {

    padding-top: 14px;

    border-top: 1px solid #eeeeee;

    font-size: 13px;
}

.payment-paid {

    color: #15803d;

    font-weight: bold;
}

.payment-pending {

    color: #946200;

    font-weight: bold;
}

.payment-failed {

    color: #c00000;

    font-weight: bold;
}


/* -----------------------------------------
   Empty
----------------------------------------- */

.empty {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 60px 20px;

    text-align: center;
}

.empty-icon {

    font-size: 42px;

    margin-bottom: 15px;
}

.empty h3 {

    margin-bottom: 7px;
}

.empty p {

    color: #777;

    font-size: 14px;
}


/* -----------------------------------------
   Mobile
----------------------------------------- */

@media (max-width: 950px) {

    .jobs {

        grid-template-columns: 1fr;
    }

}

@media (max-width: 650px) {

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

    .stats {

        grid-template-columns: 1fr;
    }

    .details {

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

    <a href="new_requests.php">
        New Requests
    </a>

    <a href="accepted_jobs.php">
        Accepted Jobs
    </a>

    <a href="rescheduled_jobs.php">
        Rescheduled
    </a>

    <a href="completed_jobs.php"
       class="active">
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

<main class="main">


    <h1 class="page-title">
        Completed Jobs
    </h1>

    <p class="page-subtitle">
        View your complete history of successfully completed services.
    </p>


    <!-- STATISTICS -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-label">
                Total Completed Jobs
            </div>

            <div class="stat-value">
                <?php
                echo $total_completed;
                ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Total Booking Value
            </div>

            <div class="stat-value">

                ₹<?php
                echo number_format(
                    $total_amount,
                    2
                );
                ?>

            </div>

        </div>


    </div>


    <!-- JOBS -->

    <?php if (count($jobs) > 0): ?>


        <div class="jobs">


        <?php foreach ($jobs as $job): ?>


            <div class="job-card">


                <!-- HEADER -->

                <div class="card-header">

                    <div>

                        <div class="service-name">

                            <?php
                            echo htmlspecialchars(
                                $job['service_name'] ??
                                'Service'
                            );
                            ?>

                        </div>

                        <div class="booking-id">

                            Booking ID:
                            <?php
                            echo htmlspecialchars(
                                $job['booking_id']
                            );
                            ?>

                        </div>

                    </div>


                    <span class="completed-badge">
                        Completed
                    </span>

                </div>


                <!-- CUSTOMER -->

                <div class="customer-box">

                    <div class="customer-name">

                        <?php
                        echo htmlspecialchars(
                            $job['customer_name']
                        );
                        ?>

                    </div>


                    <div class="info">

                        📱
                        <?php
                        echo htmlspecialchars(
                            $job['mobile']
                        );
                        ?>

                    </div>


                    <div class="info">

                        📍
                        <?php
                        echo htmlspecialchars(
                            $job['address']
                        );
                        ?>

                    </div>


                    <div class="info">

                        <?php
                        echo htmlspecialchars(
                            $job['village']
                        );
                        ?>

                        <?php if (!empty($job['area'])): ?>

                            ,
                            <?php
                            echo htmlspecialchars(
                                $job['area']
                            );
                            ?>

                        <?php endif; ?>


                        <?php if (!empty($job['city'])): ?>

                            ,
                            <?php
                            echo htmlspecialchars(
                                $job['city']
                            );
                            ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- DETAILS -->

                <div class="details">


                    <div class="detail">

                        <div class="detail-label">
                            Service Date
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $job['booking_date']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            Service Time
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $job['booking_time']
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
                                (float)$job['price'],
                                2
                            );
                            ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            Payment Mode
                        </div>

                        <div class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $job['payment_mode']
                            );
                            ?>

                        </div>

                    </div>


                </div>


                <!-- PROBLEM -->

                <?php if (!empty($job['problem_description'])): ?>

                    <div class="problem">

                        <strong>
                            Service Requirement
                        </strong>

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $job['problem_description']
                            )
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- PAYMENT -->

                <div class="payment-status">

                    Payment Status:

                    <?php

                    $payment_status =
                        $job['payment_status'] ?? 'pending';

                    if ($payment_status === 'paid'):

                    ?>

                        <span class="payment-paid">
                            Paid
                        </span>

                    <?php elseif (
                        $payment_status === 'failed'
                    ): ?>

                        <span class="payment-failed">
                            Failed
                        </span>

                    <?php elseif (
                        $payment_status === 'refunded'
                    ): ?>

                        <span class="payment-failed">
                            Refunded
                        </span>

                    <?php else: ?>

                        <span class="payment-pending">
                            Pending
                        </span>

                    <?php endif; ?>

                </div>


            </div>


        <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                ✅
            </div>

            <h3>
                No Completed Jobs
            </h3>

            <p>
                Your completed service history will appear here.
            </p>

        </div>


    <?php endif; ?>


</main>

</div>


<script src="request-polling.js?v=<?= (string) filemtime(__DIR__ . '/request-polling.js') ?>"></script>
</body>

</html>
