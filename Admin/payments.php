
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan payment records aur payment status manage hota hai.

$admin_name = $_SESSION['admin_name'] ?? "Administrator";

$message = "";
$message_type = "success";


/* =========================
   UPDATE PAYMENT STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";

    if ($action === "change_payment_status") {

        $payment_id = (int)($_POST['payment_id'] ?? 0);
        $new_status = $_POST['payment_status'] ?? "";

        $allowed_status = [
            "pending",
            "paid",
            "failed",
            "refunded"
        ];

        if ($payment_id <= 0) {

            $message = "Invalid payment.";
            $message_type = "error";

        } elseif (!in_array($new_status, $allowed_status, true)) {

            $message = "Invalid payment status.";
            $message_type = "error";

        } else {

            /*
             * Get payment booking ID and amount
             */
            $stmt = $conn->prepare(
                "SELECT booking_id, amount
                 FROM payments
                 WHERE id = ?"
            );

            $booking_id = 0;
            $amount = 0;

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $payment_id
                );

                $stmt->execute();

                $payment_result =
                    $stmt->get_result();

                $payment_data =
                    $payment_result->fetch_assoc();

                if ($payment_data) {

                    $booking_id =
                        (int)$payment_data['booking_id'];

                    $amount =
                        (float)$payment_data['amount'];
                }

                $stmt->close();
            }


            if ($booking_id <= 0) {

                $message = "Payment record not found.";
                $message_type = "error";

            } else {

                $paid_at = null;

                if ($new_status === "paid") {
                    $paid_at = date("Y-m-d H:i:s");
                }


                $stmt = $conn->prepare(
                    "UPDATE payments
                     SET
                        payment_status = ?,
                        paid_at = ?
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "ssi",
                        $new_status,
                        $paid_at,
                        $payment_id
                    );

                    if ($stmt->execute()) {

                        /*
                         * Keep booking payment_status
                         * synchronized.
                         */
                        $booking_payment_status =
                            $new_status;

                        $stmt2 = $conn->prepare(
                            "UPDATE bookings
                             SET payment_status = ?
                             WHERE id = ?"
                        );

                        if ($stmt2) {

                            $stmt2->bind_param(
                                "si",
                                $booking_payment_status,
                                $booking_id
                            );

                            $stmt2->execute();

                            $stmt2->close();
                        }


                        $message =
                            "Payment status updated successfully.";

                    } else {

                        $message =
                            "Unable to update payment.";

                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    $message =
                        "Database error.";

                    $message_type = "error";
                }
            }
        }
    }
}


/* =========================
   SEARCH / FILTER
========================= */

$search =
    trim($_GET['search'] ?? "");

$status_filter =
    $_GET['status'] ?? "";

$date_filter =
    $_GET['date'] ?? "";

$where = [];
$params = [];
$types = "";


/* =========================
   SEARCH
========================= */

if ($search !== "") {

    $where[] = "(
        p.transaction_id LIKE ?
        OR b.booking_id LIKE ?
        OR b.customer_name LIKE ?
        OR b.mobile LIKE ?
        OR s.service_name LIKE ?
        OR u.full_name LIKE ?
    )";

    $value =
        "%" . $search . "%";

    for ($i = 0; $i < 6; $i++) {

        $params[] = $value;
        $types .= "s";
    }
}


/* =========================
   STATUS
========================= */

if (
    in_array(
        $status_filter,
        [
            "pending",
            "paid",
            "failed",
            "refunded"
        ],
        true
    )
) {

    $where[] =
        "p.payment_status = ?";

    $params[] =
        $status_filter;

    $types .= "s";
}


/* =========================
   DATE
========================= */

if ($date_filter !== "") {

    /*
     * Date input comes as YYYY-MM-DD
     */
    $date_object =
        DateTime::createFromFormat(
            "Y-m-d",
            $date_filter
        );

    if (
        $date_object &&
        $date_object->format("Y-m-d") ===
        $date_filter
    ) {

        $where[] =
            "DATE(p.created_at) = ?";

        $params[] =
            $date_filter;

        $types .= "s";
    }
}


