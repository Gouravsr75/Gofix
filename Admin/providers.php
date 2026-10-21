
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan provider profile, approval aur status actions handle hote hain.

$admin_name = $_SESSION['admin_name'] ?? "Administrator";

$message = "";
$message_type = "success";

function deleteProviderGovernmentDocument($storedName)
{
    if (!is_string($storedName) || $storedName === '' || basename($storedName) !== $storedName) {
        return;
    }

    $storageDirectory =
        dirname(__DIR__, 4) .
        DIRECTORY_SEPARATOR . 'fixlify_private' .
        DIRECTORY_SEPARATOR . 'provider_documents';
    $storageRoot = realpath($storageDirectory);
    $documentPath = realpath($storageDirectory . DIRECTORY_SEPARATOR . $storedName);

    if (!$storageRoot || !$documentPath) {
        return;
    }

    $storagePrefix = rtrim($storageRoot, '\\/') . DIRECTORY_SEPARATOR;
    if (stripos($documentPath, $storagePrefix) === 0 && is_file($documentPath)) {
        @unlink($documentPath);
    }
}

function deleteProviderPhoto($storedName)
{
    if (!is_string($storedName) || $storedName === '' || basename($storedName) !== $storedName) {
        return;
    }

    $storageDirectory =
        dirname(__DIR__, 4) .
        DIRECTORY_SEPARATOR . 'fixlify_private' .
        DIRECTORY_SEPARATOR . 'provider_photos';
    $storageRoot = realpath($storageDirectory);
    $photoPath = realpath($storageDirectory . DIRECTORY_SEPARATOR . $storedName);

    if (!$storageRoot || !$photoPath) {
        return;
    }

    $storagePrefix = rtrim($storageRoot, '\\/') . DIRECTORY_SEPARATOR;
    if (stripos($photoPath, $storagePrefix) === 0 && is_file($photoPath)) {
        @unlink($photoPath);
    }
}

