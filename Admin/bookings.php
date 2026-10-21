<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Is page par booking list, filters aur provider assignment manage hota hai.

$admin_id = (int) $_SESSION['admin_id'];

$success = "";
$error = "";


/* =========================================
   HELPER FUNCTIONS
========================================= */

function statusText($status)
{
    $list = [

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

    return $list[$status] ?? ucfirst($status);
}


function statusClass($status)
{
    return 'status-' .
        str_replace(
            '_',
            '-',
            strtolower($status)
        );
}


/* =========================================
   ADMIN ACTIONS
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $action =
        $_POST['action'] ?? "";

    $booking_db_id =
        (int)($_POST['booking_db_id'] ?? 0);


    /* =====================================
       ASSIGN PROVIDER
    ====================================== */

    if (
        $action === "assign_provider"
        &&
        $booking_db_id > 0
    ) {

        $provider_id =
            (int)($_POST['provider_id'] ?? 0);


        if ($provider_id <= 0) {

            $error =
                "Please select a provider.";

        } else {


            /* Get booking */

            $sql = "SELECT
                        id,
                        booking_id,
                        booking_status,
                        provider_id
                    FROM bookings
                    WHERE id = ?
                    LIMIT 1";

            $stmt =
                $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $booking_db_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                if (
                    $result->num_rows !== 1
                ) {

                    $error =
                        "Booking not found.";

                } else {

                    $booking =
                        $result->fetch_assoc();


                    /* Check provider */

                    $provider_sql =
                        "SELECT
                            id,
                            provider_name,
                            status
                         FROM service_providers
                         WHERE id = ?
                         LIMIT 1";

                    $provider_stmt =
                        $conn->prepare(
                            $provider_sql
                        );

                    if ($provider_stmt) {

                        $provider_stmt->bind_param(
                            "i",
                            $provider_id
                        );

                        $provider_stmt->execute();

                        $provider_result =
                            $provider_stmt
                            ->get_result();

                        if (
                            $provider_result
                            ->num_rows !== 1
                        ) {

                            $error =
                                "Provider not found.";

                        } else {

                            $provider =
                                $provider_result
                                ->fetch_assoc();


                            if (
                                $provider['status']
                                !== 'active'
                            ) {

                                $error =
                                    "Only active providers can be assigned.";

                            } else {


                                /* ---------------------------------
                                   Assign Provider
                                --------------------------------- */

                                $old_status =
                                    $booking[
                                        'booking_status'
                                    ];

                                $new_status =
                                    'provider_notified';


                                // Hinglish: Sirf open request assign hoti hai; accepted job overwrite nahi hoti.
                                $update_sql =
                                    "UPDATE bookings
                                     SET provider_id = ?,
                                         booking_status = ?
                                     WHERE id = ?
                                       AND booking_status IN ('pending', 'provider_notified')
                                       AND (provider_id IS NULL OR provider_id = ?)";


                                $update =
                                    $conn->prepare(
                                        $update_sql
                                    );


                                if ($update) {

                                    $update->bind_param(
                                        "isii",
                                        $provider_id,
                                        $new_status,
                                        $booking_db_id,
                                        $provider_id
                                    );


                                    if (
                                        $update->execute()
                                        && $update->affected_rows > 0
                                    ) {

                                        // Hinglish: Manual assignment par dusre providers ki open requests band karo.
                                        $close_other_requests = $conn->prepare(
                                            "UPDATE provider_notifications
                                             SET response_status = 'rejected', is_read = 1
                                             WHERE booking_id = ?
                                               AND provider_id <> ?
                                               AND notification_type = 'new_booking'
                                               AND response_status = 'pending'"
                                        );
                                        if ($close_other_requests) {
                                            $close_other_requests->bind_param(
                                                "ii",
                                                $booking_db_id,
                                                $provider_id
                                            );
                                            $close_other_requests->execute();
                                            $close_other_requests->close();
                                        }


                                        /* History */

                                        $history_sql =
                                            "INSERT INTO
                                             booking_status_history
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
                                                'admin',
                                                ?,
                                                ?
                                             )";


                                        $history =
                                            $conn->prepare(
                                                $history_sql
                                            );


                                        if ($history) {

                                            $note =
                                                "Provider assigned by admin.";

                                            $history->bind_param(
                                                "issis",
                                                $booking_db_id,
                                                $old_status,
                                                $new_status,
                                                $admin_id,
                                                $note
                                            );

                                            $history->execute();

                                            $history->close();
                                        }


                                        /* Assigned provider ko pending notification do. */
                                        $message =
                                            "New booking " .
                                            $booking['booking_id'] .
                                            " has been assigned to you.";

                                        $refresh_notification = $conn->prepare(
                                            "UPDATE provider_notifications
                                             SET response_status = 'pending', is_read = 0, message = ?
                                             WHERE provider_id = ?
                                               AND booking_id = ?
                                               AND notification_type = 'new_booking'"
                                        );
                                        if ($refresh_notification) {
                                            $refresh_notification->bind_param(
                                                "sii",
                                                $message,
                                                $provider_id,
                                                $booking_db_id
                                            );
                                            $refresh_notification->execute();
                                            $refresh_notification->close();
                                        }

                                        $notification = $conn->prepare(
                                            "INSERT INTO provider_notifications
                                                (provider_id, booking_id, notification_type, message, is_read)
                                             SELECT ?, ?, 'new_booking', ?, 0
                                             WHERE NOT EXISTS (
                                                SELECT 1 FROM provider_notifications
                                                WHERE provider_id = ?
                                                  AND booking_id = ?
                                                  AND notification_type = 'new_booking'
                                             )"
                                        );
                                        if ($notification) {
                                            $notification->bind_param(
                                                "iisii",
                                                $provider_id,
                                                $booking_db_id,
                                                $message,
                                                $provider_id,
                                                $booking_db_id
                                            );
                                            $notification->execute();
                                            $notification->close();
                                        }


                                        $success =
                                            "Provider assigned successfully.";

                                    } else {

                                        $error =
                                            "This booking was already accepted or assigned. Refresh and try again.";
                                    }

                                    $update->close();

                                } else {

                                    $error =
                                        "Database error.";
                                }
                            }
                        }

                        $provider_stmt->close();
                    }
                }

                $stmt->close();
            }
        }
    }


    /* =====================================
       CHANGE BOOKING STATUS
    ====================================== */

    elseif (
        $action === "change_status"
        &&
        $booking_db_id > 0
    ) {

        $new_status =
            trim(
                $_POST['new_status'] ?? ""
            );


        $allowed_statuses = [

            'pending',
            'provider_notified',
            'accepted',
            'rejected',
            'rescheduled',
            'on_the_way',
            'work_started',
            'completed',
            'cancelled'
        ];


        if (
            !in_array(
                $new_status,
                $allowed_statuses,
                true
            )
        ) {

            $error =
                "Invalid booking status.";

        } else {


            /* Get old status */

            $sql =
                "SELECT
                    id,
                    booking_id,
                    booking_status,
                    provider_id
                 FROM bookings
                 WHERE id = ?
                 LIMIT 1";


            $stmt =
                $conn->prepare($sql);


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $booking_db_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();


                if (
                    $result->num_rows !== 1
                ) {

                    $error =
                        "Booking not found.";

                } else {

                    $booking =
                        $result->fetch_assoc();

                    $old_status =
                        $booking[
                            'booking_status'
                        ];


                    if (
                        $old_status ===
                        $new_status
                    ) {

                        $error =
                            "Booking already has this status.";

                    } else {


                        /* ---------------------------------
                           Update
                        --------------------------------- */

                        $update_sql =
                            "UPDATE bookings
                             SET booking_status = ?
                             WHERE id = ?";


                        $update =
                            $conn->prepare(
                                $update_sql
                            );


                        if ($update) {

                            $update->bind_param(
                                "si",
                                $new_status,
                                $booking_db_id
                            );


                            if (
                                $update->execute()
                            ) {


                                /* History */

                                $history_sql =
                                    "INSERT INTO
                                     booking_status_history
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
                                        'admin',
                                        ?,
                                        ?
                                     )";


                                $history =
                                    $conn->prepare(
                                        $history_sql
                                    );


                                if ($history) {

                                    $note =
                                        "Status changed by admin.";

                                    $history->bind_param(
                                        "issis",
                                        $booking_db_id,
                                        $old_status,
                                        $new_status,
                                        $admin_id,
                                        $note
                                    );

                                    $history->execute();

                                    $history->close();
                                }


                                /* Provider notification */

                                if (
                                    !empty(
                                        $booking[
                                            'provider_id'
                                        ]
                                    )
                                ) {

                                    $notification_type =
                                        'booking_updated';


                                    if (
                                        $new_status ===
                                        'cancelled'
                                    ) {

                                        $notification_type =
                                            'booking_cancelled';

                                    } elseif (
                                        $new_status ===
                                        'accepted'
                                    ) {

                                        $notification_type =
                                            'booking_accepted';

                                    } elseif (
                                        $new_status ===
                                        'rejected'
                                    ) {

                                        $notification_type =
                                            'booking_rejected';

                                    } elseif (
                                        $new_status ===
                                        'rescheduled'
                                    ) {

                                        $notification_type =
                                            'booking_rescheduled';
                                    }


                                    $notification_sql =
                                        "INSERT INTO
                                         provider_notifications
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
                                            ?,
                                            ?
                                         )";


                                    $notification =
                                        $conn->prepare(
                                            $notification_sql
                                        );


                                    if ($notification) {

                                        $message =
                                            "Booking " .
                                            $booking[
                                                'booking_id'
                                            ] .
                                            " status changed to " .
                                            statusText(
                                                $new_status
                                            ) .
                                            " by admin.";

                                        $notification->bind_param(
                                            "iiss",
                                            $booking[
                                                'provider_id'
                                            ],
                                            $booking_db_id,
                                            $notification_type,
                                            $message
                                        );

                                        $notification->execute();

                                        $notification->close();
                                    }
                                }


                                $success =
                                    "Booking status updated successfully.";

                            } else {

                                $error =
                                    "Unable to update booking.";
                            }


                            $update->close();

                        } else {

                            $error =
                                "Database error.";
                        }
                    }
                }

                $stmt->close();
            }
        }
    }


    /* =====================================
       CANCEL BOOKING
    ====================================== */

    elseif (
        $action === "cancel_booking"
        &&
        $booking_db_id > 0
    ) {


        /* Get booking */

        $sql =
            "SELECT
                id,
                booking_id,
                booking_status,
                provider_id
             FROM bookings
             WHERE id = ?
             LIMIT 1";


        $stmt =
            $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $booking_db_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            if (
                $result->num_rows !== 1
            ) {

                $error =
                    "Booking not found.";

            } else {

                $booking =
                    $result->fetch_assoc();


                $old_status =
                    $booking[
                        'booking_status'
                    ];


                if (
                    $old_status ===
                    'completed'
                ) {

                    $error =
                        "Completed booking cannot be cancelled.";

                } elseif (
                    $old_status ===
                    'cancelled'
                ) {

                    $error =
                        "Booking is already cancelled.";

                } else {


                    /* Update */

                    $new_status =
                        'cancelled';


                    $update_sql =
                        "UPDATE bookings
                         SET booking_status = ?
                         WHERE id = ?";


                    $update =
                        $conn->prepare(
                            $update_sql
                        );


                    if ($update) {

                        $update->bind_param(
                            "si",
                            $new_status,
                            $booking_db_id
                        );


                        if (
                            $update->execute()
                        ) {


                            /* History */

                            $history_sql =
                                "INSERT INTO
                                 booking_status_history
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
                                    'admin',
                                    ?,
                                    ?
                                 )";


                            $history =
                                $conn->prepare(
                                    $history_sql
                                );


                            if ($history) {

                                $note =
                                    "Booking cancelled by admin.";

                                $history->bind_param(
                                    "issis",
                                    $booking_db_id,
                                    $old_status,
                                    $new_status,
                                    $admin_id,
                                    $note
                                );

                                $history->execute();

                                $history->close();
                            }


                            /* Provider notification */

                            if (
                                !empty(
                                    $booking[
                                        'provider_id'
                                    ]
                                )
                            ) {

                                $notification_sql =
                                    "INSERT INTO
                                     provider_notifications
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
                                        $booking[
                                            'booking_id'
                                        ] .
                                        " was cancelled by admin.";

                                    $notification->bind_param(
                                        "iis",
                                        $booking[
                                            'provider_id'
                                        ],
                                        $booking_db_id,
                                        $message
                                    );

                                    $notification->execute();

                                    $notification->close();
                                }
                            }


                            $success =
                                "Booking cancelled successfully.";

                        } else {

                            $error =
                                "Unable to cancel booking.";
                        }


                        $update->close();

                    } else {

                        $error =
                            "Database error.";
                    }
                }
            }

            $stmt->close();
        }
    }
}


