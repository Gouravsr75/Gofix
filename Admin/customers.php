<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan customer list, search aur account actions handle hote hain.

$admin_name = $_SESSION['admin_name'] ?? "Administrator";

$message = "";
$message_type = "success";


/* =========================
   CUSTOMER ACTIONS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";
    $customer_id = (int)($_POST['customer_id'] ?? 0);

    if ($customer_id <= 0) {

        $message = "Invalid customer.";
        $message_type = "error";

    } else {

        /* =========================
           CHANGE CUSTOMER STATUS
        ========================= */

        if ($action === "change_status") {

            $new_status = $_POST['status'] ?? "";

            $allowed_status = [
                "active",
                "blocked"
            ];

            if (!in_array($new_status, $allowed_status, true)) {

                $message = "Invalid customer status.";
                $message_type = "error";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET status = ?
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "si",
                        $new_status,
                        $customer_id
                    );

                    if ($stmt->execute()) {

                        $message = "Customer status updated successfully.";

                    } else {

                        $message = "Unable to update customer status.";
                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    $message = "Database error.";
                    $message_type = "error";
                }
            }
        }


        /* =========================
           DELETE CUSTOMER
        ========================= */

        elseif ($action === "delete") {

            /*
             * Customer booking history is checked first.
             * Customer with booking history will not be deleted.
             */

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM bookings
                 WHERE user_id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $customer_id
                );

                $stmt->execute();

                $booking_result = $stmt->get_result();

                $booking_data = $booking_result->fetch_assoc();

                $stmt->close();

                $total_bookings = (int)(
                    $booking_data['total'] ?? 0
                );

            } else {

                $total_bookings = 0;
            }


            if ($total_bookings > 0) {

                $message =
                    "This customer cannot be deleted because booking history exists.";

                $message_type = "error";

            } else {

                $stmt = $conn->prepare(
                    "DELETE FROM users
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $customer_id
                    );

                    if ($stmt->execute()) {

                        $message =
                            "Customer deleted successfully.";

                    } else {

                        $message =
                            "Unable to delete customer.";

                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    $message = "Database error.";
                    $message_type = "error";
                }
            }
        }
    }
}


/* =========================
   SEARCH / FILTER
========================= */

$search = trim($_GET['search'] ?? "");
$status_filter = $_GET['status'] ?? "";

$where = [];
$params = [];
$types = "";


/* SEARCH */

if ($search !== "") {

    $where[] = "(
        u.full_name LIKE ?
        OR u.mobile LIKE ?
        OR u.email LIKE ?
        OR u.city LIKE ?
    )";

    $search_value = "%" . $search . "%";

    for ($i = 0; $i < 4; $i++) {

        $params[] = $search_value;
        $types .= "s";
    }
}


/* STATUS */

if ($status_filter !== "") {

    if (
        in_array(
            $status_filter,
            ["active", "blocked"],
            true
        )
    ) {

        $where[] = "u.status = ?";

        $params[] = $status_filter;

        $types .= "s";
    }
}


/* =========================
   CUSTOMER QUERY
========================= */

$sql = "
    SELECT
        u.*,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.user_id = u.id
        ) AS total_bookings,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.user_id = u.id
            AND b.booking_status = 'completed'
        ) AS completed_bookings,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.user_id = u.id
            AND b.booking_status IN (
                'pending',
                'provider_notified',
                'accepted',
                'rescheduled',
                'on_the_way',
                'work_started'
            )
        ) AS active_bookings,

        (
            SELECT COALESCE(SUM(b.price), 0)
            FROM bookings b
            WHERE b.user_id = u.id
            AND b.booking_status = 'completed'
        ) AS total_spent

    FROM users u
";


if (!empty($where)) {

    $sql .=
        " WHERE " .
        implode(" AND ", $where);
}

$sql .= " ORDER BY u.id DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "SQL Prepare Error: " .
        $conn->error
    );
}


/* =========================
   BIND SEARCH PARAMETERS
========================= */

if (!empty($params)) {

    $bind_names = [];

    $bind_names[] = $types;

    foreach ($params as $key => $value) {

        $bind_names[] =
            &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_names
    );
}


$stmt->execute();

$result = $stmt->get_result();


/* =========================
   CUSTOMER COUNTS
========================= */

function getCustomerCount(
    $conn,
    $status = null
) {

    if ($status === null) {

        $sql = "
            SELECT COUNT(*) AS total
            FROM users
        ";

        $result = $conn->query($sql);

    } else {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM users
             WHERE status = ?"
        );

        $stmt->bind_param(
            "s",
            $status
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $stmt->close();
    }

    $row = $result->fetch_assoc();

    return (int)(
        $row['total'] ?? 0
    );
}


$total_customers =
    getCustomerCount($conn);

$active_customers =
    getCustomerCount(
        $conn,
        "active"
    );

$blocked_customers =
    getCustomerCount(
        $conn,
        "blocked"
    );


/* =========================
   TOTAL REVENUE
========================= */

$revenue_result = $conn->query(
    "SELECT
        COALESCE(SUM(price), 0) AS total
     FROM bookings
     WHERE booking_status = 'completed'"
);

