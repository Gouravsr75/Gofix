<?php

session_start();

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    include 'nav.php';
} else {
    include 'top.php';
}

include 'db.php';

$category = "AC Repair";

/* =========================
   FETCH AC REPAIR SERVICES
========================= */

$sql = "SELECT * FROM services
        WHERE category = ?
        AND status = 1
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("s", $category);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AC Repair Services | Fixlify</title>

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

        /* =========================
           HERO SECTION
        ========================= */

        .hero {
            background: linear-gradient(
                135deg,
                #0077b6,
                #00b4d8
            );

            color: white;
            padding: 55px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 17px;
            max-width: 650px;
            margin: auto;
            line-height: 1.6;
        }

        /* =========================
           SERVICES SECTION
        ========================= */

        .services-section {
            max-width: 1200px;
            margin: auto;
            padding: 45px 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .section-title h2 {
            font-size: 30px;
            color: #111827;
            margin-bottom: 8px;
        }

        .section-title p {
            color: #666;
            font-size: 15px;
        }

        /* =========================
           SERVICE GRID
        ========================= */

        .service-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(280px, 1fr)
            );
            gap: 25px;
        }

        /* =========================
           SERVICE CARD
        ========================= */

        .service-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

            transition: 0.3s ease;

            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.12);
        }

        /* =========================
           IMAGE
        ========================= */

        .service-image {
            width:65%;
            object-fit: cover;
            background:white;
            margin-left:54px;
        }

        /* =========================
           CONTENT
        ========================= */

        .service-content {
            padding: 20px;

            display: flex;
            flex-direction: column;

            flex: 1;
        }

        .service-content h3 {
            font-size: 21px;
            color: #111827;
            margin-bottom: 10px;
        }

        .service-content p {
            color: #666;
            font-size: 14px;

            line-height: 1.6;

            min-height: 50px;

            margin-bottom: 18px;
        }

        /* =========================
           BOTTOM
        ========================= */

        .service-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: auto;
            gap: 10px;
        }

        .service-bottom strong {
            font-size: 17px;
            color: #1163D4;
            white-space: nowrap;
        }

        /* =========================
           BOOK BUTTON
        ========================= */

        .book-btn {
            border: none;

            background: #1163D4;
            color: white;

            padding: 11px 18px;

            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        .book-btn:hover {
            background: #005f8f;
        }

        /* =========================
           NO SERVICES
        ========================= */

        .no-services {
            text-align: center;

            background: white;

            padding: 40px 20px;

            border-radius: 12px;

            color: #666;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .no-services h3 {
            color: #333;
            margin-bottom: 8px;
        }

        /* =========================
           WHY CHOOSE US
        ========================= */

        .why-section {
            background: white;

            padding: 55px 20px;

            margin-top: 20px;
        }

        .why-container {
            max-width: 1100px;
            margin: auto;
        }

        .why-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .why-title h2 {
            font-size: 30px;
            margin-bottom: 10px;
            color: #111827;
        }

        .why-title p {
            color: #666;
        }

        .why-grid {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));

            gap: 25px;
        }

        .why-card {
            text-align: center;

            padding: 25px 18px;

            border: 1px solid #eee;

            border-radius: 12px;

            transition: 0.3s;
        }

        .why-card:hover {
            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.08);

            transform: translateY(-3px);
        }

        .why-icon {
            width: 55px;
            height: 55px;

            margin: 0 auto 15px;

            border-radius: 50%;

            background: #e0f7ff;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 26px;
        }

        .why-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .why-card p {
            font-size: 14px;
            line-height: 1.5;
            color: #666;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

            .hero {
                padding: 40px 15px;
            }

            .hero h1 {
                font-size: 29px;
            }

            .hero p {
                font-size: 15px;
            }

            .services-section {
                padding: 35px 15px;
            }

            .section-title h2 {
                font-size: 25px;
            }

            .service-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .service-image {
                height: 200px;
            }

            .service-content {
                padding: 17px;
            }

            .service-content h3 {
                font-size: 19px;
            }

            .service-bottom strong {
                font-size: 15px;
            }

            .book-btn {
                padding: 10px 15px;
            }

            .why-section {
                padding: 40px 15px;
            }

            .why-title h2 {
                font-size: 25px;
            }

            .why-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            .why-card {
                padding: 20px 12px;
            }

        }

        @media (max-width: 480px) {

            .hero h1 {
                font-size: 25px;
            }

            .hero p {
                font-size: 14px;
            }

            .service-image {
                height: auto;
            }

            .service-bottom {
                align-items: center;
            }

            .service-bottom strong {
                font-size: 14px;
            }

            .book-btn {
                font-size: 13px;
                padding: 9px 13px;
            }

            .why-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>



<!-- =========================
     SERVICES
========================= -->

<section class="services-section">

    <div class="section-title">

        <h2>AC Repair Services</h2>

        <p>
            Choose the AC service you need
        </p>

    </div>


    <?php if ($result->num_rows > 0) { ?>

        <div class="service-grid">

            <?php while ($service = $result->fetch_assoc()) { ?>

                <div class="service-card">


                    <!-- SERVICE IMAGE -->

                    <?php
                    $serviceImgUrl = getServiceImageUrl($service['image'] ?? '', $category);
                    if ($serviceImgUrl !== ''): ?>

                        <img
                            class="service-image"
                            src="<?= htmlspecialchars($serviceImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($service['service_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >

                    <?php else: ?>

                        <div
                            class="service-image"
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                color:#888;
                                font-size:15px;
                            "
                        >
                            No Image Available
                        </div>

                    <?php endif; ?>


                    <!-- SERVICE CONTENT -->

                    <div class="service-content">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $service['service_name']
                            );
                            ?>
                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $service['description']
                            );
                            ?>

                        </p>


                        <div class="service-bottom">

                            <strong>

                                ₹<?php
                                echo number_format(
                                    (float)$service['price'],
                                    0
                                );
                                ?>

                                onwards

                            </strong>


                            <button
                                type="button"
                                class="book-btn"
                                onclick="handleBooking(
                                    <?php echo (int)$service['id']; ?>
                                )"
                            >

                                Book Now

                            </button>

                        </div>

                    </div>

                </div>

            <?php } ?>

        </div>

    <?php } else { ?>


        <!-- NO SERVICE -->

        <div class="no-services">

            <h3>
                No AC Repair Services Available
            </h3>

            <p>
                AC repair services are currently unavailable.
                Please check again later.
            </p>

        </div>


    <?php } ?>