/* =========================================
   FILTERS
========================================= */

$status_filter =
    trim($_GET['status'] ?? "");

$search =
    trim($_GET['search'] ?? "");

$date_filter =
    trim($_GET['date'] ?? "");


/* =========================================
   BUILD QUERY
========================================= */

$sql = "SELECT

            b.id,
            b.provider_id,
            b.booking_id,
            b.customer_name,
            b.mobile,
            b.email,
            b.address,
            b.village,
            b.area,
            b.city,
            b.district,
            b.pincode,
            b.landmark,

            b.booking_date,
            b.booking_time,

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

            sp.provider_name,
            sp.mobile AS provider_mobile

        FROM bookings b

        LEFT JOIN services s
            ON b.service_id = s.id

        LEFT JOIN service_providers sp
            ON b.provider_id = sp.id

        WHERE 1 = 1";


$params = [];
$types = "";


/* =========================================
   STATUS FILTER
========================================= */

if ($status_filter !== "") {

    $sql .=
        " AND b.booking_status = ?";

    $types .= "s";

    $params[] =
        $status_filter;
}


/* =========================================
   SEARCH
========================================= */

if ($search !== "") {

    $sql .=
        " AND (
            b.booking_id LIKE ?
            OR b.customer_name LIKE ?
            OR b.mobile LIKE ?
            OR s.service_name LIKE ?
            OR sp.provider_name LIKE ?
        )";

    $search_value =
        "%" . $search . "%";

    $types .= "sssss";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}