/* =========================
   PAYMENT QUERY
========================= */

$sql = "
    SELECT

        p.id AS payment_id,
        p.booking_id,
        p.payment_method,
        p.amount,
        p.payment_status,
        p.transaction_id,
        p.paid_at,
        p.created_at AS payment_created_at,

        b.id AS booking_db_id,
        b.booking_id AS booking_code,
        b.customer_name,
        b.mobile,
        b.email,
        b.booking_date,
        b.booking_time,
        b.booking_status,

        s.service_name,
        s.category,

        sp.id AS provider_id,
        sp.provider_name,
        sp.mobile AS provider_mobile,

        u.full_name AS user_full_name

    FROM payments p

    INNER JOIN bookings b
        ON p.booking_id = b.id

    LEFT JOIN services s
        ON b.service_id = s.id

    LEFT JOIN service_providers sp
        ON b.provider_id = sp.id

    LEFT JOIN users u
        ON b.user_id = u.id
";


if (!empty($where)) {

    $sql .=
        " WHERE " .
        implode(
            " AND ",
            $where
        );
}


$sql .=
    " ORDER BY p.id DESC";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "SQL Prepare Error: " .
        $conn->error
    );
}


/* =========================
   BIND PARAMETERS
========================= */

if (!empty($params)) {

    $bind_names = [];

    $bind_names[] =
        $types;

    foreach (
        $params as $key => $value
    ) {

        $bind_names[] =
            &$params[$key];
    }

    call_user_func_array(
        [$stmt, "bind_param"],
        $bind_names
    );
}


$stmt->execute();

$result =
    $stmt->get_result();


/* =========================
   PAYMENT STATISTICS
========================= */