$revenue_row =
    $revenue_result->fetch_assoc();

$total_revenue =
    (float)($revenue_row['total'] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Customers - Admin</title>


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
   MOBILE TOP
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
   STAT CARDS
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

    font-size: 27px;

    font-weight: 700;
}


.stat-small {

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

    min-width: 260px;
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
}


.btn-primary {

    background: #1264d6;

    color: white;
}


.btn-light {

    background: #e9eef5;

    color: #172033;

    display: flex;

    align-items: center;

    justify-content: center;
}


/* =========================
   CUSTOMER TABLE
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

    min-width: 1050px;
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


.customer-name {

    font-weight: 700;

    margin-bottom: 4px;
}


.customer-mobile {

    color: #64748b;

    font-size: 12px;
}


.customer-email {

    color: #64748b;

    font-size: 12px;

    margin-top: 2px;
}


/* =========================
   STATUS
========================= */

.status {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;
}


.status-active {

    background: #dcfce7;

    color: #166534;
}


.status-blocked {

    background: #fee2e2;

    color: #991b1b;
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


.block-btn {

    background: #fee2e2;

    color: #991b1b;
}


.activate-btn {

    background: #dcfce7;

    color: #166534;
}


.delete-btn {

    background: #dc2626;

    color: white;
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

    width: 100%;

    max-width: 650px;

    max-height: 90vh;

    overflow-y: auto;

    background: white;

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

@media (max-width: 1000px) {

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
            repeat(2, 1fr);

        gap: 10px;
    }


    .stat-card {

        padding: 14px;
    }


    .stat-number {

        font-size: 22px;
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
     MOBILE HEADER
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
                Customers
            </h1>

            <p>
                Manage registered customers and their bookings.
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
                Total Customers
            </div>

            <div class="stat-number">

                <?php
                echo $total_customers;
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Active Customers
            </div>

            <div class="stat-number">

                <?php
                echo $active_customers;
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Blocked Customers
            </div>

            <div class="stat-number">

                <?php
                echo $blocked_customers;
                ?>

            </div>

        </div>


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

        </div>

    </div>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form">


            <input
                type="text"
                name="search"
                placeholder="Search name, mobile, email, city..."
                value="<?php
                echo htmlspecialchars(
                    $search
                );
                ?>">


            <select name="status">

                <option value="">
                    All Status
                </option>


                <option
                    value="active"
                    <?php
                    echo $status_filter === "active"
                        ? "selected"
                        : "";
                    ?>>

                    Active

                </option>


                <option
                    value="blocked"
                    <?php
                    echo $status_filter === "blocked"
                        ? "selected"
                        : "";
                    ?>>

                    Blocked

                </option>

            </select>


            <button
                type="submit"
                class="btn btn-primary">

                Search

            </button>


            <a
                href="customers.php"
                class="btn btn-light">

                Reset

            </a>

        </form>

    </div>


    <!-- =========================
         CUSTOMER TABLE
    ========================= -->

    <div class="table-box">


        <div class="table-header">

            Customer List

        </div>


        <div class="table-scroll">


            <?php if ($result->num_rows > 0): ?>


                <table>


                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>

                            <th>
                                City
                            </th>

                            <th>
                                Bookings
                            </th>

                            <th>
                                Spent
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Registered
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $customer =
                        $result->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- CUSTOMER -->

                            <td>

                                <div
                                    class="customer-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['full_name']
                                    );
                                    ?>

                                </div>


                                <div
                                    class="customer-mobile">

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['mobile']
                                    );
                                    ?>

                                </div>


                                <div
                                    class="customer-email">

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['email']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- CITY -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $customer['city']
                                    ?: "Not provided"
                                );
                                ?>

                            </td>


                            <!-- BOOKINGS -->

                            <td>

                                <strong>

                                    <?php
                                    echo (int)
                                        $customer[
                                            'total_bookings'
                                        ];
                                    ?>

                                </strong>


                                <?php
                                if (
                                    (int)$customer[
                                        'completed_bookings'
                                    ] > 0
                                ):
                                ?>

                                    <br>

                                    <small>

                                        Completed:
                                        <?php
                                        echo (int)
                                            $customer[
                                                'completed_bookings'
                                            ];
                                        ?>

                                    </small>

                                <?php endif; ?>


                                <?php
                                if (
                                    (int)$customer[
                                        'active_bookings'
                                    ] > 0
                                ):
                                ?>

                                    <br>

                                    <small>

                                        Active:
                                        <?php
                                        echo (int)
                                            $customer[
                                                'active_bookings'
                                            ];
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- SPENT -->

                            <td>

                                ₹<?php
                                echo number_format(
                                    (float)
                                    $customer[
                                        'total_spent'
                                    ],
                                    0
                                );
                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status
                                    status-<?php
                                    echo htmlspecialchars(
                                        $customer[
                                            'status'
                                        ]
                                    );
                                    ?>">

                                    <?php
                                    echo ucfirst(
                                        $customer[
                                            'status'
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- REGISTERED -->

                            <td>

                                <?php

                                echo !empty(
                                    $customer[
                                        'created_at'
                                    ]
                                )
                                ?
                                date(
                                    "d M Y",
                                    strtotime(
                                        $customer[
                                            'created_at'
                                        ]
                                    )
                                )
                                :
                                "-";

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
                                        onclick='viewCustomer(
                                            <?php
                                            echo json_encode(
                                                $customer,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT |
                                                JSON_HEX_AMP
                                            );
                                            ?>
                                        )'>

                                        View

                                    </button>


                                    <!-- ACTIVE -->

                                    <?php
                                    if (
                                        $customer[
                                            'status'
                                        ] === "active"
                                    ):
                                    ?>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Block this customer?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">


                                            <input
                                                type="hidden"
                                                name="customer_id"
                                                value="<?php
                                                echo (int)
                                                    $customer[
                                                        'id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="status"
                                                value="blocked">


                                            <button
                                                type="submit"
                                                class="action-btn block-btn">

                                                Block

                                            </button>

                                        </form>


                                    <?php else: ?>


                                        <!-- BLOCKED -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Activate this customer?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">


                                            <input
                                                type="hidden"
                                                name="customer_id"
                                                value="<?php
                                                echo (int)
                                                    $customer[
                                                        'id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="status"
                                                value="active">


                                            <button
                                                type="submit"
                                                class="action-btn activate-btn">

                                                Activate

                                            </button>

                                        </form>


                                    <?php endif; ?>


                                    <!-- DELETE -->

                                    <?php
                                    if (
                                        (int)$customer[
                                            'total_bookings'
                                        ] === 0
                                    ):
                                    ?>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to permanently delete this customer?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete">


                                            <input
                                                type="hidden"
                                                name="customer_id"
                                                value="<?php
                                                echo (int)
                                                    $customer[
                                                        'id'
                                                    ];
                                                ?>">


                                            <button
                                                type="submit"
                                                class="action-btn delete-btn">

                                                Delete

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
                        No customers found
                    </h3>

                    <p>
                        No customer matches your search or filter.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>


<!-- =========================
     CUSTOMER DETAILS MODAL
========================= -->

<div
    class="modal"
    id="customerModal"
    onclick="closeOutside(event)">


    <div class="modal-box">


        <button
            class="modal-close"
            onclick="closeModal()">

            ×

        </button>


        <h2
            class="modal-title"
            id="modalCustomerName">

            Customer Details

        </h2>


        <div class="detail-grid">


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
                    Email
                </div>

                <div
                    class="detail-value"
                    id="modalEmail">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    City
                </div>

                <div
                    class="detail-value"
                    id="modalCity">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Status
                </div>

                <div
                    class="detail-value"
                    id="modalStatus">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Total Bookings
                </div>

                <div
                    class="detail-value"
                    id="modalBookings">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Completed Bookings
                </div>

                <div
                    class="detail-value"
                    id="modalCompleted">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Active Bookings
                </div>

                <div
                    class="detail-value"
                    id="modalActive">

                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Total Spent
                </div>

                <div
                    class="detail-value"
                    id="modalSpent">

                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Registered On
                </div>

                <div
                    class="detail-value"
                    id="modalJoined">

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
   CUSTOMER MODAL
========================= */

function viewCustomer(customer) {


    document.getElementById(
        "modalCustomerName"
    ).textContent =
        customer.full_name ||
        "Customer Details";


    document.getElementById(
        "modalMobile"
    ).textContent =
        customer.mobile ||
        "Not provided";


    document.getElementById(
        "modalEmail"
    ).textContent =
        customer.email ||
        "Not provided";


    document.getElementById(
        "modalCity"
    ).textContent =
        customer.city ||
        "Not provided";


    document.getElementById(
        "modalStatus"
    ).textContent =
        customer.status ||
        "Unknown";


    document.getElementById(
        "modalBookings"
    ).textContent =
        customer.total_bookings ||
        "0";


    document.getElementById(
        "modalCompleted"
    ).textContent =
        customer.completed_bookings ||
        "0";


    document.getElementById(
        "modalActive"
    ).textContent =
        customer.active_bookings ||
        "0";


    document.getElementById(
        "modalSpent"
    ).textContent =
        "₹" +
        Number(
            customer.total_spent || 0
        ).toLocaleString("en-IN");


    let joined =
        "Not available";


    if (customer.created_at) {

        const date =
            new Date(
                customer.created_at
                    .replace(" ", "T")
            );


        if (!isNaN(
            date.getTime()
        )) {

            joined =
                date.toLocaleDateString(
                    "en-IN",
                    {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    }
                );
        }
    }


    document.getElementById(
        "modalJoined"
    ).textContent =
        joined;


    document
        .getElementById(
            "customerModal"
        )
        .classList.add("show");

}


/* =========================
   CLOSE MODAL
========================= */

function closeModal() {

    document
        .getElementById(
            "customerModal"
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
            "customerModal"
        )
    ) {

        closeModal();

    }

}


/* =========================
   ESCAPE
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