/* =========================================
   DATE FILTER
========================================= */

if ($date_filter !== "") {

    $sql .=
        " AND b.booking_date = ?";

    $types .= "s";

    $params[] =
        $date_filter;
}


/* =========================================
   ORDER
========================================= */

$sql .=
    " ORDER BY b.id DESC";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database Error: " .
        $conn->error
    );
}


/* =========================================
   BIND FILTER PARAMETERS
========================================= */

if (
    $types !== ""
    &&
    count($params) > 0
) {

    $bind_values = [];

    $bind_values[] =
        $types;


    foreach ($params as $key => $value) {

        $bind_values[] =
            &$params[$key];
    }


    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_values
    );
}


$stmt->execute();

$result =
    $stmt->get_result();

$bookings = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $bookings[] = $row;
}

$stmt->close();


/* =========================================
   PROVIDERS
========================================= */

$providers = [];

$sql =
    "SELECT
        id,
        provider_name,
        mobile,
        area,
        city,
        service_categories

     FROM service_providers

     WHERE status = 'active'

     ORDER BY provider_name ASC";


$result =
    $conn->query($sql);


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $providers[] =
            $row;
    }
}


/* =========================================
   COUNT
========================================= */

$total_results =
    count($bookings);


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

<title>Manage Bookings</title>