$stats_result =
    $conn->query(
        "SELECT

            COUNT(*) AS total,

            SUM(
                payment_status = 'pending'
            ) AS pending,

            SUM(
                payment_status = 'paid'
            ) AS paid,

            SUM(
                payment_status = 'failed'
            ) AS failed,

            SUM(
                payment_status = 'refunded'
            ) AS refunded,

            COALESCE(
                SUM(
                    CASE
                        WHEN payment_status = 'paid'
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS collected

         FROM payments"
    );


$stats = [
    "total" => 0,
    "pending" => 0,
    "paid" => 0,
    "failed" => 0,
    "refunded" => 0,
    "collected" => 0
];


if ($stats_result) {

    $stats_data =
        $stats_result->fetch_assoc();

    $stats['total'] =
        (int)($stats_data['total'] ?? 0);

    $stats['pending'] =
        (int)($stats_data['pending'] ?? 0);

    $stats['paid'] =
        (int)($stats_data['paid'] ?? 0);

    $stats['failed'] =
        (int)($stats_data['failed'] ?? 0);

    $stats['refunded'] =
        (int)($stats_data['refunded'] ?? 0);

    $stats['collected'] =
        (float)($stats_data['collected'] ?? 0);
}


/* =========================
   STATUS HELPER
========================= */

function paymentStatusClass($status)
{
    return "payment-" .
        str_replace(
            "_",
            "-",
            strtolower($status)
        );
}


function paymentStatusText($status)
{
    return ucwords(
        str_replace(
            "_",
            " ",
            $status
        )
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Payments - Admin</title>


<style>

/* =========================
   RESET
========================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f7fb;

    color: #172033;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 240px;
    height: 100vh;

    background: #0f172a;

    padding: 22px 15px;

    overflow-y: auto;

    z-index: 1000;
}


.logo {

    color: white;

    font-size: 22px;

    font-weight: 700;

    padding:
        0 10px 25px;
}


.logo span {
    color: #1264d6;
}


.menu-title {

    color: #64748b;

    font-size: 11px;

    text-transform: uppercase;

    padding:
        15px 10px 8px;
}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #cbd5e1;

    text-decoration: none;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 4px;

    font-size: 14px;
}


.sidebar a:hover,
.sidebar a.active {

    background: #1264d6;

    color: white;
}


.logout-link {
    margin-top: 15px;
}


/* =========================
   MOBILE HEADER
========================= */

.mobile-top {

    display: none;

    height: 58px;

    background: #0f172a;

    color: white;

    padding: 0 15px;

    align-items: center;

    justify-content: space-between;
}


.menu-toggle {

    border: none;

    background: #1264d6;

    color: white;

    width: 40px;

    height: 40px;

    border-radius: 7px;

    font-size: 20px;

    cursor: pointer;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 240px;

    padding: 25px;
}


/* =========================
   TOPBAR
========================= */

.topbar {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}


.page-title h1 {

    font-size: 27px;

    margin-bottom: 5px;
}


.page-title p {

    color: #64748b;

    font-size: 14px;
}


.admin-name {

    background: white;

    padding: 10px 15px;

    border-radius: 8px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.05);

    font-size: 14px;
}


/* =========================
   ALERT
========================= */

.alert {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
}


.alert.success {

    background: #dcfce7;

    color: #166534;
}


.alert.error {

    background: #fee2e2;

    color: #991b1b;
}


/* =========================
   STATS
========================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.stat-card {

    background: white;

    border-radius: 12px;

    padding: 18px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.05);
}


.stat-label {

    color: #64748b;

    font-size: 13px;

    margin-bottom: 8px;
}


.stat-number {

    font-size: 26px;

    font-weight: 700;
}


.stat-extra {

    color: #64748b;

    font-size: 12px;

    margin-top: 5px;
}


/* =========================
   FILTER
========================= */

.filter-box {

    background: white;

    padding: 18px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.05);

    margin-bottom: 20px;
}


.filter-form {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


.filter-form input,
.filter-form select {

    height: 42px;

    border: 1px solid #dbe1ea;

    border-radius: 7px;

    padding: 0 12px;

    font-size: 14px;

    outline: none;
}


.filter-form input {

    flex: 1;

    min-width: 230px;
}


.filter-form input:focus,
.filter-form select:focus {

    border-color: #1264d6;
}


.btn {

    height: 42px;

    padding: 0 18px;

    border: none;

    border-radius: 7px;

    cursor: pointer;

    font-size: 14px;

    font-weight: 600;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}


.btn-primary {

    background: #1264d6;

    color: white;
}


.btn-light {

    background: #e9eef5;

    color: #172033;
}


/* =========================
   TABLE
========================= */

.table-box {

    background: white;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.05);

    overflow: hidden;
}


.table-header {

    padding: 18px;

    border-bottom:
        1px solid #edf0f5;

    font-size: 17px;

    font-weight: 700;
}


.table-scroll {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1200px;
}


th {

    background: #f8fafc;

    color: #64748b;

    font-size: 12px;

    text-align: left;

    padding: 13px 15px;

    text-transform: uppercase;
}


td {

    padding: 14px 15px;

    border-top:
        1px solid #edf0f5;

    font-size: 13px;

    vertical-align: middle;
}


/* =========================
   BOOKING
========================= */

.booking-id {

    color: #1264d6;

    font-weight: 700;

    margin-bottom: 4px;
}


.booking-date {

    color: #64748b;

    font-size: 12px;
}


/* =========================
   CUSTOMER
========================= */

.customer-name {

    font-weight: 700;

    margin-bottom: 4px;
}


.customer-mobile {

    color: #64748b;

    font-size: 12px;
}


/* =========================
   SERVICE
========================= */

.service-name {

    font-weight: 700;

    margin-bottom: 4px;
}


.category {

    display: inline-block;

    background: #e0edff;

    color: #1264d6;

    padding: 4px 8px;

    border-radius: 15px;

    font-size: 10px;

    font-weight: 600;
}


/* =========================
   PAYMENT STATUS
========================= */

.payment-status {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;
}


.payment-pending {

    background: #fef3c7;

    color: #92400e;
}


.payment-paid {

    background: #dcfce7;

    color: #166534;
}


.payment-failed {

    background: #fee2e2;

    color: #991b1b;
}


.payment-refunded {

    background: #e0e7ff;

    color: #3730a3;
}


/* =========================
   PAYMENT METHOD
========================= */

.cod {

    display: inline-block;

    background: #f1f5f9;

    color: #475569;

    padding: 5px 8px;

    border-radius: 5px;

    font-size: 11px;

    font-weight: 600;
}


/* =========================
   ACTIONS
========================= */

.action-buttons {

    display: flex;

    gap: 6px;

    flex-wrap: wrap;
}


.action-btn {

    border: none;

    padding: 7px 10px;

    border-radius: 6px;

    cursor: pointer;

    font-size: 11px;

    font-weight: 600;
}


.view-btn {

    background: #e0edff;

    color: #1264d6;
}


.paid-btn {

    background: #dcfce7;

    color: #166534;
}


.pending-btn {

    background: #fef3c7;

    color: #92400e;
}


.failed-btn {

    background: #fee2e2;

    color: #991b1b;
}


.refund-btn {

    background: #e0e7ff;

    color: #3730a3;
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #64748b;
}


/* =========================
   MODAL
========================= */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(15,23,42,.65);

    z-index: 3000;

    padding: 20px;

    align-items: center;

    justify-content: center;
}


.modal.show {

    display: flex;
}


.modal-box {

    background: white;

    width: 100%;

    max-width: 650px;

    max-height: 90vh;

    overflow-y: auto;

    border-radius: 14px;

    padding: 25px;

    position: relative;
}


.modal-close {

    position: absolute;

    right: 18px;

    top: 15px;

    width: 32px;

    height: 32px;

    border: none;

    border-radius: 50%;

    background: #f1f5f9;

    cursor: pointer;

    font-size: 18px;
}


.modal-title {

    font-size: 22px;

    margin-bottom: 20px;

    padding-right: 40px;
}


.detail-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 14px;
}


