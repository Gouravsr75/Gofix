
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$provider_id = (int) $_SESSION['provider_id'];

$success = "";
$error = "";


/* -----------------------------------------
   Update Job Status
----------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";
    $booking_db_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_db_id <= 0) {

        $error = "Invalid booking.";

    } else {

        /* Get booking and verify provider */

        $sql = "SELECT *
                FROM bookings
                WHERE id = ?
                AND provider_id = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Database error.";

        } else {

            $stmt->bind_param(
                "ii",
                $booking_db_id,
                $provider_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {

                $error = "Booking not found.";

            } else {

                $booking = $result->fetch_assoc();

                $old_status =
                    $booking['booking_status'];

                $new_status = "";
                $note = "";


                /* ---------------------------------
                   ON THE WAY
                --------------------------------- */

                if ($action === "on_the_way") {

                    if ($old_status !== "accepted") {

                        $error =
                            "This booking cannot be moved to On The Way.";

                    } else {

                        $new_status = "on_the_way";

                        $note =
                            "Provider is on the way to customer.";
                    }
                }


                /* ---------------------------------
                   WORK STARTED
                --------------------------------- */

                elseif ($action === "work_started") {

                    if ($old_status !== "on_the_way") {

                        $error =
                            "Work can only be started after On The Way status.";

                    } else {

                        $new_status = "work_started";

                        $note =
                            "Provider has started the service.";
                    }
                }


                /* ---------------------------------
                   COMPLETED
                --------------------------------- */

                elseif ($action === "completed") {

                    if ($old_status !== "work_started") {

                        $error =
                            "Job can only be completed after work has started.";

                    } else {

                        $new_status = "completed";

                        $note =
                            "Service completed successfully.";
                    }
                }


                /* ---------------------------------
                   UPDATE DATABASE
                --------------------------------- */

                if (
                    $error === "" &&
                    $new_status !== ""
                ) {

                    $update = $conn->prepare(
                        "UPDATE bookings
                         SET booking_status = ?
                         WHERE id = ?
                         AND provider_id = ?"
                    );

                    if (!$update) {

                        $error =
                            "Unable to update booking.";

                    } else {

                        $update->bind_param(
                            "sii",
                            $new_status,
                            $booking_db_id,
                            $provider_id
                        );


                        if ($update->execute()) {

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
                                VALUES (?, ?, ?, 'provider', ?, ?)"
                            );

                            if ($history) {

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
                            }


                            /* ---------------------------------
                               Provider Notification
                            --------------------------------- */

                            $notification = $conn->prepare(
                                "INSERT INTO provider_notifications
                                (
                                    provider_id,
                                    booking_id,
                                    notification_type,
                                    message
                                )
                                VALUES (?, ?, 'booking_updated', ?)"
                            );

                            if ($notification) {

                                $message =
                                    "Booking " .
                                    $booking['booking_id'] .
                                    " status updated to " .
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $new_status
                                        )
                                    ) .
                                    ".";

                                $notification->bind_param(
                                    "iis",
                                    $provider_id,
                                    $booking_db_id,
                                    $message
                                );

                                $notification->execute();

                                $notification->close();
                            }


                            $success =
                                "Booking status updated successfully.";

                        } else {

                            $error =
                                "Unable to update booking.";
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
   Get Provider Jobs
----------------------------------------- */

$jobs = [];

$sql = "SELECT
            b.*,
            s.service_name,
            s.category,
            s.image AS service_image

        FROM bookings b

        LEFT JOIN services s
        ON b.service_id = s.id

        WHERE b.provider_id = ?

        AND b.booking_status IN
        (
            'accepted',
            'on_the_way',
            'work_started'
        )

        ORDER BY b.booking_date ASC,
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Accepted Jobs</title>


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
   Messages
----------------------------------------- */

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


/* -----------------------------------------
   Status
----------------------------------------- */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}

.status-accepted {

    background: #e7f7ed;

    color: #15803d;
}

.status-on_the_way {

    background: #e5f3ff;

    color: #1264d6;
}

.status-work_started {

    background: #fff4d6;

    color: #946200;
}


/* -----------------------------------------
   Customer Box
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
   Action
----------------------------------------- */

.action-box {

    border-top: 1px solid #eeeeee;

    padding-top: 15px;
}

.action-label {

    font-size: 12px;

    color: #777;

    margin-bottom: 9px;
}

.actions {

    display: flex;

    gap: 8px;

    flex-wrap: wrap;
}

.btn {

    border: none;

    padding: 10px 14px;

    border-radius: 7px;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;
}

.btn-primary {

    background: #1264d6;

    color: white;
}

.btn-secondary {

    background: #eaf2ff;

    color: #1264d6;
}

.btn-success {

    background: #15803d;

    color: white;
}

.btn:hover {

    opacity: 0.9;
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

    .details {

        grid-template-columns: 1fr;
    }

    .actions {

        flex-direction: column;
    }

    .btn {

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

    <a href="accepted_jobs.php"
       class="active">
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

<main class="main">

    <h1 class="page-title">
        Accepted Jobs
    </h1>

    <p class="page-subtitle">
        Manage your accepted service bookings and update their progress.
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


                    <span class="status status-<?php
                        echo htmlspecialchars(
                            $job['booking_status']
                        );
                    ?>">

                        <?php
                        echo ucwords(
                            str_replace(
                                "_",
                                " ",
                                $job['booking_status']
                            )
                        );
                        ?>

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
                            Booking Date
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
                            Booking Time
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
                            Price
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
                            Payment
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
                            Problem / Requirement
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


                <!-- ACTIONS -->

                <div class="action-box">

                    <div class="action-label">
                        Update Job Status
                    </div>


                    <div class="actions">


                        <?php if (
                            $job['booking_status'] ===
                            'accepted'
                        ): ?>


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo (int)$job['id'];
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="on_the_way"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    On The Way
                                </button>

                            </form>


                        <?php elseif (
                            $job['booking_status'] ===
                            'on_the_way'
                        ): ?>


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo (int)$job['id'];
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="work_started"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Start Work
                                </button>

                            </form>


                        <?php elseif (
                            $job['booking_status'] ===
                            'work_started'
                        ): ?>


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo (int)$job['id'];
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="completed"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                    onclick="return confirm('Mark this job as completed?');"
                                >
                                    Mark Completed
                                </button>

                            </form>


                        <?php endif; ?>


                    </div>

                </div>


            </div>


        <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                🔧
            </div>

            <h3>
                No Active Jobs
            </h3>

            <p>
                You don't have any accepted or ongoing jobs right now.
            </p>

        </div>


    <?php endif; ?>


</main>

</div>

<script src="request-polling.js?v=<?= (string) filemtime(__DIR__ . '/request-polling.js') ?>"></script>
</body>

</html>