<style>

/* =========================================
   RESET
========================================= */

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


/* =========================================
   SIDEBAR
========================================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    width: 240px;

    background: #fff;

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
}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 240px;

    min-height: 100vh;
}


.topbar {

    height: 70px;

    background: #fff;

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

    gap: 15px;

    align-items: center;
}


.view-site {

    color: #1264d6;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;
}


.logout {

    background: #1264d6;

    color: #fff;

    text-decoration: none;

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


.heading {

    margin-bottom: 20px;
}


.heading h1 {

    font-size: 24px;

    margin-bottom: 5px;
}


.heading p {

    color: #777;

    font-size: 13px;
}


/* =========================================
   MESSAGES
========================================= */

.message {

    padding: 12px 15px;

    border-radius: 8px;

    margin-bottom: 18px;

    font-size: 13px;
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
   FILTER BOX
========================================= */

.filter-box {

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    padding: 18px;

    margin-bottom: 20px;
}


.filter-form {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr auto;

    gap: 10px;
}


.filter-form input,
.filter-form select {

    width: 100%;

    padding: 11px;

    border: 1px solid #d7dce3;

    border-radius: 7px;

    outline: none;

    font-size: 13px;

    background: #fff;
}


.filter-form input:focus,
.filter-form select:focus {

    border-color: #1264d6;
}


.filter-btn {

    border: none;

    background: #1264d6;

    color: #fff;

    padding: 11px 18px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 13px;

    font-weight: bold;
}


.clear-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    background: #f1f3f5;

    color: #555;

    padding: 11px 15px;

    border-radius: 7px;

    font-size: 13px;
}


