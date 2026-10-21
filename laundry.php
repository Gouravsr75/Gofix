```php
<?php
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    include 'nav.php';
} else {
    include 'top.php';
}

include 'db.php';

$category = "Laundry";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laundry Services</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #222;
        }

        .page-header {
            text-align: center;
            padding: 30px 15px 20px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            color: #1264d6;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #666;
            font-size: 15px;
        }

        .services-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding-bottom: 40px;
        }

        .service-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: 0.2s;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .service-image {
            width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .no-image {
            width: 100%;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef3f9;
            color: #777;
        }

        .service-content {
            padding: 16px;
        }

        .service-content h3 {
            margin: 0 0 8px;
            font-size: 19px;
            color: #222;
        }

        .service-content p {
            margin: 0;
            min-height: 45px;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .service-bottom {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .service-price {
            color: #1264d6;
            font-size: 16px;
            font-weight: bold;
        }

        .book-btn {
            border: none;
            background: #1264d6;
            color: white;
            padding: 10px 15px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .book-btn:hover {
            background: #0d51b0;
        }

        .empty-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        @media (max-width: 900px) {
            .services-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .page-header {
                padding: 25px 12px 15px;
            }

            .page-header h1 {
                font-size: 25px;
            }

            .services-container {
                grid-template-columns: 1fr;
                padding: 12px;
                gap: 15px;
            }

            .service-content {
                padding: 14px;
            }

            .service-bottom {
                align-items: center;
            }
        }
    </style>
</head>

<body>

    <div class="page-header">
        <h1>Laundry Services</h1>
        <p>Professional laundry and clothes care services at your doorstep</p>
    </div>

    <div class="services-container">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($service = $result->fetch_assoc()): ?>

                <div class="service-card">

                    <?php
                    $imagePath = "uploads/services/" . $service['image'];

                    if (
                        !empty($service['image']) &&
                        file_exists(
                            __DIR__ . "/uploads/services/" . $service['image']
                        )
                    ):
                    ?>

                        <img
                            class="service-image"
                            src="<?php echo htmlspecialchars($imagePath); ?>"
                            alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                        >

                    <?php else: ?>

                        <div class="no-image">
                            No Image Available
                        </div>

                    <?php endif; ?>

                    <div class="service-content">

                        <h3>
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>

                        <div class="service-bottom">

                            <span class="service-price">
                                ₹<?php echo number_format((float)$service['price'], 0); ?> onwards
                            </span>

                            <button
                                type="button"
                                class="book-btn"
                                onclick="handleBooking(<?php echo (int)$service['id']; ?>)"
                            >
                                Book Now
                            </button>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-message">
                <h3>No Laundry Services Available</h3>
                <p>Please check again later.</p>
            </div>

        <?php endif; ?>

    </div>

    <script>
        function handleBooking(serviceId) {

            if (!serviceId || serviceId <= 0) {
                alert("Invalid service selected.");
                return;
            }

            const isLoggedIn = <?php
                echo (
                    isset($_SESSION['user_id']) &&
                    $_SESSION['user_id'] > 0
                ) ? 'true' : 'false';
            ?>;

            if (isLoggedIn) {

                // User already logged in
                window.location.href =
                    "booking.php?service_id=" +
                    encodeURIComponent(serviceId);

            } else {

                // User not logged in
                window.location.href =
                    "login.php?service_id=" +
                    encodeURIComponent(serviceId);
            }
        }
    </script>

</body>
</html>

<?php
$stmt->close();
include 'bottom.php';
?>
```