.detail-item {

    background: #f8fafc;

    padding: 12px;

    border-radius: 8px;
}


.detail-item.full {

    grid-column: 1 / -1;
}


.detail-label {

    color: #64748b;

    font-size: 11px;

    text-transform: uppercase;

    margin-bottom: 5px;
}


.detail-value {

    font-size: 14px;

    font-weight: 600;

    word-break: break-word;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 768px) {

    .mobile-top {

        display: flex;

        position: sticky;

        top: 0;

        z-index: 2000;
    }


    .sidebar {

        left: -250px;

        transition: .25s;
    }


    .sidebar.open {

        left: 0;
    }


    .main {

        margin-left: 0;

        padding: 15px;
    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;
    }


    .admin-name {

        display: none;
    }


    .filter-form {

        flex-direction: column;
    }


    .filter-form input {

        min-width: 100%;
    }


    .filter-form select,
    .filter-form button,
    .filter-form a {

        width: 100%;
    }


    .detail-grid {

        grid-template-columns: 1fr;
    }


    .detail-item.full {

        grid-column: auto;
    }
}


@media (max-width: 450px) {

    .stats {

        grid-template-columns:
            1fr 1fr;

        gap: 10px;
    }


    .stat-card {

        padding: 14px;
    }


    .stat-number {

        font-size: 21px;
    }


    .page-title h1 {

        font-size: 23px;
    }
}

</style>

    <link rel="stylesheet" href="admin-ui.css">
</head>


<body>


<!-- =========================
     MOBILE TOP
========================= -->

<div class="mobile-top">

    <strong>
        Gofix Admin
    </strong>

    <button
        class="menu-toggle"
        onclick="toggleSidebar()">

        ☰

    </button>

</div>


<!-- =========================
     SIDEBAR
========================= -->