/* =========================================
   TABLE BOX
========================================= */

.table-box {

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    overflow: hidden;
}


.table-header {

    padding: 17px 20px;

    border-bottom: 1px solid #eee;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.table-header h2 {

    font-size: 17px;
}


.result-count {

    color: #777;

    font-size: 12px;
}


.table-wrapper {

    width: 100%;

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}


th {

    background: #f8fafc;

    text-align: left;

    padding: 12px 15px;

    color: #666;

    font-size: 11px;

    text-transform: uppercase;

    border-bottom: 1px solid #e5e7eb;
}


td {

    padding: 14px 15px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 13px;

    vertical-align: top;
}


tr:hover td {

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


.small {

    color: #777;

    font-size: 11px;

    line-height: 1.5;
}


.amount {

    color: #1264d6;

    font-weight: bold;
}


/* =========================================
   STATUS
========================================= */

.status {

    display: inline-block;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

    white-space: nowrap;
}


.status-pending,
.status-provider-notified {

    background: #fff4d6;

    color: #9a6700;
}


.status-accepted {

    background: #e7f7ed;

    color: #15803d;
}


.status-rejected,
.status-cancelled {

    background: #ffe8e8;

    color: #c00000;
}


.status-rescheduled {

    background: #f0e9ff;

    color: #6941c6;
}


.status-on-the-way,
.status-work-started {

    background: #e8f7ff;

    color: #087ea4;
}


.status-completed {

    background: #e7f7ed;

    color: #15803d;
}


/* =========================================
   ACTION BUTTON
========================================= */

.action-btn {

    border: none;

    background: #1264d6;

    color: #fff;

    padding: 7px 11px;

    border-radius: 6px;

    cursor: pointer;

    font-size: 11px;

    font-weight: bold;
}


.action-btn:hover {

    background: #0d55b8;
}


.cancel-action {

    background: #dc2626;

    margin-top: 5px;
}


.cancel-action:hover {

    background: #b91c1c;
}


/* =========================================
   MODAL
========================================= */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.45);

    z-index: 2000;

    padding: 20px;

    overflow-y: auto;
}


.modal-box {

    background: #fff;

    width: 100%;

    max-width: 600px;

    margin: 30px auto;

    border-radius: 12px;

    overflow: hidden;
}


.modal-header {

    padding: 17px 20px;

    border-bottom: 1px solid #eee;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.modal-header h2 {

    font-size: 18px;
}


.close-btn {

    border: none;

    background: transparent;

    font-size: 23px;

    cursor: pointer;

    color: #777;
}


.modal-body {

    padding: 20px;
}


.detail-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 12px;

    margin-bottom: 20px;
}


.detail {

    background: #f8fafc;

    padding: 12px;

    border-radius: 7px;
}


.detail-label {

    color: #777;

    font-size: 11px;

    margin-bottom: 4px;
}


.detail-value {

    font-size: 13px;

    font-weight: bold;
}


/* =========================================
   FORMS
========================================= */

.modal-form {

    margin-top: 20px;

    padding-top: 20px;

    border-top: 1px solid #eee;
}


.modal-form h3 {

    font-size: 15px;

    margin-bottom: 12px;
}


.modal-form select {

    width: 100%;

    padding: 11px;

    border: 1px solid #d7dce3;

    border-radius: 7px;

    margin-bottom: 10px;

    outline: none;
}


.modal-form button {

    border: none;

    background: #1264d6;

    color: #fff;

    padding: 10px 15px;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;

    cursor: pointer;
}


/* =========================================
   MOBILE MENU
========================================= */

.mobile-menu {

    display: none;

    border: none;

    background: #1264d6;

    color: #fff;

    width: 38px;

    height: 38px;

    border-radius: 7px;

    font-size: 19px;

    cursor: pointer;
}