/* =========================
   PROVIDER ACTIONS
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";
    $provider_id = (int)($_POST['provider_id'] ?? 0);

    if ($provider_id <= 0) {
        $message = "Invalid provider.";
        $message_type = "error";
    } else {

        /* =========================
           CHANGE PROVIDER STATUS
        ========================= */
        if ($action === "change_status") {

            $new_status = $_POST['status'] ?? "";

            $allowed_status = [
                "pending",
                "active",
                "inactive",
                "blocked"
            ];

            if (!in_array($new_status, $allowed_status, true)) {

                $message = "Invalid status.";
                $message_type = "error";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE service_providers
                     SET status = ?
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "si",
                        $new_status,
                        $provider_id
                    );

                    if ($stmt->execute()) {
                        $message = "Provider status updated successfully.";
                    } else {
                        $message = "Unable to update provider status.";
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
           DELETE PROVIDER
        ========================= */
        elseif ($action === "delete") {

            /*
             * Check if provider has bookings.
             * We do not delete providers having booking history.
             */

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM bookings
                 WHERE provider_id = ?"
            );

            $stmt->bind_param("i", $provider_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $booking_data = $result->fetch_assoc();

            $stmt->close();

            $total_bookings = (int)($booking_data['total'] ?? 0);

            if ($total_bookings > 0) {

                $message = "This provider cannot be deleted because booking history exists.";
                $message_type = "error";

            } else {

                $governmentDocumentName = "";
                $documentStmt = $conn->prepare(
                    "SELECT government_document_file, provider_photo_file
                     FROM service_providers
                     WHERE id = ?
                     LIMIT 1"
                );

                if (!$documentStmt) {
                    $message = "Database error. The provider was not deleted.";
                    $message_type = "error";
                } else {
                    $documentStmt->bind_param("i", $provider_id);

                    if ($documentStmt->execute()) {
                        $providerRecord = $documentStmt->get_result()->fetch_assoc();
                        $governmentDocumentName = $providerRecord['government_document_file'] ?? "";
                        $providerPhotoName = $providerRecord['provider_photo_file'] ?? "";
                    } else {
                        $message = "Database error. The provider was not deleted.";
                        $message_type = "error";
                    }

                    $documentStmt->close();
                }

                if ($message_type !== "error") {
                    $stmt = $conn->prepare(
                        "DELETE FROM service_providers
                         WHERE id = ?"
                    );

                    if ($stmt) {

                        $stmt->bind_param(
                            "i",
                            $provider_id
                        );

                        if ($stmt->execute()) {
                            deleteProviderGovernmentDocument($governmentDocumentName);
                            deleteProviderPhoto($providerPhotoName ?? "");
                            $message = "Provider deleted successfully.";
                        } else {
                            $message = "Unable to delete provider.";
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
        sp.provider_name LIKE ?
        OR sp.mobile LIKE ?
        OR sp.email LIKE ?
        OR sp.area LIKE ?
        OR sp.city LIKE ?
        OR sp.district LIKE ?
    )";

    $search_value = "%" . $search . "%";

    for ($i = 0; $i < 6; $i++) {
        $params[] = $search_value;
        $types .= "s";
    }
}


/* STATUS FILTER */
if ($status_filter !== "") {

    $allowed_filter = [
        "pending",
        "active",
        "inactive",
        "blocked"
    ];

    if (in_array($status_filter, $allowed_filter, true)) {

        $where[] = "sp.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}


/* =========================
   PROVIDER QUERY
========================= */

$sql = "
    SELECT
        sp.*,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.provider_id = sp.id
        ) AS total_jobs,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.provider_id = sp.id
            AND b.booking_status = 'completed'
        ) AS completed_jobs,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.provider_id = sp.id
            AND b.booking_status IN (
                'pending',
                'provider_notified',
                'accepted',
                'rescheduled',
                'on_the_way',
                'work_started'
            )
        ) AS active_jobs

    FROM service_providers sp
";


if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY sp.id DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}


if (!empty($params)) {

    $bind_names = [];
    $bind_names[] = $types;

    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_names
    );
}


$stmt->execute();
$result = $stmt->get_result();


/* =========================
   DASHBOARD COUNTS
========================= */

function getProviderCount($conn, $status = null)
{
    if ($status === null) {

        $sql = "SELECT COUNT(*) AS total
                FROM service_providers";

        $result = $conn->query($sql);

    } else {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM service_providers
             WHERE status = ?"
        );

        $stmt->bind_param("s", $status);
        $stmt->execute();

        $result = $stmt->get_result();

        $stmt->close();
    }

    $row = $result->fetch_assoc();

    return (int)($row['total'] ?? 0);
}


$total_providers    = getProviderCount($conn);
$pending_providers  = getProviderCount($conn, "pending");
$active_providers   = getProviderCount($conn, "active");
$inactive_providers = getProviderCount($conn, "inactive");
$blocked_providers  = getProviderCount($conn, "blocked");


/* =========================
   HELPER FUNCTIONS
========================= */

function statusClass($status)
{
    return "status-" . str_replace("_", "-", strtolower($status));
}


function statusText($status)
{
    return ucwords(str_replace("_", " ", $status));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Providers - Admin</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
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

    padding: 0 10px 25px;
}

.logo span {
    color: #1264d6;
}

.menu-title {

    color: #64748b;
    font-size: 11px;

    text-transform: uppercase;

    padding: 15px 10px 8px;
}

.sidebar a {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #cbd5e1;

    text-decoration: none;

    padding: 12px 12px;

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
   MAIN
========================= */

.main {

    margin-left: 240px;

    padding: 25px;
}


/* =========================
   TOP BAR
========================= */

.topbar {

    display: flex;

    justify-content: space-between;

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

    box-shadow: 0 2px 10px rgba(0,0,0,.05);

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
        repeat(5, minmax(150px, 1fr));

    gap: 15px;

    margin-bottom: 25px;
}

.stat-card {

    background: white;

    border-radius: 12px;

    padding: 18px;

    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.stat-card .label {

    color: #64748b;

    font-size: 13px;

    margin-bottom: 8px;
}

.stat-card .number {

    font-size: 27px;

    font-weight: 700;
}


/* =========================
   FILTER
========================= */

.filter-box {

    background: white;

    padding: 18px;

    border-radius: 12px;

    box-shadow: 0 3px 15px rgba(0,0,0,.05);

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

    min-width: 260px;

    flex: 1;
}

.filter-form input:focus,
.filter-form select:focus {

    border-color: #1264d6;
}

.btn {

    border: none;

    height: 42px;

    padding: 0 18px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 14px;

    font-weight: 600;
}

.btn-primary {

    background: #1264d6;
    color: white;
}

.btn-light {

    background: #e9eef5;
    color: #172033;
}

.btn-danger {

    background: #dc2626;
    color: white;
}


/* =========================
   TABLE
========================= */

.table-box {

    background: white;

    border-radius: 12px;

    box-shadow: 0 3px 15px rgba(0,0,0,.05);

    overflow: hidden;
}

.table-header {

    padding: 18px;

    border-bottom: 1px solid #edf0f5;

    font-size: 17px;

    font-weight: 700;
}

.table-scroll {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
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

    border-top: 1px solid #edf0f5;

    font-size: 13px;

    vertical-align: middle;
}

.provider-name {

    font-weight: 700;

    color: #172033;

    margin-bottom: 4px;
}

.provider-mobile {

    color: #64748b;

    font-size: 12px;
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

.status-pending {

    background: #fef3c7;
    color: #92400e;
}

.status-inactive {

    background: #e2e8f0;
    color: #475569;
}

.status-blocked {

    background: #fee2e2;
    color: #991b1b;
}


/* =========================
   ACTION BUTTONS
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

.activate-btn {

    background: #dcfce7;

    color: #166534;
}

.inactive-btn {

    background: #f1f5f9;

    color: #475569;
}

.block-btn {

    background: #fee2e2;

    color: #991b1b;
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

    background: rgba(15,23,42,.65);

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

    font-size: 11px;

    color: #64748b;

    margin-bottom: 5px;

    text-transform: uppercase;
}

.detail-value {

    font-size: 14px;

    font-weight: 600;

    word-break: break-word;
}

.provider-photo-preview {
    width: 112px;
    height: 112px;
    display: block;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #dbeafe;
    background: #eaf2ff;
}


/* =========================
   MOBILE MENU
========================= */

.mobile-top {

    display: none;

    background: #0f172a;

    color: white;

    height: 58px;

    align-items: center;

    justify-content: space-between;

    padding: 0 15px;
}

.menu-toggle {

    background: #1264d6;

    border: none;

    color: white;

    width: 40px;

    height: 40px;

    border-radius: 7px;

    font-size: 20px;

    cursor: pointer;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(3, 1fr);
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

        align-items: flex-start;

        gap: 10px;

        flex-direction: column;
    }

    .admin-name {

        display: none;
    }

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

        gap: 10px;
    }

    .stat-card {

        padding: 14px;
    }

    .stat-card .number {

        font-size: 23px;
    }

    .filter-form {

        flex-direction: column;
    }

    .filter-form input {

        min-width: 100%;
    }

    .filter-form select,
    .filter-form button {

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

        grid-template-columns: 1fr 1fr;
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

    <strong>Gofix Admin</strong>

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

            <h1>Service Providers</h1>

            <p>
                Manage and monitor all registered service providers.
            </p>

        </div>

        <div class="admin-name">

            👨‍💼
            <?php echo htmlspecialchars($admin_name); ?>

        </div>

    </div>


    <!-- ALERT -->

    <?php if ($message !== ""): ?>

        <div class="alert <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         STATS
    ========================= -->

    <div class="stats">

        <div class="stat-card">

            <div class="label">
                Total Providers
            </div>

            <div class="number">
                <?php echo $total_providers; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="label">
                Pending
            </div>

            <div class="number">
                <?php echo $pending_providers; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="label">
                Active
            </div>

            <div class="number">
                <?php echo $active_providers; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="label">
                Inactive
            </div>

            <div class="number">
                <?php echo $inactive_providers; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="label">
                Blocked
            </div>

            <div class="number">
                <?php echo $blocked_providers; ?>
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
                placeholder="Search name, mobile, email, area..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <select name="status">

                <option value="">
                    All Status
                </option>

                <option value="pending"
                    <?php echo $status_filter === "pending" ? "selected" : ""; ?>>
                    Pending
                </option>

                <option value="active"
                    <?php echo $status_filter === "active" ? "selected" : ""; ?>>
                    Active
                </option>

                <option value="inactive"
                    <?php echo $status_filter === "inactive" ? "selected" : ""; ?>>
                    Inactive
                </option>

                <option value="blocked"
                    <?php echo $status_filter === "blocked" ? "selected" : ""; ?>>
                    Blocked
                </option>

            </select>

            <button
                type="submit"
                class="btn btn-primary">
                Search
            </button>

            <a
                href="providers.php"
                class="btn btn-light"
                style="display:flex;align-items:center;justify-content:center;text-decoration:none;">
                Reset
            </a>

        </form>

    </div>


    <!-- =========================
         PROVIDER TABLE
    ========================= -->

    <div class="table-box">

        <div class="table-header">

            Provider List

        </div>

        <div class="table-scroll">

            <?php if ($result->num_rows > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                Provider
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Services
                            </th>

                            <th>
                                Experience
                            </th>

                            <th>
                                Jobs
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Joined
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($provider = $result->fetch_assoc()): ?>

                        <?php
                        $providerModalData = $provider;
                        $providerModalData['has_government_document'] =
                            !empty($provider['government_document_file']);
                        $providerModalData['has_provider_photo'] =
                            !empty($provider['provider_photo_file']);
                        unset(
                            $providerModalData['government_document_file'],
                            $providerModalData['provider_photo_file'],
                            $providerModalData['password']
                        );
                        ?>

                        <tr>

                            <!-- PROVIDER -->

                            <td>

                                <div class="provider-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $provider['provider_name']
                                    );
                                    ?>

                                </div>

                                <div class="provider-mobile">

                                    <?php
                                    echo htmlspecialchars(
                                        $provider['mobile']
                                    );
                                    ?>

                                </div>

                                <?php if (!empty($provider['email'])): ?>

                                    <div class="provider-mobile">

                                        <?php
                                        echo htmlspecialchars(
                                            $provider['email']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- LOCATION -->

                            <td>

                                <?php

                                $location = [];

                                if (!empty($provider['area'])) {
                                    $location[] = $provider['area'];
                                }

                                if (!empty($provider['city'])) {
                                    $location[] = $provider['city'];
                                }

                                if (!empty($provider['district'])) {
                                    $location[] = $provider['district'];
                                }

                                echo htmlspecialchars(
                                    !empty($location)
                                    ? implode(", ", $location)
                                    : "Not provided"
                                );

                                ?>

                                <?php if (!empty($provider['pincode'])): ?>

                                    <br>

                                    <small>
                                        PIN:
                                        <?php
                                        echo htmlspecialchars(
                                            $provider['pincode']
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- SERVICES -->

                            <td>

                                <?php

                                $services = trim(
                                    $provider['service_categories'] ?? ""
                                );

                                if ($services !== "") {

                                    echo htmlspecialchars(
                                        $services
                                    );

                                } else {

                                    echo "Not specified";
                                }

                                ?>

                            </td>


                            <!-- EXPERIENCE -->

                            <td>

                                <?php
                                echo (int)$provider['experience'];
                                ?>

                                years

                            </td>


                            <!-- JOBS -->

                            <td>

                                <strong>
                                    <?php
                                    echo (int)$provider['total_jobs'];
                                    ?>
                                </strong>

                                <br>

                                <small>
                                    Completed:
                                    <?php
                                    echo (int)$provider['completed_jobs'];
                                    ?>
                                </small>

                                <?php if ((int)$provider['active_jobs'] > 0): ?>

                                    <br>

                                    <small>
                                        Active:
                                        <?php
                                        echo (int)$provider['active_jobs'];
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="status
                                    <?php
                                    echo statusClass(
                                        $provider['status']
                                    );
                                    ?>">

                                    <?php
                                    echo statusText(
                                        $provider['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- JOINED -->

                            <td>

                                <?php

                                echo !empty($provider['created_at'])
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $provider['created_at']
                                        )
                                    )
                                    : "-";

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div class="action-buttons">

                                    <!-- VIEW -->

                                    <button
                                        type="button"
                                        class="action-btn view-btn"
                                        onclick='viewProvider(
                                            <?php
                                            echo json_encode(
                                                $providerModalData,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT |
                                                JSON_HEX_AMP
                                            );
                                            ?>
                                        )'>

                                        View

                                    </button>


                                    <!-- PENDING -->

                                    <?php if ($provider['status'] === "pending"): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="active">

                                            <button
                                                class="action-btn activate-btn"
                                                type="submit">

                                                Approve

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- ACTIVE -->

                                    <?php if ($provider['status'] === "active"): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="inactive">

                                            <button
                                                class="action-btn inactive-btn"
                                                type="submit">

                                                Deactivate

                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="blocked">

                                            <button
                                                class="action-btn block-btn"
                                                type="submit">

                                                Block

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- INACTIVE -->

                                    <?php if ($provider['status'] === "inactive"): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="active">

                                            <button
                                                class="action-btn activate-btn"
                                                type="submit">

                                                Activate

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- BLOCKED -->

                                    <?php if ($provider['status'] === "blocked"): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="active">

                                            <button
                                                class="action-btn activate-btn"
                                                type="submit">

                                                Unblock

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- DELETE -->

                                    <?php if ((int)$provider['total_jobs'] === 0): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm(
                                                'Are you sure you want to delete this provider?'
                                            );">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete">

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int)$provider['id']; ?>">

                                            <button
                                                class="action-btn delete-btn"
                                                type="submit">

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
                        No providers found
                    </h3>

                    <p>
                        No service provider matches your search or filter.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- =========================
     PROVIDER MODAL
========================= -->

<div
    class="modal"
    id="providerModal"
    onclick="closeOutside(event)">

    <div class="modal-box">

        <button
            class="modal-close"
            onclick="closeModal()">

            ×

        </button>

        <h2
            class="modal-title"
            id="modalProviderName">

            Provider Details

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
                    Village
                </div>

                <div
                    class="detail-value"
                    id="modalVillage">
                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Area
                </div>

                <div
                    class="detail-value"
                    id="modalArea">
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
                    District
                </div>

                <div
                    class="detail-value"
                    id="modalDistrict">
                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    PIN Code
                </div>

                <div
                    class="detail-value"
                    id="modalPincode">
                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Experience
                </div>

                <div
                    class="detail-value"
                    id="modalExperience">
                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Services
                </div>

                <div
                    class="detail-value"
                    id="modalServices">
                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Address
                </div>

                <div
                    class="detail-value"
                    id="modalAddress">
                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Government ID
                </div>

                <div
                    class="detail-value"
                    id="modalGovernmentDocument">
                </div>

            </div>


            <div class="detail-item full">

                <div class="detail-label">
                    Provider Photo
                </div>

                <div
                    class="detail-value"
                    id="modalProviderPhoto">
                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Total Jobs
                </div>

                <div
                    class="detail-value"
                    id="modalTotalJobs">
                </div>

            </div>


            <div class="detail-item">

                <div class="detail-label">
                    Completed Jobs
                </div>

                <div
                    class="detail-value"
                    id="modalCompletedJobs">
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
                    Joined
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
   PROVIDER MODAL
========================= */

function viewProvider(provider) {

    document.getElementById(
        "modalProviderName"
    ).textContent =
        provider.provider_name || "Provider Details";


    document.getElementById(
        "modalMobile"
    ).textContent =
        provider.mobile || "Not provided";


    document.getElementById(
        "modalEmail"
    ).textContent =
        provider.email || "Not provided";


    document.getElementById(
        "modalVillage"
    ).textContent =
        provider.village || "Not provided";


    document.getElementById(
        "modalArea"
    ).textContent =
        provider.area || "Not provided";


    document.getElementById(
        "modalCity"
    ).textContent =
        provider.city || "Not provided";


    document.getElementById(
        "modalDistrict"
    ).textContent =
        provider.district || "Not provided";


    document.getElementById(
        "modalPincode"
    ).textContent =
        provider.pincode || "Not provided";


    document.getElementById(
        "modalExperience"
    ).textContent =
        (provider.experience || 0) + " years";


    document.getElementById(
        "modalServices"
    ).textContent =
        provider.service_categories || "Not specified";


    document.getElementById(
        "modalAddress"
    ).textContent =
        provider.address || "Not provided";

    const governmentDocument = document.getElementById(
        "modalGovernmentDocument"
    );
    governmentDocument.textContent = "";

    if (provider.has_government_document && provider.id) {
        const documentLink = document.createElement("a");
        documentLink.href =
            "provider_document.php?provider_id=" +
            encodeURIComponent(provider.id);
        documentLink.textContent = "Download submitted document";
        documentLink.target = "_blank";
        documentLink.rel = "noopener";
        governmentDocument.appendChild(documentLink);
    } else {
        governmentDocument.textContent = "Not uploaded";
    }

    const providerPhoto = document.getElementById("modalProviderPhoto");
    providerPhoto.textContent = "";

    if (provider.has_provider_photo && provider.id) {
        const photo = document.createElement("img");
        photo.className = "provider-photo-preview";
        photo.src = "provider_photo.php?provider_id=" + encodeURIComponent(provider.id);
        photo.alt = "Provider profile photo";
        photo.loading = "lazy";
        providerPhoto.appendChild(photo);
    } else {
        providerPhoto.textContent = "Not uploaded";
    }


    document.getElementById(
        "modalTotalJobs"
    ).textContent =
        provider.total_jobs || "0";


    document.getElementById(
        "modalCompletedJobs"
    ).textContent =
        provider.completed_jobs || "0";


    document.getElementById(
        "modalStatus"
    ).textContent =
        provider.status || "Unknown";


    let joined = "Not available";

    if (provider.created_at) {

        const date = new Date(
            provider.created_at.replace(" ", "T")
        );

        if (!isNaN(date.getTime())) {

            joined = date.toLocaleDateString(
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
    ).textContent = joined;


    document
        .getElementById("providerModal")
        .classList.add("show");

}


function closeModal() {

    document
        .getElementById("providerModal")
        .classList.remove("show");

}


function closeOutside(event) {

    if (
        event.target ===
        document.getElementById("providerModal")
    ) {

        closeModal();

    }

}


/* ESC KEY */

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
