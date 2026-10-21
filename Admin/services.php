
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan services add, edit aur active/inactive ki jaati hain.

$admin_name = $_SESSION['admin_name'] ?? "Administrator";

$message = "";
$message_type = "success";


/* =========================
   UPLOAD DIRECTORY
========================= */

$upload_dir = "../uploads/services/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/* =========================
   HELPER: IMAGE DELETE
========================= */

function deleteServiceImage($filename)
{
    if (empty($filename)) {
        return;
    }

    $file = "../uploads/services/" . basename($filename);

    if (file_exists($file)) {
        @unlink($file);
    }
}


/* =========================
   ADD SERVICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";


    /* =========================
       ADD
    ========================= */

    if ($action === "add") {

        $category = trim($_POST['category'] ?? "");
        $service_name = trim($_POST['service_name'] ?? "");
        $description = trim($_POST['description'] ?? "");
        $price = (float)($_POST['price'] ?? 0);
        $status = isset($_POST['status']) ? 1 : 0;

        if (
            $category === "" ||
            $service_name === ""
        ) {

            $message = "Category and service name are required.";
            $message_type = "error";

        } else {

            $image_name = null;

            /* IMAGE UPLOAD */

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['image']['error'] !== UPLOAD_ERR_OK
                ) {

                    $message = "Image upload failed.";
                    $message_type = "error";

                } else {

                    $allowed_types = [
                        "image/jpeg",
                        "image/jpg",
                        "image/png",
                        "image/webp"
                    ];

                    $file_type =
                        mime_content_type(
                            $_FILES['image']['tmp_name']
                        );

                    if (!in_array(
                        $file_type,
                        $allowed_types,
                        true
                    )) {

                        $message =
                            "Only JPG, JPEG, PNG and WEBP images are allowed.";

                        $message_type = "error";

                    } elseif (
                        $_FILES['image']['size'] >
                        5 * 1024 * 1024
                    ) {

                        $message =
                            "Image size must be less than 5 MB.";

                        $message_type = "error";

                    } else {

                        $extension =
                            strtolower(
                                pathinfo(
                                    $_FILES['image']['name'],
                                    PATHINFO_EXTENSION
                                )
                            );

                        $image_name =
                            "service_" .
                            time() .
                            "_" .
                            uniqid() .
                            "." .
                            $extension;

                        $target =
                            $upload_dir .
                            $image_name;


                        if (
                            !move_uploaded_file(
                                $_FILES['image']['tmp_name'],
                                $target
                            )
                        ) {

                            $message =
                                "Unable to save uploaded image.";

                            $message_type = "error";

                            $image_name = null;
                        }
                    }
                }
            }


            if (
                $message_type === "success"
            ) {

                $stmt = $conn->prepare(
                    "INSERT INTO services
                    (
                        category,
                        service_name,
                        description,
                        price,
                        image,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "sssdsi",
                        $category,
                        $service_name,
                        $description,
                        $price,
                        $image_name,
                        $status
                    );

                    if ($stmt->execute()) {

                        $message =
                            "Service added successfully.";

                    } else {

                        if ($image_name) {
                            deleteServiceImage($image_name);
                        }

                        $message =
                            "Unable to add service.";

                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    if ($image_name) {
                        deleteServiceImage($image_name);
                    }

                    $message =
                        "Database error.";

                    $message_type = "error";
                }
            }
        }
    }


    /* =========================
       EDIT SERVICE
    ========================= */

    elseif ($action === "edit") {

        $service_id =
            (int)($_POST['service_id'] ?? 0);

        $category =
            trim($_POST['category'] ?? "");

        $service_name =
            trim($_POST['service_name'] ?? "");

        $description =
            trim($_POST['description'] ?? "");

        $price =
            (float)($_POST['price'] ?? 0);

        $status =
            isset($_POST['status'])
            ? 1
            : 0;


        if (
            $service_id <= 0 ||
            $category === "" ||
            $service_name === ""
        ) {

            $message =
                "Invalid service details.";

            $message_type = "error";

        } else {

            /* GET OLD IMAGE */

            $stmt = $conn->prepare(
                "SELECT image
                 FROM services
                 WHERE id = ?"
            );

            $old_image = null;

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $service_id
                );

                $stmt->execute();

                $old_result =
                    $stmt->get_result();

                $old_data =
                    $old_result->fetch_assoc();

                $old_image =
                    $old_data['image'] ?? null;

                $stmt->close();
            }


            $new_image = $old_image;


            /* NEW IMAGE */

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['image']['error'] !== UPLOAD_ERR_OK
                ) {

                    $message =
                        "Image upload failed.";

                    $message_type = "error";

                } else {

                    $allowed_types = [
                        "image/jpeg",
                        "image/jpg",
                        "image/png",
                        "image/webp"
                    ];

                    $file_type =
                        mime_content_type(
                            $_FILES['image']['tmp_name']
                        );

                    if (!in_array(
                        $file_type,
                        $allowed_types,
                        true
                    )) {

                        $message =
                            "Only JPG, JPEG, PNG and WEBP images are allowed.";

                        $message_type = "error";

                    } elseif (
                        $_FILES['image']['size'] >
                        5 * 1024 * 1024
                    ) {

                        $message =
                            "Image size must be less than 5 MB.";

                        $message_type = "error";

                    } else {

                        $extension =
                            strtolower(
                                pathinfo(
                                    $_FILES['image']['name'],
                                    PATHINFO_EXTENSION
                                )
                            );

                        $new_image =
                            "service_" .
                            time() .
                            "_" .
                            uniqid() .
                            "." .
                            $extension;


                        $target =
                            $upload_dir .
                            $new_image;


                        if (
                            move_uploaded_file(
                                $_FILES['image']['tmp_name'],
                                $target
                            )
                        ) {

                            /* DELETE OLD IMAGE AFTER SUCCESS */

                            if (
                                !empty($old_image) &&
                                $old_image !== $new_image
                            ) {

                                deleteServiceImage(
                                    $old_image
                                );
                            }

                        } else {

                            $message =
                                "Unable to save new image.";

                            $message_type = "error";

                            $new_image =
                                $old_image;
                        }
                    }
                }
            }


            if (
                $message_type === "success"
            ) {

                $stmt = $conn->prepare(
                    "UPDATE services
                     SET
                        category = ?,
                        service_name = ?,
                        description = ?,
                        price = ?,
                        image = ?,
                        status = ?
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "sssd sii",
                        $category,
                        $service_name,
                        $description,
                        $price,
                        $new_image,
                        $status,
                        $service_id
                    );

                    /*
                     * Correct bind type string:
                     * s s s d s i i
                     */

                    $stmt->bind_param(
                        "sssdsii",
                        $category,
                        $service_name,
                        $description,
                        $price,
                        $new_image,
                        $status,
                        $service_id
                    );

                    if ($stmt->execute()) {

                        $message =
                            "Service updated successfully.";

                    } else {

                        $message =
                            "Unable to update service.";

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


    /* =========================
       CHANGE STATUS
    ========================= */

    elseif ($action === "change_status") {

        $service_id =
            (int)($_POST['service_id'] ?? 0);

        $new_status =
            (int)($_POST['status'] ?? 0);

        $new_status =
            $new_status ? 1 : 0;


        if ($service_id <= 0) {

            $message =
                "Invalid service.";

            $message_type = "error";

        } else {

            $stmt = $conn->prepare(
                "UPDATE services
                 SET status = ?
                 WHERE id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $new_status,
                    $service_id
                );

                if ($stmt->execute()) {

                    $message =
                        $new_status
                        ? "Service activated successfully."
                        : "Service deactivated successfully.";

                } else {

                    $message =
                        "Unable to change service status.";

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


    /* =========================
       DELETE SERVICE
    ========================= */

    elseif ($action === "delete") {

        $service_id =
            (int)($_POST['service_id'] ?? 0);


        if ($service_id <= 0) {

            $message =
                "Invalid service.";

            $message_type = "error";

        } else {

            /* CHECK BOOKING HISTORY */

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM bookings
                 WHERE service_id = ?"
            );

            $booking_count = 0;

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $service_id
                );

                $stmt->execute();

                $booking_result =
                    $stmt->get_result();

                $booking_data =
                    $booking_result->fetch_assoc();

                $booking_count =
                    (int)($booking_data['total'] ?? 0);

                $stmt->close();
            }


            if ($booking_count > 0) {

                $message =
                    "This service cannot be deleted because booking history exists. Deactivate it instead.";

                $message_type = "error";

            } else {

                /* GET IMAGE */

                $stmt = $conn->prepare(
                    "SELECT image
                     FROM services
                     WHERE id = ?"
                );

                $image = null;

                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $service_id
                    );

                    $stmt->execute();

                    $image_result =
                        $stmt->get_result();

                    $image_data =
                        $image_result->fetch_assoc();

                    $image =
                        $image_data['image'] ?? null;

                    $stmt->close();
                }


                /* DELETE SERVICE */

                $stmt = $conn->prepare(
                    "DELETE FROM services
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $service_id
                    );

                    if ($stmt->execute()) {

                        if ($image) {
                            deleteServiceImage($image);
                        }

                        $message =
                            "Service deleted successfully.";

                    } else {

                        $message =
                            "Unable to delete service.";

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
   FILTERS
========================= */

$search =
    trim($_GET['search'] ?? "");

$category_filter =
    trim($_GET['category'] ?? "");

$status_filter =
    $_GET['status'] ?? "";


$where = [];
$params = [];
$types = "";


/* SEARCH */

if ($search !== "") {

    $where[] = "(
        service_name LIKE ?
        OR category LIKE ?
        OR description LIKE ?
    )";

    $value =
        "%" . $search . "%";

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sss";
}


/* CATEGORY */

if ($category_filter !== "") {

    $where[] =
        "category = ?";

    $params[] =
        $category_filter;

    $types .= "s";
}


/* STATUS */

if (
    $status_filter === "active" ||
    $status_filter === "inactive"
) {

    $where[] =
        "status = ?";

    $params[] =
        $status_filter === "active"
        ? 1
        : 0;

    $types .= "i";
}


/* =========================
   SERVICES QUERY
========================= */

$sql =
    "SELECT *
     FROM services";


if (!empty($where)) {

    $sql .=
        " WHERE " .
        implode(
            " AND ",
            $where
        );
}


$sql .=
    " ORDER BY id DESC";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "SQL Prepare Error: " .
        $conn->error
    );
}


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
   CATEGORIES
========================= */

$categories = [];

$category_result =
    $conn->query(
        "SELECT DISTINCT category
         FROM services
         WHERE category <> ''
         ORDER BY category ASC"
    );

if ($category_result) {

    while (
        $row =
        $category_result->fetch_assoc()
    ) {

        $categories[] =
            $row['category'];
    }
}


/* =========================
   SERVICE COUNTS
========================= */

$total_services = 0;
$active_services = 0;
$inactive_services = 0;


$count_result =
    $conn->query(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 1) AS active,
            SUM(status = 0) AS inactive
         FROM services"
    );


