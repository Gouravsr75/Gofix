
<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET["session_check"]) && $_GET["session_check"] === "1") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["authenticated" => !empty($_SESSION["provider_id"])]);
    exit;
}

include "../db.php";

$error = "";
$success = "";
$documentError = "";
$documentExtension = "";
$documentTempPath = "";
$photoError = "";
$photoExtension = "";
$photoTempPath = "";

function validateProviderGovernmentDocument($upload, &$message, &$extension, &$tempPath)
{
    $message = "";
    $extension = "";
    $tempPath = "";

    if (!is_array($upload) || !isset($upload['error'])) {
        $message = "Please upload a valid government-issued document.";
        return false;
    }

    if ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Please upload a valid government-issued document.";
        return false;
    }

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $message = in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? "The document is too large. Please choose a file up to 5 MB."
            : "The document could not be uploaded. Please try again.";
        return false;
    }

    if (empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
        $message = "The uploaded document could not be verified. Please try again.";
        return false;
    }

    if ((int)($upload['size'] ?? 0) <= 0 || (int)$upload['size'] > 5 * 1024 * 1024) {
        $message = "The document must be 5 MB or smaller.";
        return false;
    }

    if (!function_exists('finfo_open')) {
        $message = "Document verification is unavailable right now. Please contact support.";
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $upload['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedTypes = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp']
    ];

    $originalExtension = strtolower(pathinfo($upload['name'] ?? '', PATHINFO_EXTENSION));
    if (
        !isset($allowedTypes[$mimeType]) ||
        !in_array($originalExtension, $allowedTypes[$mimeType], true)
    ) {
        $message = "Upload a PDF, JPG, PNG, or WebP image of your government-issued document.";
        return false;
    }

    $extension = $mimeType === 'image/jpeg' ? 'jpg' : $originalExtension;
    $tempPath = $upload['tmp_name'];
    return true;
}

function validateProviderPhoto($upload, &$message, &$extension, &$tempPath)
{
    $message = "";
    $extension = "";
    $tempPath = "";

    if (!is_array($upload) || !isset($upload['error']) || $upload['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Please upload your profile photo.";
        return false;
    }

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $message = in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? "Your profile photo must be 2 MB or smaller."
            : "Your profile photo could not be uploaded. Please try again.";
        return false;
    }

    if (empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
        $message = "Your profile photo could not be verified. Please try again.";
        return false;
    }

    if ((int)($upload['size'] ?? 0) <= 0 || (int)$upload['size'] > 2 * 1024 * 1024) {
        $message = "Your profile photo must be 2 MB or smaller.";
        return false;
    }

    if (!function_exists('finfo_open')) {
        $message = "Photo verification is unavailable right now. Please contact support.";
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $upload['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp']
    ];
    $originalExtension = strtolower(pathinfo($upload['name'] ?? '', PATHINFO_EXTENSION));

    if (
        !isset($allowedTypes[$mimeType]) ||
        !in_array($originalExtension, $allowedTypes[$mimeType], true)
    ) {
        $message = "Upload your photo as a JPG, PNG, or WebP image.";
        return false;
    }

    $extension = $mimeType === 'image/jpeg' ? 'jpg' : $originalExtension;
    $tempPath = $upload['tmp_name'];
    return true;
}

/* If already logged in */
if (isset($_SESSION['provider_id']) && $_SESSION['provider_id'] > 0) {
    header("Location: dashboard.php");
    exit;
}


