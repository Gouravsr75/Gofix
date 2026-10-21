<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "fixlify_db"; // Replace with your actual database name

// Create connection and store it in $conn
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!function_exists('getServiceImageUrl')) {
    function getServiceImageUrl($image, $category = '') {
        $image = trim((string)$image);
        $baseDir = __DIR__;

        if ($image !== '') {
            // 1. uploads/services/
            if (file_exists($baseDir . '/uploads/services/' . $image)) {
                return 'uploads/services/' . rawurlencode($image);
            }
            // 2. image/
            if (file_exists($baseDir . '/image/' . $image)) {
                return 'image/' . rawurlencode($image);
            }
            // 3. image/Category/
            if ($category !== '' && file_exists($baseDir . '/image/' . $category . '/' . $image)) {
                return 'image/' . rawurlencode($category) . '/' . rawurlencode($image);
            }
        }

        // 4. Fallback based on category
        $catFallbacks = [
            'AC Repair'      => 'image/ac repair.png',
            'Appliance'      => 'image/appliance repair.png',
            'Carpenter'      => 'image/Carpenter.png',
            'Car Wash'       => 'image/carwash.png',
            'Electrician'    => 'image/elec.png',
            'Home Cleaning'  => 'image/home cleaning.png',
            'Laundry'        => 'image/Laundry.png',
            'Painter'        => 'image/painter.png',
            'Painting'       => 'image/painter.png',
            'Pest Control'   => 'image/Pest Control.png',
            'Plumber'        => 'image/plumber.png',
            'Salon at Home'  => 'image/Salon at Home.png',
            'Sofa Cleaning'  => 'image/Sofa Cleaning.png',
        ];

        if (!empty($category) && isset($catFallbacks[$category]) && file_exists($baseDir . '/' . $catFallbacks[$category])) {
            return str_replace('%2F', '/', rawurlencode($catFallbacks[$category]));
        }

        return '';
    }
}
?>