.overlay {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.35);

    z-index: 999;
}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:850px) {

    .filter-form {

        grid-template-columns:
            1fr 1fr;
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


    .filter-form {

        grid-template-columns: 1fr;
    }


    .detail-grid {

        grid-template-columns: 1fr;
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


    <!-- TOPBAR -->

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
                Bookings
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



    <!-- CONTENT -->

    <div class="content">


        <div class="heading">

            <h1>
                Manage Bookings
            </h1>

            <p>
                View, assign providers and manage
                all customer bookings.
            </p>

        </div>



        <!-- =================================
             MESSAGES
        ================================== -->

        <?php if ($success !== ""): ?>

            <div class="message success">

                <?php
                echo htmlspecialchars(
                    $success
                );
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="message error">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>



        <!-- =================================
             FILTER
        ================================== -->

        <div class="filter-box">


            <form
                method="GET"
                class="filter-form"
            >


                <input
                    type="text"
                    name="search"
                    placeholder="Search booking ID, customer, mobile, service..."
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >


                <select name="status">


                    <option value="">
                        All Status
                    </option>


                    <option
                        value="pending"
                        <?php
                        echo $status_filter ===
                            'pending'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Pending
                    </option>


                    <option
                        value="provider_notified"
                        <?php
                        echo $status_filter ===
                            'provider_notified'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Provider Notified
                    </option>


                    <option
                        value="accepted"
                        <?php
                        echo $status_filter ===
                            'accepted'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Accepted
                    </option>


                    <option
                        value="rescheduled"
                        <?php
                        echo $status_filter ===
                            'rescheduled'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Rescheduled
                    </option>


                    <option
                        value="on_the_way"
                        <?php
                        echo $status_filter ===
                            'on_the_way'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        On The Way
                    </option>


                    <option
                        value="work_started"
                        <?php
                        echo $status_filter ===
                            'work_started'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Work Started
                    </option>


                    <option
                        value="completed"
                        <?php
                        echo $status_filter ===
                            'completed'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Completed
                    </option>


                    <option
                        value="cancelled"
                        <?php
                        echo $status_filter ===
                            'cancelled'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Cancelled
                    </option>


                    <option
                        value="rejected"
                        <?php
                        echo $status_filter ===
                            'rejected'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Rejected
                    </option>


                </select>


                <input
                    type="date"
                    name="date"
                    value="<?php
                    echo htmlspecialchars(
                        $date_filter
                    );
                    ?>"
                >


                <div style="
                    display:flex;
                    gap:7px;
                ">


                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Filter
                    </button>


                    <a
                        href="bookings.php"
                        class="clear-btn"
                    >
                        Clear
                    </a>


                </div>


            </form>


        </div>



        <!-- =================================
             BOOKING TABLE
        ================================== -->

        <div class="table-box">


            <div class="table-header">

                <h2>
                    All Bookings
                </h2>

                <span class="result-count">

                    <?php
                    echo $total_results;
                    ?>
                    result(s)

                </span>

            </div>



            <?php if (
                count($bookings) > 0
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
                                    Schedule
                                </th>

                                <th>
                                    Provider
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php
                            foreach (
                                $bookings
                                as $booking
                            ):
                            ?>


                                <tr>


                                    <!-- BOOKING -->

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


                                        <div
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y",
                                                    strtotime(
                                                        $booking[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            );
                                            ?>

                                        </div>

                                    </td>



                                    <!-- CUSTOMER -->

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
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'mobile'
                                                ]
                                            );
                                            ?>

                                        </div>


                                        <div
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'city'
                                                ]
                                                ?? ''
                                            );
                                            ?>

                                        </div>

                                    </td>



                                    <!-- SERVICE -->

                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'service_name'
                                                ]
                                                ?? '-'
                                            );
                                            ?>

                                        </strong>


                                        <div
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'category'
                                                ]
                                                ?? ''
                                            );
                                            ?>

                                        </div>

                                    </td>



                                    <!-- SCHEDULE -->

                                    <td>

                                        <strong>

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

                                        </strong>


                                        <div
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'booking_time'
                                                ]
                                            );
                                            ?>

                                        </div>

                                    </td>



                                    <!-- PROVIDER -->

                                    <td>

                                        <?php
                                        if (
                                            !empty(
                                                $booking[
                                                    'provider_name'
                                                ]
                                            )
                                        ):
                                        ?>


                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'provider_name'
                                                    ]
                                                );
                                                ?>

                                            </strong>


                                            <div
                                                class="small"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $booking[
                                                        'provider_mobile'
                                                    ]
                                                    ?? ''
                                                );
                                                ?>

                                            </div>


                                        <?php else: ?>


                                            <span
                                                class="small"
                                            >
                                                Not Assigned
                                            </span>


                                        <?php endif; ?>

                                    </td>



                                    <!-- AMOUNT -->

                                    <td>

                                        <span
                                            class="amount"
                                        >

                                            ₹<?php
                                            echo number_format(
                                                (float)
                                                $booking[
                                                    'price'
                                                ],
                                                2
                                            );
                                            ?>

                                        </span>


                                        <div
                                            class="small"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $booking[
                                                    'payment_mode'
                                                ]
                                            );
                                            ?>

                                        </div>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                            status
                                            <?php
                                            echo statusClass(
                                                $booking[
                                                    'booking_status'
                                                ]
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                statusText(
                                                    $booking[
                                                        'booking_status'
                                                    ]
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>



                                    <!-- ACTION -->

                                    <td>


                                        <button
                                            type="button"
                                            class="action-btn"
                                            onclick="openModal(
                                                <?php
                                                echo (int)
                                                    $booking[
                                                        'id'
                                                    ];
                                                ?>
                                            )"
                                        >
                                            Manage
                                        </button>


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
                                                name="action"
                                                value="cancel_booking"
                                            >


                                            <input
                                                type="hidden"
                                                name="booking_db_id"
                                                value="<?php
                                                echo (int)
                                                    $booking[
                                                        'id'
                                                    ];
                                                ?>"
                                            >


                                            <?php if (
                                                $booking[
                                                    'booking_status'
                                                ]
                                                !==
                                                'completed'
                                                &&
                                                $booking[
                                                    'booking_status'
                                                ]
                                                !==
                                                'cancelled'
                                            ): ?>


                                                <button
                                                    type="submit"
                                                    class="
                                                    action-btn
                                                    cancel-action
                                                    "
                                                >

                                                    Cancel

                                                </button>


                                            <?php endif; ?>


                                        </form>


                                    </td>


                                </tr>


                                <!-- =================================
                                     HIDDEN DETAILS
                                ================================== -->

                                <tr
                                    id="booking-details-<?php
                                    echo (int)
                                        $booking['id'];
                                    ?>"
                                    style="display:none;"
                                >

                                    <td
                                        colspan="8"
                                        style="
                                        background:#f8fafc;
                                        "
                                    >

                                        <strong>
                                            Booking Details
                                        </strong>

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


    </div>


</main>



<!-- =========================================
     MANAGE MODAL
========================================= -->

<?php
foreach (
    $bookings
    as $booking
):
?>


<div
    class="modal"
    id="modal-<?php
    echo (int)
        $booking['id'];
    ?>"
>


    <div class="modal-box">


        <div class="modal-header">


            <h2>
                Booking Details
            </h2>


            <button
                type="button"
                class="close-btn"
                onclick="closeModal(
                    <?php
                    echo (int)
                        $booking['id'];
                    ?>
                )"
            >
                ×
            </button>


        </div>


        <div class="modal-body">


            <!-- DETAILS -->

            <div class="detail-grid">


                <div class="detail">

                    <div class="detail-label">
                        Booking ID
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'booking_id'
                            ]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Status
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            statusText(
                                $booking[
                                    'booking_status'
                                ]
                            )
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Customer
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'customer_name'
                            ]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Mobile
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'mobile'
                            ]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Service
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'service_name'
                            ]
                            ?? '-'
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Category
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'category'
                            ]
                            ?? '-'
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Booking Date
                    </div>

                    <div class="detail-value">

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

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Booking Time
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'booking_time'
                            ]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Amount
                    </div>

                    <div class="detail-value">

                        ₹<?php
                        echo number_format(
                            (float)
                            $booking[
                                'price'
                            ],
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
                            $booking[
                                'payment_mode'
                            ]
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            ucfirst(
                                $booking[
                                    'payment_status'
                                ]
                            )
                        );
                        ?>

                    </div>

                </div>


                <div
                    class="detail"
                    style="
                    grid-column:1/-1;
                    "
                >

                    <div class="detail-label">
                        Address
                    </div>

                    <div class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $booking[
                                'address'
                            ]
                        );
                        ?>


                        <?php if (
                            !empty(
                                $booking[
                                    'landmark'
                                ]
                            )
                        ): ?>

                            <br>

                            Landmark:
                            <?php
                            echo htmlspecialchars(
                                $booking[
                                    'landmark'
                                ]
                            );
                            ?>

                        <?php endif; ?>

                    </div>

                </div>


                <div
                    class="detail"
                    style="
                    grid-column:1/-1;
                    "
                >

                    <div class="detail-label">
                        Problem / Requirement
                    </div>

                    <div class="detail-value">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $booking[
                                    'problem_description'
                                ]
                                ?? '-'
                            )
                        );
                        ?>

                    </div>

                </div>


            </div>



            <!-- =================================
                 ASSIGN PROVIDER
            ================================== -->

            <div class="modal-form">


                <h3>
                    Assign Service Provider
                </h3>


                <form method="POST">


                    <input
                        type="hidden"
                        name="action"
                        value="assign_provider"
                    >


                    <input
                        type="hidden"
                        name="booking_db_id"
                        value="<?php
                        echo (int)
                            $booking['id'];
                        ?>"
                    >


                    <select
                        name="provider_id"
                        required
                    >


                        <option value="">
                            Select Provider
                        </option>


                        <?php
                        foreach (
                            $providers
                            as $provider
                        ):
                        ?>


                            <option
                                value="<?php
                                echo (int)
                                    $provider['id'];
                                ?>"
                                <?php
                                echo
                                    (
                                        (int)
                                        ($booking['provider_id'] ?? 0)
                                        ===
                                        (int)
                                        $provider['id']
                                    )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $provider[
                                        'provider_name'
                                    ]
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $provider[
                                        'area'
                                    ]
                                    ?? ''
                                );
                                ?>

                            </option>


                        <?php
                        endforeach;
                        ?>


                    </select>


                    <button type="submit">

                        Assign Provider

                    </button>


                </form>


            </div>



            <!-- =================================
                 CHANGE STATUS
            ================================== -->

            <div class="modal-form">


                <h3>
                    Change Booking Status
                </h3>


                <form method="POST">


                    <input
                        type="hidden"
                        name="action"
                        value="change_status"
                    >


                    <input
                        type="hidden"
                        name="booking_db_id"
                        value="<?php
                        echo (int)
                            $booking['id'];
                        ?>"
                    >


                    <select
                        name="new_status"
                        required
                    >


                        <option value="">
                            Select Status
                        </option>


                        <option
                            value="pending"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'pending'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Pending
                        </option>


                        <option
                            value="provider_notified"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] ===
                            'provider_notified'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Provider Notified
                        </option>


                        <option
                            value="accepted"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'accepted'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Accepted
                        </option>


                        <option
                            value="rejected"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'rejected'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Rejected
                        </option>


                        <option
                            value="rescheduled"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'rescheduled'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Rescheduled
                        </option>


                        <option
                            value="on_the_way"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'on_the_way'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            On The Way
                        </option>


                        <option
                            value="work_started"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'work_started'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Work Started
                        </option>


                        <option
                            value="completed"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'completed'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Completed
                        </option>


                        <option
                            value="cancelled"
                            <?php
                            echo $booking[
                                'booking_status'
                            ] === 'cancelled'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Cancelled
                        </option>


                    </select>


                    <button type="submit">

                        Update Status

                    </button>


                </form>


            </div>


        </div>


    </div>


</div>


<?php
endforeach;
?>



<script>

/* =========================================
   SIDEBAR
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


/* =========================================
   MODAL
========================================= */

function openModal(id) {

    const modal =
        document.getElementById(
            "modal-" + id
        );

    if (modal) {

        modal.style.display = "block";

        document.body.style.overflow =
            "hidden";
    }
}


function closeModal(id) {

    const modal =
        document.getElementById(
            "modal-" + id
        );

    if (modal) {

        modal.style.display = "none";

        document.body.style.overflow =
            "auto";
    }
}


/* =========================================
   CLOSE MODAL WHEN CLICK OUTSIDE
========================================= */

window.addEventListener(
    "click",
    function(event) {

        if (
            event.target.classList
            .contains("modal")
        ) {

            event.target.style.display =
                "none";

            document.body.style.overflow =
                "auto";
        }

    }
);

</script>


<script src="admin-ui.js"></script>
</body>

</html>