if ($count_result) {

    $count_data =
        $count_result->fetch_assoc();

    $total_services =
        (int)($count_data['total'] ?? 0);

    $active_services =
        (int)($count_data['active'] ?? 0);

    $inactive_services =
        (int)($count_data['inactive'] ?? 0);
}


/* =========================
   HELPER
========================= */

function serviceImage($image)
{
    if (
        !empty($image) &&
        file_exists(
            "../uploads/services/" .
            basename($image)
        )
    ) {

        return "../uploads/services/" .
               basename($image);
    }

    return "";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Services - Admin</title>


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
        repeat(3, 1fr);

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


/* =========================
   TOOLBAR
========================= */

.toolbar {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;
}


.filter-box {

    background: white;

    padding: 18px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.05);

    flex: 1;
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

    min-width: 220px;

    flex: 1;
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
   ADD BUTTON
========================= */

.add-service-btn {

    white-space: nowrap;

    height: 48px;

    padding: 0 20px;

    border: none;

    border-radius: 8px;

    background: #1264d6;

    color: white;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;
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

    border-top:
        1px solid #edf0f5;

    font-size: 13px;

    vertical-align: middle;
}


/* =========================
   SERVICE IMAGE
========================= */

.service-thumb {

    width: 70px;

    height: 55px;

    border-radius: 7px;

    object-fit: cover;

    display: block;

    background: #f1f5f9;
}


.no-image {

    width: 70px;

    height: 55px;

    border-radius: 7px;

    background: #f1f5f9;

    color: #64748b;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 10px;
}


.service-name {

    font-weight: 700;

    margin-bottom: 4px;
}


.service-description {

    color: #64748b;

    font-size: 12px;

    max-width: 300px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.category-badge {

    display: inline-block;

    background: #e0edff;

    color: #1264d6;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 600;
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


.status-inactive {

    background: #e2e8f0;

    color: #475569;
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


.edit-btn {

    background: #e0edff;

    color: #1264d6;
}


.activate-btn {

    background: #dcfce7;

    color: #166534;
}


.deactivate-btn {

    background: #fef3c7;

    color: #92400e;
}


.delete-btn {

    background: #fee2e2;

    color: #991b1b;
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

    max-width: 600px;

    max-height: 90vh;

    overflow-y: auto;

    border-radius: 14px;

    padding: 25px;

    position: relative;
}


.modal-title {

    font-size: 21px;

    margin-bottom: 20px;

    padding-right: 40px;
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


/* =========================
   FORM
========================= */

.form-group {

    margin-bottom: 16px;
}


.form-group label {

    display: block;

    margin-bottom: 6px;

    font-size: 13px;

    font-weight: 600;
}


.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    border: 1px solid #dbe1ea;

    border-radius: 7px;

    padding: 11px 12px;

    font-family: inherit;

    font-size: 14px;

    outline: none;
}


.form-group textarea {

    resize: vertical;

    min-height: 90px;
}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    border-color: #1264d6;
}


.form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 12px;
}


.current-image {

    margin-bottom: 10px;
}


.current-image img {

    width: 100px;

    height: 75px;

    object-fit: cover;

    border-radius: 7px;

    display: block;
}


.checkbox-row {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 18px;

    font-size: 13px;
}


.checkbox-row input {

    width: 17px;

    height: 17px;
}


.submit-btn {

    width: 100%;

    height: 44px;

    border: none;

    border-radius: 7px;

    background: #1264d6;

    color: white;

    font-weight: 700;

    cursor: pointer;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 900px) {

    .toolbar {

        flex-direction: column;

        align-items: stretch;
    }

    .add-service-btn {

        width: 100%;
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


    .stats {

        grid-template-columns:
            repeat(3, 1fr);

        gap: 10px;
    }


    .stat-card {

        padding: 14px;
    }


    .stat-number {

        font-size: 22px;
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


    .form-row {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 500px) {

    .stats {

        grid-template-columns:
            1fr;
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
                Services
            </h1>

            <p>
                Add, edit and manage all home services.
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
                Total Services
            </div>

            <div class="stat-number">

                <?php
                echo $total_services;
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Active Services
            </div>

            <div class="stat-number">

                <?php
                echo $active_services;
                ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Inactive Services
            </div>

            <div class="stat-number">

                <?php
                echo $inactive_services;
                ?>

            </div>

        </div>

    </div>


    <!-- =========================
         TOOLBAR
    ========================= -->

    <div class="toolbar">


        <!-- FILTER -->

        <div class="filter-box">

            <form
                method="GET"
                class="filter-form">


                <input
                    type="text"
                    name="search"
                    placeholder="Search service..."
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>">


                <select name="category">

                    <option value="">
                        All Categories
                    </option>


                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $category
                            );
                            ?>"
                            <?php
                            echo
                            $category_filter ===
                            $category
                            ? "selected"
                            : "";
                            ?>>

                            <?php
                            echo htmlspecialchars(
                                $category
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <select name="status">

                    <option value="">
                        All Status
                    </option>


                    <option
                        value="active"
                        <?php
                        echo
                        $status_filter === "active"
                        ? "selected"
                        : "";
                        ?>>

                        Active

                    </option>


                    <option
                        value="inactive"
                        <?php
                        echo
                        $status_filter === "inactive"
                        ? "selected"
                        : "";
                        ?>>

                        Inactive

                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary">

                    Search

                </button>


                <a
                    href="services.php"
                    class="btn btn-light">

                    Reset

                </a>

            </form>

        </div>


        <!-- ADD -->

        <button
            type="button"
            class="add-service-btn"
            onclick="openAddModal()">

            + Add Service

        </button>

    </div>


    <!-- =========================
         SERVICE TABLE
    ========================= -->

    <div class="table-box">


        <div class="table-header">

            Service List

        </div>


        <div class="table-scroll">


            <?php if (
                $result->num_rows > 0
            ): ?>


                <table>


                    <thead>

                        <tr>

                            <th>
                                Image
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $service =
                        $result->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- IMAGE -->

                            <td>

                                <?php

                                $image =
                                    serviceImage(
                                        $service[
                                            'image'
                                        ]
                                    );

                                ?>


                                <?php if (
                                    $image !== ""
                                ): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            $image
                                        );
                                        ?>"
                                        class="service-thumb"
                                        alt="Service">

                                <?php else: ?>

                                    <div class="no-image">

                                        No Image

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- SERVICE -->

                            <td>

                                <div
                                    class="service-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $service[
                                            'service_name'
                                        ]
                                    );
                                    ?>

                                </div>


                                <div
                                    class="service-description">

                                    <?php
                                    echo htmlspecialchars(
                                        $service[
                                            'description'
                                        ] ??
                                        ""
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span
                                    class="category-badge">

                                    <?php
                                    echo htmlspecialchars(
                                        $service[
                                            'category'
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <strong>

                                    ₹<?php
                                    echo number_format(
                                        (float)
                                        $service[
                                            'price'
                                        ],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status
                                    status-<?php
                                    echo
                                    $service[
                                        'status'
                                    ]
                                    ? "active"
                                    : "inactive";
                                    ?>">

                                    <?php
                                    echo
                                    $service[
                                        'status'
                                    ]
                                    ? "Active"
                                    : "Inactive";
                                    ?>

                                </span>

                            </td>


                            <!-- CREATED -->

                            <td>

                                <?php

                                echo !empty(
                                    $service[
                                        'created_at'
                                    ]
                                )
                                ?
                                date(
                                    "d M Y",
                                    strtotime(
                                        $service[
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


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="action-btn edit-btn"
                                        onclick='openEditModal(
                                            <?php
                                            echo json_encode(
                                                $service,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT |
                                                JSON_HEX_AMP
                                            );
                                            ?>
                                        )'>

                                        Edit

                                    </button>


                                    <!-- STATUS -->

                                    <?php
                                    if (
                                        (int)$service[
                                            'status'
                                        ] === 1
                                    ):
                                    ?>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Deactivate this service?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">


                                            <input
                                                type="hidden"
                                                name="service_id"
                                                value="<?php
                                                echo (int)
                                                    $service[
                                                        'id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="status"
                                                value="0">


                                            <button
                                                type="submit"
                                                class="action-btn deactivate-btn">

                                                Deactivate

                                            </button>

                                        </form>


                                    <?php else: ?>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Activate this service?'
                                                );
                                            ">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="change_status">


                                            <input
                                                type="hidden"
                                                name="service_id"
                                                value="<?php
                                                echo (int)
                                                    $service[
                                                        'id'
                                                    ];
                                                ?>">


                                            <input
                                                type="hidden"
                                                name="status"
                                                value="1">


                                            <button
                                                type="submit"
                                                class="action-btn activate-btn">

                                                Activate

                                            </button>

                                        </form>


                                    <?php endif; ?>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this service?'
                                            );
                                        ">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">


                                        <input
                                            type="hidden"
                                            name="service_id"
                                            value="<?php
                                            echo (int)
                                                $service[
                                                    'id'
                                                ];
                                            ?>">


                                        <button
                                            type="submit"
                                            class="action-btn delete-btn">

                                            Delete

                                        </button>

                                    </form>


                                </div>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty">

                    <h3>
                        No services found
                    </h3>

                    <p>
                        Add your first service or change the filter.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>


<!-- ==================================================
     ADD / EDIT SERVICE MODAL
================================================== -->

<div
    class="modal"
    id="serviceModal"
    onclick="closeOutside(event)">


    <div class="modal-box">


        <button
            type="button"
            class="modal-close"
            onclick="closeModal()">

            ×

        </button>


        <h2
            class="modal-title"
            id="modalTitle">

            Add Service

        </h2>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="serviceForm">


            <input
                type="hidden"
                name="action"
                id="formAction"
                value="add">


            <input
                type="hidden"
                name="service_id"
                id="serviceId"
                value="">


            <!-- CATEGORY -->

            <div class="form-group">

                <label>
                    Category *
                </label>

                <input
                    type="text"
                    name="category"
                    id="category"
                    placeholder="Example: Electrician"
                    required>

            </div>


            <!-- SERVICE NAME -->

            <div class="form-group">

                <label>
                    Service Name *
                </label>

                <input
                    type="text"
                    name="service_name"
                    id="serviceName"
                    placeholder="Example: Fan Repair"
                    required>

            </div>


            <!-- PRICE -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Price *
                    </label>

                    <input
                        type="number"
                        name="price"
                        id="price"
                        min="0"
                        step="0.01"
                        placeholder="0"
                        required>

                </div>


                <div class="form-group">

                    <label>
                        Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        id="image"
                        accept=".jpg,.jpeg,.png,.webp">

                </div>

            </div>


            <!-- CURRENT IMAGE -->

            <div
                class="current-image"
                id="currentImageBox"
                style="display:none;">

                <label
                    style="
                    display:block;
                    font-size:13px;
                    font-weight:600;
                    margin-bottom:6px;
                    ">

                    Current Image

                </label>


                <img
                    id="currentImage"
                    src=""
                    alt="Current Image">

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    id="description"
                    placeholder="Enter service description..."></textarea>

            </div>


            <!-- STATUS -->

            <label class="checkbox-row">

                <input
                    type="checkbox"
                    name="status"
                    id="status"
                    checked>

                <span>
                    Service is Active
                </span>

            </label>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="submit-btn"
                id="submitButton">

                Add Service

            </button>


        </form>

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
   OPEN ADD
========================= */

function openAddModal() {

    document.getElementById(
        "modalTitle"
    ).textContent =
        "Add Service";


    document.getElementById(
        "formAction"
    ).value =
        "add";


    document.getElementById(
        "serviceId"
    ).value =
        "";


    document.getElementById(
        "category"
    ).value =
        "";


    document.getElementById(
        "serviceName"
    ).value =
        "";


    document.getElementById(
        "price"
    ).value =
        "";


    document.getElementById(
        "description"
    ).value =
        "";


    document.getElementById(
        "status"
    ).checked =
        true;


    document.getElementById(
        "image"
    ).value =
        "";


    document.getElementById(
        "currentImageBox"
    ).style.display =
        "none";


    document.getElementById(
        "currentImage"
    ).src =
        "";


    document.getElementById(
        "submitButton"
    ).textContent =
        "Add Service";


    document
        .getElementById(
            "serviceModal"
        )
        .classList.add("show");

}


/* =========================
   OPEN EDIT
========================= */

function openEditModal(service) {

    document.getElementById(
        "modalTitle"
    ).textContent =
        "Edit Service";


    document.getElementById(
        "formAction"
    ).value =
        "edit";


    document.getElementById(
        "serviceId"
    ).value =
        service.id || "";


    document.getElementById(
        "category"
    ).value =
        service.category || "";


    document.getElementById(
        "serviceName"
    ).value =
        service.service_name || "";


    document.getElementById(
        "price"
    ).value =
        service.price || "";


    document.getElementById(
        "description"
    ).value =
        service.description || "";


    document.getElementById(
        "status"
    ).checked =
        Number(service.status) === 1;


    document.getElementById(
        "image"
    ).value =
        "";


    if (service.image) {

        document.getElementById(
            "currentImageBox"
        ).style.display =
            "block";


        document.getElementById(
            "currentImage"
        ).src =
            "../uploads/services/" +
            encodeURIComponent(
                service.image
            );

    } else {

        document.getElementById(
            "currentImageBox"
        ).style.display =
            "none";

        document.getElementById(
            "currentImage"
        ).src =
            "";
    }


    document.getElementById(
        "submitButton"
    ).textContent =
        "Update Service";


    document
        .getElementById(
            "serviceModal"
        )
        .classList.add("show");

}


/* =========================
   CLOSE MODAL
========================= */

function closeModal() {

    document
        .getElementById(
            "serviceModal"
        )
        .classList.remove("show");

}


/* =========================
   CLOSE OUTSIDE
========================= */

function closeOutside(event) {

    if (
        event.target ===
        document.getElementById(
            "serviceModal"
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