<?php require __DIR__ . '/sidebar.php'; ?>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="page-title">

            <h1>
                Payments
            </h1>

            <p>
                Manage COD payments and payment records.
            </p>

        </div>


        <div class="admin-name">

            👨‍💼

            <?php
            echo htmlspecialchars(
                $admin_name
            );
            ?>

        </div>

    </div>


    <!-- ALERT -->

    <?php if ($message !== ""): ?>

        <div
            class="alert
            <?php echo $message_type; ?>">

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-label">
                Total Payments
            </div>

            <div class="stat-number">

                <?php
                echo $stats['total'];
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Pending
            </div>

            <div class="stat-number">

                <?php
                echo $stats['pending'];
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Paid
            </div>

            <div class="stat-number">

                <?php
                echo $stats['paid'];
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Collected Amount
            </div>

            <div class="stat-number">

                ₹<?php
                echo number_format(
                    $stats['collected'],
                    0
                );
                ?>

            </div>

            <div class="stat-extra">

                Successful payments

            </div>

        </div>

    </div>


    <!-- =========================
         FILTER
    ========================= -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form">


            <input
                type="text"
                name="search"
                placeholder="Search booking ID, customer, mobile..."
                value="<?php
                echo htmlspecialchars(
                    $search
                );
                ?>">


            <select name="status">

                <option value="">
                    All Payment Status
                </option>


                <option
                    value="pending"
                    <?php
                    echo
                    $status_filter === "pending"
                    ? "selected"
                    : "";
                    ?>>

                    Pending

                </option>


                <option
                    value="paid"
                    <?php
                    echo
                    $status_filter === "paid"
                    ? "selected"
                    : "";
                    ?>>

                    Paid

                </option>


                <option
                    value="failed"
                    <?php
                    echo
                    $status_filter === "failed"
                    ? "selected"
                    : "";
                    ?>>

                    Failed

                </option>


                <option
                    value="refunded"
                    <?php
                    echo
                    $status_filter === "refunded"
                    ? "selected"
                    : "";
                    ?>>

                    Refunded

                </option>

            </select>


            <input
                type="date"
                name="date"
                value="<?php
                echo htmlspecialchars(
                    $date_filter
                );
                ?>">


            <button
                type="submit"
                class="btn btn-primary">

                Search

            </button>


            <a
                href="payments.php"
                class="btn btn-light">

                Reset

            </a>

        </form>

    </div>


    <!-- =========================
         PAYMENT TABLE
    ========================= -->

    <div class="table-box">


        <div class="table-header">

            Payment Records

        </div>


        <div class="table-scroll">


            <?php if (
                $result->num_rows > 0
            ): ?>


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
                                Provider
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Method
                            </th>

                            <th>
                                Payment Status
                            </th>

                            <th>
                                Payment Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $payment =
                        $result->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- BOOKING -->

                            <td>

                                <div
                                    class="booking-id">

                                    <?php
                                    echo htmlspecialchars(
                                        $payment[
                                            'booking_code'
                                        ]
                                    );
                                    ?>

                                </div>


                                <div
                                    class="booking-date">

                                    <?php

                                    if (
                                        !empty(
                                            $payment[
                                                'booking_date'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $payment[
                                                    'booking_date'
                                                ]
                                            )
                                        );

                                    }

                                    ?>

                                    <?php
                                    if (
                                        !empty(
                                            $payment[
                                                'booking_time'
                                            ]
                                        )
                                    ):
                                    ?>

                                        <br>

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                'booking_time'
                                            ]
                                        );
                                        ?>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div
                                    class="customer-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $payment[
                                            'customer_name'
                                        ]
                                        ?:
                                        $payment[
                                            'user_full_name'
                                        ]
                                    );
                                    ?>

                                </div>


                                <div
                                    class="customer-mobile">

                                    <?php
                                    echo htmlspecialchars(
                                        $payment[
                                            'mobile'
                                        ]
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- SERVICE -->

                            <td>

                                <div
                                    class="service-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $payment[
                                            'service_name'
                                        ]
                                        ?:
                                        "Service"
                                    );
                                    ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $payment[
                                            'category'
                                        ]
                                    )
                                ): ?>

                                    <span
                                        class="category">

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                'category'
                                            ]
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- PROVIDER -->

                            <td>

                                <?php
                                if (
                                    !empty(
                                        $payment[
                                            'provider_name'
                                        ]
                                    )
                                ):
                                ?>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                'provider_name'
                                            ]
                                        );
                                        ?>

                                    </strong>

                                    <br>

                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                'provider_mobile'
                                            ]
                                        );
                                        ?>

                                    </small>

                                <?php else: ?>

                                    <span
                                        style="color:#64748b;">

                                        Not Assigned

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AMOUNT -->

                            <td>

                                <strong>

                                    ₹<?php
                                    echo number_format(
                                        (float)
                                        $payment[
                                            'amount'
                                        ],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- METHOD -->

                            <td>

                                <span class="cod">

                                    <?php
                                    echo htmlspecialchars(
                                        $payment[
                                            'payment_method'
                                        ] ??
                                        "COD"
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="payment-status
                                    <?php
                                    echo paymentStatusClass(
                                        $payment[
                                            'payment_status'
                                        ]
                                    );
                                    ?>">

                                    <?php
                                    echo paymentStatusText(
                                        $payment[
                                            'payment_status'
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?php

                                if (
                                    $payment[
                                        'payment_status'
                                    ] === "paid" &&
                                    !empty(
                                        $payment[
                                            'paid_at'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $payment[
                                                'paid_at'
                                            ]
                                        )
                                    );

                                    echo "<br>";

                                    echo "<small>";

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $payment[
                                                'paid_at'
                                            ]
                                        )
                                    );

                                    echo "</small>";

                                } elseif (
                                    !empty(
                                        $payment[
                                            'payment_created_at'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $payment[
                                                'payment_created_at'
                                            ]
                                        )
                                    );

                                } else {

                                    echo "-";
                                }

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div
                                    class="action-buttons">


                                    <!-- VIEW -->

                                    <button
                                        type="button"
                                        class="action-btn view-btn"
                                        onclick='viewPayment(
                                            <?php
                                            echo json_encode(
                                                $payment,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT |
                                                JSON_HEX_AMP
                                            );
                                            ?>
                                        )'>

                                        View

                                    </button>


                                    <?php
                                    if (
                                        $payment[
                                            'payment_status'
                                        ] !== "paid"
                                    ):
                                    ?>


                                        <!-- MARK PAID -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Mark this payment as PAID?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_payment_status">


                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?php
                                                echo (int)
                                                    $payment[
                                                        'payment_id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="payment_status"
                                                value="paid">


                                            <button
                                                type="submit"
                                                class="action-btn paid-btn">

                                                Mark Paid

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        $payment[
                                            'payment_status'
                                        ] === "pending"
                                    ):
                                    ?>


                                        <!-- FAILED -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Mark this payment as failed?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_payment_status">


                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?php
                                                echo (int)
                                                    $payment[
                                                        'payment_id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="payment_status"
                                                value="failed">


                                            <button
                                                type="submit"
                                                class="action-btn failed-btn">

                                                Failed

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        $payment[
                                            'payment_status'
                                        ] === "paid"
                                    ):
                                    ?>


                                        <!-- REFUND -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Mark this payment as refunded?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_payment_status">


                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?php
                                                echo (int)
                                                    $payment[
                                                        'payment_id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="payment_status"
                                                value="refunded">


                                            <button
                                                type="submit"
                                                class="action-btn refund-btn">

                                                Refund

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        $payment[
                                            'payment_status'
                                        ] !== "pending"
                                    ):
                                    ?>


                                        <!-- RESET PENDING -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Set payment back to pending?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_payment_status">


                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?php
                                                echo (int)
                                                    $payment[
                                                        'payment_id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="payment_status"
                                                value="pending">


                                            <button
                                                type="submit"
                                                class="action-btn pending-btn">

                                                Pending

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty">

                    <h3>
                        No payment records found
                    </h3>

                    <p>
                        No payment matches your search or filter.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>


<!-- =========================
     PAYMENT DETAILS MODAL
========================= -->

<div
    class="modal"
    id="paymentModal"
    onclick="closeOutside(event)">


    <div class="modal-box">


        <button
            class="modal-close"
            onclick="closeModal()">

            ×

        </button>


        <h2
            class="modal-title"
            id="modalTitle">

            Payment Details

        </h2>


        <div class="detail-grid">


            <div class="detail-item">

                <div class="detail-label">
                    Booking ID
                </div>

                <div
                    class="detail-value"
                    id="modalBooking">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Payment ID
                </div>

                <div
                    class="detail-value"
                    id="modalPaymentId">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Customer
                </div>

                <div
                    class="detail-value"
                    id="modalCustomer">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Mobile
                </div>

                <div
                    class="detail-value"
                    id="modalMobile">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Service
                </div>

                <div
                    class="detail-value"
                    id="modalService">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Provider
                </div>

                <div
                    class="detail-value"
                    id="modalProvider">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Amount
                </div>

                <div
                    class="detail-value"
                    id="modalAmount">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Payment Method
                </div>

                <div
                    class="detail-value"
                    id="modalMethod">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Payment Status
                </div>

                <div
                    class="detail-value"
                    id="modalStatus">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Booking Status
                </div>

                <div
                    class="detail-value"
                    id="modalBookingStatus">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Paid At
                </div>

                <div
                    class="detail-value"
                    id="modalPaidAt">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Created At
                </div>

                <div
                    class="detail-value"
                    id="modalCreatedAt">

                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Transaction ID
                </div>

                <div
                    class="detail-value"
                    id="modalTransaction">

                </div>

            </div>


        </div>

    </div>

</div>


<script>

/* =========================
   SIDEBAR
========================= */

function toggleSidebar() {

    document
        .getElementById("sidebar")
        .classList.toggle("open");

}


/* =========================
   PAYMENT MODAL
========================= */

function viewPayment(payment) {

    document.getElementById(
        "modalTitle"
    ).textContent =
        "Payment Details";


    document.getElementById(
        "modalBooking"
    ).textContent =
        payment.booking_code ||
        "-";


    document.getElementById(
        "modalPaymentId"
    ).textContent =
        payment.payment_id ||
        "-";


    document.getElementById(
        "modalCustomer"
    ).textContent =
        payment.customer_name ||
        payment.user_full_name ||
        "-";


    document.getElementById(
        "modalMobile"
    ).textContent =
        payment.mobile ||
        "-";


    document.getElementById(
        "modalService"
    ).textContent =
        payment.service_name ||
        "-";


    document.getElementById(
        "modalProvider"
    ).textContent =
        payment.provider_name ||
        "Not Assigned";


    document.getElementById(
        "modalAmount"
    ).textContent =
        "₹" +
        Number(
            payment.amount || 0
        ).toLocaleString(
            "en-IN",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );


    document.getElementById(
        "modalMethod"
    ).textContent =
        payment.payment_method ||
        "COD";


    document.getElementById(
        "modalStatus"
    ).textContent =
        payment.payment_status ||
        "-";


    document.getElementById(
        "modalBookingStatus"
    ).textContent =
        payment.booking_status ||
        "-";


    document.getElementById(
        "modalPaidAt"
    ).textContent =
        formatDateTime(
            payment.paid_at
        );


    document.getElementById(
        "modalCreatedAt"
    ).textContent =
        formatDateTime(
            payment.payment_created_at
        );


    document.getElementById(
        "modalTransaction"
    ).textContent =
        payment.transaction_id ||
        "Not available";


    document
        .getElementById(
            "paymentModal"
        )
        .classList.add("show");

}


/* =========================
   FORMAT DATE
========================= */

function formatDateTime(value) {

    if (!value) {
        return "Not available";
    }

    const date =
        new Date(
            value.replace(" ", "T")
        );


    if (
        isNaN(
            date.getTime()
        )
    ) {

        return value;
    }


    return date.toLocaleString(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }
    );
}


/* =========================
   CLOSE MODAL
========================= */

function closeModal() {

    document
        .getElementById(
            "paymentModal"
        )
        .classList.remove("show");

}


/* =========================
   CLICK OUTSIDE
========================= */

function closeOutside(event) {

    if (
        event.target ===
        document.getElementById(
            "paymentModal"
        )
    ) {

        closeModal();

    }

}


/* =========================
   ESC KEY
========================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeModal();

        }

    }
);

</script>


<script src="admin-ui.js"></script>
</body>

</html>
