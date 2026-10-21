
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$provider_id = (int) $_SESSION['provider_id'];


/* -----------------------------------------
   Get Rejected Jobs
----------------------------------------- */

$rejected_jobs = [];

$sql = "SELECT
            b.*,
            s.service_name,
            s.category

        FROM bookings b

        LEFT JOIN services s
        ON b.service_id = s.id

        WHERE b.provider_id = ?
        AND b.booking_status = 'rejected'

        ORDER BY b.updated_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "i",
        $provider_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $rejected_jobs[] = $row;
    }

    $stmt->close();
}


/* -----------------------------------------
   Total Rejected
----------------------------------------- */

$total_rejected = count($rejected_jobs);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Rejected Jobs</title>


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

    font-size: 21px;

    font-weight: bold;

    color: #1264d6;
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

    max-width: 1400px;
}

.page-title {

    font-size: 25px;

    margin-bottom: 5px;
}

.page-subtitle {

    color: #777;

    font-size: 14px;

    margin-bottom: 22px;
}


/* -----------------------------------------
   Summary
----------------------------------------- */

.summary-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 20px;

    width: 250px;

    margin-bottom: 25px;
}

.summary-title {

    color: #777;

    font-size: 13px;

    margin-bottom: 8px;
}

.summary-value {

    color: #1264d6;

    font-size: 27px;

    font-weight: bold;
}


/* -----------------------------------------
   Table
----------------------------------------- */

.table-box {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;
}

th {

    background: #f8fafc;

    color: #555;

    font-size: 13px;

    font-weight: bold;

    padding: 14px 15px;

    text-align: left;

    border-bottom: 1px solid #e5e7eb;
}

td {

    padding: 14px 15px;

    font-size: 13px;

    border-bottom: 1px solid #eeeeee;

    vertical-align: top;
}

tr:last-child td {

    border-bottom: none;
}


/* -----------------------------------------
   Booking ID
----------------------------------------- */

.booking-id {

    color: #1264d6;

    font-weight: bold;
}


/* -----------------------------------------
   Customer
----------------------------------------- */

.customer-name {

    font-weight: bold;

    margin-bottom: 5px;
}

.customer-mobile {

    color: #777;

    font-size: 12px;
}


/* -----------------------------------------
   Service
----------------------------------------- */

.service-name {

    font-weight: bold;

    margin-bottom: 4px;
}

.category {

    color: #777;

    font-size: 11px;
}


/* -----------------------------------------
   Address
----------------------------------------- */

.address {

    max-width: 230px;

    color: #555;

    line-height: 1.5;
}


/* -----------------------------------------
   Price
----------------------------------------- */

.price {

    font-weight: bold;

    color: #222;
}


/* -----------------------------------------
   Status
----------------------------------------- */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    background: #ffe5e5;

    color: #c00000;

    font-size: 11px;

    font-weight: bold;
}


/* -----------------------------------------
   Payment
----------------------------------------- */

.payment {

    font-size: 12px;

    color: #555;
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

@media (max-width: 700px) {

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

    .summary-card {

        width: 100%;
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

    <a href="completed_jobs.php">
        Completed
    </a>

    <a href="rejected_jobs.php"
       class="active">
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
        Rejected Jobs
    </h1>

    <p class="page-subtitle">
        View booking requests that you have rejected.
    </p>


    <!-- SUMMARY -->

    <div class="summary-card">

        <div class="summary-title">
            Total Rejected Jobs
        </div>

        <div class="summary-value">
            <?php
            echo $total_rejected;
            ?>
        </div>

    </div>


    <!-- JOBS -->

    <?php if ($total_rejected > 0): ?>


        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>
                            Booking ID
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Address
                        </th>

                        <th>
                            Booking Date
                        </th>

                        <th>
                            Booking Time
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($rejected_jobs as $job): ?>


                    <tr>


                        <!-- Booking ID -->

                        <td>

                            <div class="booking-id">

                                <?php
                                echo htmlspecialchars(
                                    $job['booking_id']
                                );
                                ?>

                            </div>

                        </td>


                        <!-- Service -->

                        <td>

                            <div class="service-name">

                                <?php
                                echo htmlspecialchars(
                                    $job['service_name']
                                    ?? 'Service'
                                );
                                ?>

                            </div>

                            <div class="category">

                                <?php
                                echo htmlspecialchars(
                                    $job['category']
                                    ?? ''
                                );
                                ?>

                            </div>

                        </td>


                        <!-- Customer -->

                        <td>

                            <div class="customer-name">

                                <?php
                                echo htmlspecialchars(
                                    $job['customer_name']
                                );
                                ?>

                            </div>

                            <div class="customer-mobile">

                                <?php
                                echo htmlspecialchars(
                                    $job['mobile']
                                );
                                ?>

                            </div>

                        </td>


                        <!-- Address -->

                        <td>

                            <div class="address">

                                <?php
                                echo htmlspecialchars(
                                    $job['address']
                                );
                                ?>


                                <?php if (!empty($job['village'])): ?>

                                    <br>

                                    <?php
                                    echo htmlspecialchars(
                                        $job['village']
                                    );
                                    ?>

                                <?php endif; ?>


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


                                <?php if (!empty($job['pincode'])): ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $job['pincode']
                                    );
                                    ?>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- Date -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $job['booking_date']
                            );
                            ?>

                        </td>


                        <!-- Time -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $job['booking_time']
                            );
                            ?>

                        </td>


                        <!-- Amount -->

                        <td>

                            <div class="price">

                                ₹<?php
                                echo number_format(
                                    (float)$job['price'],
                                    2
                                );
                                ?>

                            </div>

                        </td>


                        <!-- Payment -->

                        <td>

                            <div class="payment">

                                <?php
                                echo htmlspecialchars(
                                    $job['payment_mode']
                                );
                                ?>

                                <br>

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $job['payment_status']
                                    )
                                );
                                ?>

                            </div>

                        </td>


                        <!-- Status -->

                        <td>

                            <span class="status">
                                Rejected
                            </span>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                ✓
            </div>

            <h3>
                No Rejected Jobs
            </h3>

            <p>
                Booking requests that you reject will appear here.
            </p>

        </div>


    <?php endif; ?>


</main>

</div>


<script src="request-polling.js?v=<?= (string) filemtime(__DIR__ . '/request-polling.js') ?>"></script>
</body>

</html>