/* -----------------------------------------
   Registration
----------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $provider_name   = trim($_POST['provider_name'] ?? "");
    $mobile          = trim($_POST['mobile'] ?? "");
    $email           = trim($_POST['email'] ?? "");
    $password        = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    $address         = trim($_POST['address'] ?? "");
    $village         = trim($_POST['village'] ?? "");
    $area            = trim($_POST['area'] ?? "");
    $city            = trim($_POST['city'] ?? "");
    $district        = trim($_POST['district'] ?? "");
    $pincode         = trim($_POST['pincode'] ?? "");

    $experience      = (int)($_POST['experience'] ?? 0);

    /* Services */
    $services = $_POST['services'] ?? [];

    /* -----------------------------------------
       Validation
    ----------------------------------------- */

    if (
        $provider_name === "" ||
        $mobile === "" ||
        $password === "" ||
        $confirm_password === "" ||
        $address === "" ||
        $village === "" ||
        $area === "" ||
        $city === "" ||
        $district === "" ||
        $pincode === ""
    ) {

        $error = "Please fill all required fields.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $error = "Please enter a valid 10 digit mobile number.";

    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Password and confirm password do not match.";

    } elseif (count($services) === 0) {

        $error = "Please select at least one service.";

    } elseif ($experience < 0 || $experience > 50) {

        $error = "Please enter a valid experience.";

    } elseif (!validateProviderGovernmentDocument(
        $_FILES['government_document'] ?? null,
        $documentError,
        $documentExtension,
        $documentTempPath
    )) {

        $error = $documentError;

    } elseif (!validateProviderPhoto(
        $_FILES['provider_photo'] ?? null,
        $photoError,
        $photoExtension,
        $photoTempPath
    )) {

        $error = $photoError;

    } else {

        /* -----------------------------------------
           Convert selected services to text
        ----------------------------------------- */

        $service_categories = implode(", ", $services);


        /* -----------------------------------------
           Check Mobile
        ----------------------------------------- */

        $sql = "SELECT id
                FROM service_providers
                WHERE mobile = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $mobile);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "This mobile number is already registered.";

            }

            $stmt->close();
        }


        /* -----------------------------------------
           Check Email
        ----------------------------------------- */

        if ($error === "" && $email !== "") {

            $sql = "SELECT id
                    FROM service_providers
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param("s", $email);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    $error = "This email is already registered.";

                }

                $stmt->close();
            }
        }


        /* -----------------------------------------
           Create Account
        ----------------------------------------- */

        if ($error === "") {

            $documentStorageDirectory =
                dirname(__DIR__, 4) .
                DIRECTORY_SEPARATOR . 'fixlify_private' .
                DIRECTORY_SEPARATOR . 'provider_documents';
            $photoStorageDirectory =
                dirname(__DIR__, 4) .
                DIRECTORY_SEPARATOR . 'fixlify_private' .
                DIRECTORY_SEPARATOR . 'provider_photos';

            $storedDocumentName = "";
            $storedDocumentPath = "";
            $storedPhotoName = "";
            $storedPhotoPath = "";

            if (
                !is_dir($documentStorageDirectory) &&
                !@mkdir($documentStorageDirectory, 0700, true) &&
                !is_dir($documentStorageDirectory)
            ) {
                $error = "Secure document storage is unavailable. Please try again later.";
            }

            if (
                $error === "" &&
                !is_dir($photoStorageDirectory) &&
                !@mkdir($photoStorageDirectory, 0700, true) &&
                !is_dir($photoStorageDirectory)
            ) {
                $error = "Secure photo storage is unavailable. Please try again later.";
            }

            if ($error === "") {
                try {
                    $storedDocumentName =
                        bin2hex(random_bytes(24)) . '.' . $documentExtension;
                } catch (Throwable $exception) {
                    $error = "A secure document name could not be created. Please try again.";
                }
            }

            if ($error === "") {
                $storedDocumentPath =
                    $documentStorageDirectory . DIRECTORY_SEPARATOR . $storedDocumentName;

                if (!move_uploaded_file($documentTempPath, $storedDocumentPath)) {
                    $error = "The document could not be saved securely. Please try again.";
                    $storedDocumentPath = "";
                }
            }

            if ($error === "") {
                try {
                    $storedPhotoName = bin2hex(random_bytes(24)) . '.' . $photoExtension;
                } catch (Throwable $exception) {
                    $error = "A secure photo name could not be created. Please try again.";
                }
            }

            if ($error === "") {
                $storedPhotoPath = $photoStorageDirectory . DIRECTORY_SEPARATOR . $storedPhotoName;

                if (!move_uploaded_file($photoTempPath, $storedPhotoPath)) {
                    $error = "Your photo could not be saved securely. Please try again.";
                    $storedPhotoPath = "";
                }
            }

            if ($error === "") {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $status = "pending";

                $sql = "INSERT INTO service_providers
                        (
                            provider_name,
                            mobile,
                            email,
                            password,
                            address,
                            village,
                            area,
                            city,
                            district,
                            pincode,
                            service_categories,
                            experience,
                            government_document_file,
                            provider_photo_file,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = null;

                try {
                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        $error = "Registration could not be completed. Please try again.";
                    } else {
                        $stmt->bind_param(
                            "sssssssssssisss",
                            $provider_name,
                            $mobile,
                            $email,
                            $hashed_password,
                            $address,
                            $village,
                            $area,
                            $city,
                            $district,
                            $pincode,
                            $service_categories,
                            $experience,
                            $storedDocumentName,
                            $storedPhotoName,
                            $status
                        );

                        if ($stmt->execute()) {
                            $success = "Registration successful! Your account is waiting for admin approval.";
                            $_POST = [];
                        } else {
                            $error = "Registration failed. Please try again.";
                        }

                        $stmt->close();
                    }
                } catch (Throwable $exception) {
                    if ($stmt instanceof mysqli_stmt) {
                        $stmt->close();
                    }

                    $error = "Registration could not be completed. Please try again.";
                }
            }

            if (
                $error !== "" &&
                $storedDocumentPath !== ""
            ) {
                if (is_file($storedDocumentPath)) {
                    @unlink($storedDocumentPath);
                }
            }

            if ($error !== "" && $storedPhotoPath !== "" && is_file($storedPhotoPath)) {
                @unlink($storedPhotoPath);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Join Gofix as a Service Provider</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: Arial, sans-serif;

    background: #f4f7fb;

    color: #222;

    padding: 25px 15px;
}


/* -----------------------------------------
   Container
----------------------------------------- */

.container {

    width: 100%;

    max-width: 850px;

    margin: auto;
}


/* -----------------------------------------
   Header
----------------------------------------- */

.page-header {

    text-align: center;

    margin-bottom: 25px;
}

.logo {

    width: 65px;

    height: 65px;

    background: #1264d6;

    color: white;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    font-size: 27px;

    font-weight: bold;
}

.page-header h1 {

    margin-top: 15px;

    font-size: 27px;
}

.page-header p {

    color: #777;

    margin-top: 7px;

    font-size: 14px;
}


/* -----------------------------------------
   Form Box
----------------------------------------- */

.form-box {

    background: white;

    padding: 30px;

    border-radius: 15px;

    box-shadow:
        0 8px 30px rgba(0,0,0,0.08);
}


/* -----------------------------------------
   Messages
----------------------------------------- */

.error {

    background: #ffe8e8;

    color: #c00000;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
}

.success {

    background: #e7f8ed;

    color: #15803d;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
}


/* -----------------------------------------
   Section
----------------------------------------- */

.section {

    margin-bottom: 28px;
}

.section-title {

    font-size: 18px;

    color: #1264d6;

    margin-bottom: 17px;

    padding-bottom: 8px;

    border-bottom: 1px solid #e5e7eb;
}


/* -----------------------------------------
   Grid
----------------------------------------- */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 17px;
}

