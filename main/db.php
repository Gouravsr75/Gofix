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
?>
