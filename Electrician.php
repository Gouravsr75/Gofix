<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
$isLoggedIn =
    isset($_SESSION['user_id']) ||
    isset($_SESSION['user']) ||
    isset($_SESSION['email']);

// Navbar
if ($isLoggedIn) {
    include 'nav.php';
} else {
    include 'top.php';
}

include 'db.php';


// Category
$category = "Electrician";


// Get active electrician services
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Electrician Services</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            background: #f5f8fc;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .services-section {
            width: 100%;
            padding: clamp(42px, 6vw, 76px) clamp(16px, 4vw, 40px) 72px;
            background:
                radial-gradient(circle at 12% 8%, rgba(37, 99, 235, .09), transparent 27rem),
                linear-gradient(180deg, #f8fbff 0%, #f2f6fc 100%);
        }

        .section-heading {
            max-width: 720px;
            margin: 0 auto 38px;
            text-align: center;
        }

        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 8px 12px;
            border: 1px solid #d8e6ff;
            border-radius: 999px;
            background: #fff;
            color: #155eef;
            font-size: 12px;
            font-weight: 750;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .section-heading h2 {
            margin: 0 0 10px;
            color: #14213d;
            font-size: clamp(30px, 4vw, 42px);
            font-weight: 800;
            letter-spacing: -.045em;
            line-height: 1.1;
        }

        .section-heading p {
            margin: 0;
            color: #667085;
            font-size: 16px;
            line-height: 1.65;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: stretch;
            gap: 24px;
            width: min(100%, 1220px);
            margin: 0 auto;
        }

        .service-card {
            display: flex;
            min-width: 0;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e3eaf4;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 8px 26px rgba(24, 45, 82, .06);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .service-card:hover,
        .service-card:focus-within {
            transform: translateY(-5px);
            border-color: #c2d6ff;
            box-shadow: 0 18px 38px rgba(24, 45, 82, .12);
        }

        .service-media {
            display: grid;
            place-items: center;
            overflow: hidden;
            aspect-ratio: 16 / 10;
            padding: 12px;
            background: linear-gradient(145deg, #f0f5ff 0%, #f8fbff 58%, #eaf2ff 100%);
        }

        .service-image {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 14px;
            object-fit: contain;
            transition: transform .25s ease;
        }

        .service-card:hover .service-image {
            transform: scale(1.035);
        }

        .no-image {
            display: flex;
            width: 100%;
            height: 100%;
            min-height: 150px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px dashed #c7d6eb;
            border-radius: 14px;
            color: #71809a;
            font-size: 13px;
            font-weight: 600;
        }

        .no-image i {
            color: #4d7ed8;
            font-size: 28px;
        }

        .service-content {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 21px;
        }

        .service-content h3 {
            display: -webkit-box;
            overflow: hidden;
            min-height: 48px;
            margin: 0 0 9px;
            color: #18243b;
            font-size: 18px;
            font-weight: 750;
            line-height: 1.35;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .service-content p {
            display: -webkit-box;
            overflow: hidden;
            min-height: 63px;
            margin: 0 0 20px;
            color: #68758a;
            font-size: 14px;
            line-height: 1.55;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .service-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: auto;
            padding-top: 17px;
            border-top: 1px solid #edf1f7;
        }

        .service-price {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 3px;
        }

        .service-price span {
            color: #8490a3;
            font-size: 11px;
            font-weight: 650;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .service-price strong {
            color: #155eef;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
        }

        .service-price small {
            color: #667085;
            font-size: 12px;
            font-weight: 500;
        }

        .service-bottom button {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 15px;
            border: 0;
            border-radius: 12px;
            background: #155eef;
            color: #fff;
            box-shadow: 0 7px 15px rgba(21, 94, 239, .2);
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 750;
            transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .service-bottom button:hover {
            transform: translateY(-1px);
            background: #104bc4;
            box-shadow: 0 10px 18px rgba(21, 94, 239, .28);
        }

        .service-bottom button:focus-visible {
            outline: 3px solid rgba(21, 94, 239, .25);
            outline-offset: 3px;
        }

        .why-section {
            padding: 66px clamp(16px, 4vw, 40px) 74px;
            border-top: 1px solid #e2eaf5;
            background: linear-gradient(180deg, #edf4ff 0%, #f7faff 100%);
            text-align: center;
        }

        .why-section > h2 {
            margin: 0 0 30px;
            color: #14213d;
            font-size: clamp(25px, 3vw, 32px);
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .why-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            width: min(100%, 1080px);
            margin: 0 auto;
        }

        .why-card {
            padding: 28px 22px;
            border: 1px solid #e2eaf5;
            border-radius: 20px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 8px 24px rgba(24, 45, 82, .05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .why-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(24, 45, 82, .1);
        }

        .why-icon {
            display: grid;
            width: 58px;
            height: 58px;
            place-items: center;
            margin: 0 auto 16px;
            border: 1px solid #d8e6ff;
            border-radius: 18px;
            background: #eef4ff;
            color: #155eef;
            font-size: 22px;
        }

        .why-card h3 {
            margin: 0 0 8px;
            color: #18243b;
            font-size: 17px;
            font-weight: 750;
        }

        .why-card p {
            margin: 0;
            color: #68758a;
            font-size: 14px;
            line-height: 1.55;
        }

        .empty-message {
            grid-column: 1 / -1;
            padding: 52px 24px;
            border: 1px dashed #c7d6eb;
            border-radius: 22px;
            background: rgba(255, 255, 255, .8);
            color: #68758a;
            text-align: center;
        }

        .empty-message h3 {
            margin: 0 0 8px;
            color: #18243b;
            font-size: 20px;
        }

        .empty-message p {
            margin: 0;
            font-size: 14px;
        }

        @media (max-width: 960px) {
            .services-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .why-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .services-section {
                padding: 38px 16px 52px;
            }

            .section-heading {
                margin-bottom: 26px;
            }

            .section-heading p {
                font-size: 14px;
            }

            .services-grid,
            .why-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .service-media {
                aspect-ratio: 16 / 9;
                padding: 10px;
            }

            .service-content {
                padding: 18px;
            }

            .why-section {
                padding: 48px 16px 54px;
            }
        }

    </style>

</head>


<body>
<!-- =========================
     SERVICES
========================= -->

<section class="services-section">

    <div class="section-heading">

        <span class="section-eyebrow">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
            Trusted electrical help
        </span>

        <h2>
            Electrician Services
        </h2>

        <p>
            Select a service and book an electrician
        </p>

    </div>


    <div class="services-grid">


        <?php if ($result->num_rows > 0): ?>


            <?php while ($service = $result->fetch_assoc()): ?>


                <div class="service-card">


                    <?php

                    $imagePath =
                        "uploads/services/" .
                        $service['image'];


                    $imageFile =
                        __DIR__ .
                        "/uploads/services/" .
                        $service['image'];


                    ?>

                    <div class="service-media">

                    <?php if (
                        !empty($service['image']) &&
                        file_exists($imageFile)
                    ):
                    ?>

                        <img
                            class="service-image"
                            src="<?php echo htmlspecialchars(
                                $imagePath
                            ); ?>"
                            alt="<?php echo htmlspecialchars(
                                $service['service_name']
                            ); ?>"
                            loading="lazy"
                            decoding="async"
                        >

                    <?php else: ?>

                        <div class="no-image">

                            <i class="fa-regular fa-image" aria-hidden="true"></i>
                            No Image Available

                        </div>

                    <?php endif; ?>

                    </div>


                    <div class="service-content">


                        <h3>

                            <?php echo htmlspecialchars(
                                $service['service_name']
                            ); ?>

                        </h3>


                        <p>

                            <?php echo htmlspecialchars(
                                $service['description']
                            ); ?>

                        </p>


                        <div class="service-bottom">


                            <div class="service-price">
                                <span>Starting from</span>
                                <div>
                                    <strong>₹<?php echo number_format(
                                        (float)$service['price'],
                                        0
                                    ); ?></strong>
                                    <small>onwards</small>
                                </div>
                            </div>


                            <!--
                                IMPORTANT:
                                Send SERVICE ID
                                to booking system
                            -->

                            <button
                                type="button"
                                onclick="handleBooking(
                                    <?php echo (int)$service['id']; ?>
                                )"
                            >

                                <span>Book Now</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>

                            </button>


                        </div>


                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty-message">

                <h3>
                    No Electrician Services Available
                </h3>

                <p>
                    Please check again later.
                </p>

            </div>


        <?php endif; ?>


    </div>

</section>


<!-- =========================
     WHY CHOOSE US
========================= -->

<section class="why-section">


    <h2>
        Why Choose Our Electrician?
    </h2>


    <div class="why-grid">


        <div class="why-card">

            <div class="why-icon">

                <i class="fa-solid fa-user-shield"></i>

            </div>

            <h3>
                Verified Electricians
            </h3>

            <p>
                Experienced and trusted professionals.
            </p>

        </div>


        <div class="why-card">

            <div class="why-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

            <h3>
                Quick Service
            </h3>

            <p>
                Get service at your preferred time.
            </p>

        </div>


        <div class="why-card">

            <div class="why-icon">

                <i class="fa-solid fa-indian-rupee-sign"></i>

            </div>

            <h3>
                Affordable Price
            </h3>

            <p>
                Simple and transparent pricing.
            </p>

        </div>


    </div>

</section>


<!-- =========================
     JAVASCRIPT
========================= -->

<script>

function handleBooking(serviceId) {

    /*
     * Make sure service ID exists
     */

    if (!serviceId || serviceId <= 0) {

        alert("Invalid service selected.");

        return;

    }


    /*
     * Send service ID to booking page.
     *
     * booking.php itself checks whether
     * the customer is logged in.
     */

    window.location.href =
        "booking.php?service_id=" +
        encodeURIComponent(serviceId);

}

</script>


</body>

</html>


<?php

$stmt->close();

include 'bottom.php';

?>