.form-group {

    display: flex;

    flex-direction: column;
}

.full {

    grid-column: 1 / -1;
}


/* -----------------------------------------
   Labels
----------------------------------------- */

label {

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 7px;

    color: #333;
}

.required {

    color: red;
}


/* -----------------------------------------
   Inputs
----------------------------------------- */

input,
textarea,
select {

    width: 100%;

    padding: 12px 13px;

    border: 1px solid #d7dce3;

    border-radius: 8px;

    font-size: 14px;

    outline: none;

    background: white;
}

input:focus,
textarea:focus,
select:focus {

    border-color: #1264d6;

    box-shadow:
        0 0 0 3px rgba(18,100,214,0.10);
}

textarea {

    min-height: 90px;

    resize: vertical;
}


/* -----------------------------------------
   Services
----------------------------------------- */

.services-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 10px;
}

.service-option {

    border: 1px solid #dfe4eb;

    border-radius: 8px;

    padding: 11px;

    cursor: pointer;

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 14px;
}

.service-option:hover {

    border-color: #1264d6;

    background: #f5f9ff;
}

.service-option input {

    width: auto;
}


/* -----------------------------------------
   Password
----------------------------------------- */

.password-note {

    font-size: 12px;

    color: #777;

    margin-top: 5px;
}