</section>


<!-- =========================
     WHY CHOOSE US
========================= -->

<section class="why-section">

    <div class="why-container">


        <div class="why-title">

            <h2>
                Why Choose Our AC Services?
            </h2>

            <p>
                Convenient and reliable AC services
                at your doorstep.
            </p>

        </div>


        <div class="why-grid">


            <!-- CARD 1 -->

            <div class="why-card">

                <div class="why-icon">
                    ❄️
                </div>

                <h3>
                    AC Experts
                </h3>

                <p>
                    Get help from experienced AC
                    service professionals.
                </p>

            </div>


            <!-- CARD 2 -->

            <div class="why-card">

                <div class="why-icon">
                    🔧
                </div>

                <h3>
                    Quality Service
                </h3>

                <p>
                    Professional repair and maintenance
                    for your AC.
                </p>

            </div>


            <!-- CARD 3 -->

            <div class="why-card">

                <div class="why-icon">
                    💰
                </div>

                <h3>
                    Affordable Price
                </h3>

                <p>
                    Choose useful AC services at
                    reasonable prices.
                </p>

            </div>


            <!-- CARD 4 -->

            <div class="why-card">

                <div class="why-icon">
                    🏠
                </div>

                <h3>
                    At Your Doorstep
                </h3>

                <p>
                    Get AC repair and maintenance
                    services conveniently at home.
                </p>

            </div>


        </div>

    </div>

</section>


<!-- =========================
     JAVASCRIPT
========================= -->

<script>

function goToLogin() {

    window.location.href = "login.php";

}

function handleBooking(serviceId) {

    /*
    |--------------------------------------------------------------------------
    | Check service ID
    |--------------------------------------------------------------------------
    */

    if (!serviceId || serviceId <= 0) {

        alert("Invalid service selected.");

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Check login status
    |--------------------------------------------------------------------------
    */

    const isLoggedIn = <?php

        echo (
            isset($_SESSION['user_id']) &&
            $_SESSION['user_id'] > 0
        ) ? 'true' : 'false';

    ?>;


    /*
    |--------------------------------------------------------------------------
    | USER LOGGED IN
    |--------------------------------------------------------------------------
    */

    if (isLoggedIn) {

        window.location.href =
            "booking.php?service_id=" +
            encodeURIComponent(serviceId);

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | USER NOT LOGGED IN
    |--------------------------------------------------------------------------
    | Send service_id to login.php.
    | After login, login.php will send user
    | to booking.php with the same service ID.
    |--------------------------------------------------------------------------
    */

    window.location.href =
        "login.php?service_id=" +
        encodeURIComponent(serviceId);

}
</script>


</body>

</html>


<?php

$stmt->close();
include'bottom.php';
?>