/* -----------------------------------------
   Button
----------------------------------------- */

.register-btn {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 8px;

    background: #1264d6;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.register-btn:hover {

    background: #0d55b8;
}


/* -----------------------------------------
   Bottom
----------------------------------------- */

.bottom-links {

    text-align: center;

    margin-top: 20px;

    font-size: 14px;

    color: #777;
}

.bottom-links a {

    color: #1264d6;

    text-decoration: none;

    font-weight: bold;
}


/* -----------------------------------------
   Mobile
----------------------------------------- */

@media (max-width: 650px) {

    body {
        padding: 15px 10px;
    }

    .form-box {
        padding: 20px 15px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .services-grid {
        grid-template-columns: 1fr 1fr;
    }

    .page-header h1 {
        font-size: 23px;
    }
}

@media (max-width: 400px) {

    .services-grid {
        grid-template-columns: 1fr;
    }
}

</style>
<link rel="stylesheet" href="provider-theme.css?v=<?= filemtime(__DIR__ . '/provider-theme.css') ?>">
<link rel="stylesheet" href="register.css?v=<?= filemtime(__DIR__ . '/register.css') ?>">
</head>
<body class="provider-auth-page provider-register-page">


<div class="container">


    <header class="register-topbar">
        <a class="gofix-brand" href="../index.php" aria-label="Gofix home">
            <span class="gofix-brand-mark">G</span>
            <span class="gofix-brand-name">Gofix <small>PROVIDER NETWORK</small></span>
        </a>
        <p>Already have an account? <a href="login.php">Provider login</a></p>
    </header>

    <section class="register-intro" aria-labelledby="register-title">
        <p class="register-eyebrow">JOIN THE GOFIX NETWORK</p>
        <h1 id="register-title">Build your service business with Gofix</h1>
        <p class="register-intro-copy">Create your provider profile, choose the services you offer, and tell us where you work. Our team will review your application.</p>
        <div class="register-highlights" aria-label="Registration overview">
            <span><b>01</b> Your details</span>
            <span><b>02</b> Work area</span>
            <span><b>03</b> Identity check</span>
        </div>
    </section>


    <div class="form-box">

        <div class="form-heading">
            <div>
                <h2>Provider application</h2>
                <p>Complete each section to submit your profile.</p>
            </div>
            <span class="required-note"><span class="required">*</span> Required</span>
        </div>


        <?php if ($error !== ""): ?>

            <div class="error">
                <?php
                echo htmlspecialchars($error);
                ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="success">

                <?php
                echo htmlspecialchars($success);
                ?>

                <br><br>

                <a href="login.php"
                   style="color:#1264d6;font-weight:bold;text-decoration:none;">

                    Go to Provider Login →

                </a>

            </div>

        <?php endif; ?>


        <form method="POST" enctype="multipart/form-data">


            <!-- BASIC INFORMATION -->

            <div class="section">

                <div class="section-title">Basic Information</div>
                <p class="section-help">Share the contact details customers and the Gofix team can use.</p>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Provider Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="provider_name"
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($_POST['provider_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Mobile Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="tel"
                            name="mobile"
                            maxlength="10"
                            placeholder="10 digit mobile number"
                            value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Enter email address"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Experience
                        </label>

                        <input
                            type="number"
                            name="experience"
                            min="0"
                            max="50"
                            placeholder="Experience in years"
                            value="<?php echo htmlspecialchars($_POST['experience'] ?? '0'); ?>"
                        >

                    </div>

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="section">

                <div class="section-title">
                    Account Security
                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Password
                            <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="password"
                            placeholder="Create password"
                            required
                        >

                        <span class="password-note">
                            Minimum 6 characters
                        </span>

                    </div>


                    <div class="form-group">

                        <label>
                            Confirm Password
                            <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Confirm password"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- SERVICES -->

            <div class="section">

                <div class="section-title">Services You Provide</div>
                <p class="section-help">Select every service you are qualified to provide.</p>


                <div class="services-grid">


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Electrician"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Electrician",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Electrician

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Plumber"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Plumber",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Plumber

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="AC Repair"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "AC Repair",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        AC Repair

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Home Cleaning"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Home Cleaning",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Home Cleaning

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Salon at Home"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Salon at Home",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Salon at Home

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Carpenter"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Carpenter",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Carpenter

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Painter"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Painter",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Painter

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Pest Control"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Pest Control",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Pest Control

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Car Wash"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Car Wash",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Car Wash

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Sofa Cleaning"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Sofa Cleaning",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Sofa Cleaning

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Laundry"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Laundry",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Laundry

                    </label>


                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Appliance Repair"
                            <?php
                            if (
                                isset($_POST['services']) &&
                                in_array(
                                    "Appliance Repair",
                                    $_POST['services']
                                )
                            ) echo "checked";
                            ?>
                        >

                        Appliance Repair

                    </label>

                </div>

            </div>


            <!-- GOVERNMENT DOCUMENT -->

            <div class="section">

                <div class="section-title">Identity Verification</div>
                <p class="section-help">A government-issued document helps us verify your provider account.</p>

                <div class="form-grid">

                    <div class="form-group full">

                        <label for="government_document">
                            Valid Government-Issued ID
                            <span class="required">*</span>
                        </label>

                        <input
                            type="file"
                            id="government_document"
                            name="government_document"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                            aria-describedby="government-document-help"
                            required
                        >

                        <span class="password-note" id="government-document-help">
                            Upload an Aadhaar card, PAN card, Voter ID, or Driving Licence.
                            PDF, JPG, PNG, or WebP; maximum 5 MB. Only authorized administrators can access it.
                        </span>

                    </div>

                </div>

            </div>


            <!-- PROVIDER PROFILE PHOTO -->

            <div class="section">

                <div class="section-title">Your Profile Photo</div>
                <p class="section-help">Use a clear, recent photo so customers can recognize you.</p>

                <div class="form-grid">

                    <div class="form-group full">

                        <label for="provider_photo">
                            Upload Your Photo
                            <span class="required">*</span>
                        </label>

                        <input
                            type="file"
                            id="provider_photo"
                            name="provider_photo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            aria-describedby="provider-photo-help"
                            required
                        >

                        <span class="password-note" id="provider-photo-help">
                            Choose a clear photo of yourself. JPG, PNG, or WebP; maximum 2 MB.
                            This photo will appear on your provider dashboard and can be reviewed by administrators.
                        </span>

                    </div>

                </div>

            </div>


            <!-- ADDRESS -->

            <div class="section">

                <div class="section-title">Service Location</div>
                <p class="section-help">Requests are matched with providers who work in the customer's area.</p>


                <div class="form-grid">


                    <div class="form-group full">

                        <label>
                            Complete Address
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="address"
                            placeholder="House number, street, etc."
                            required
                        ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            Village / Locality
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="village"
                            placeholder="Village / locality"
                            value="<?php echo htmlspecialchars($_POST['village'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Area
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="area"
                            placeholder="Area"
                            value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            City
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="city"
                            placeholder="City"
                            value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            District
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="district"
                            placeholder="District"
                            value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            PIN Code
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="pincode"
                            maxlength="6"
                            placeholder="6 digit PIN code"
                            value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="register-btn"
            >
                Create provider account
            </button>


        </form>


        <div class="bottom-links">
            <a href="../index.php">
                ← Back to Gofix home
            </a>

        </div>

    </div>

</div>

<script>
window.addEventListener("pageshow", function () {
    fetch("login.php?session_check=1&_=" + Date.now(), {
        cache: "no-store",
        credentials: "same-origin"
    })
        .then(function (response) { return response.json(); })
        .then(function (session) {
            if (session.authenticated) {
                window.location.replace("dashboard.php");
            }
        })
        .catch(function () {});
});
</script>

</body>

</html>
