
<?php

// Hinglish: Ye setup page database mein pehla admin account banata hai.
include "../db.php";

$admin_name = "Gourav Admin";
$email = "admin@gmail.com";
$password = "admin123";

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email already exists
$check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Admin with this email already exists.");
}

// Insert admin
$stmt = $conn->prepare("
    INSERT INTO admins
    (admin_name, email, password, status)
    VALUES (?, ?, ?, 'active')
");

$stmt->bind_param(
    "sss",
    $admin_name,
    $email,
    $hashed_password
);

if ($stmt->execute()) {
    echo "Admin registered successfully.<br><br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password: " . htmlspecialchars($password);
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>
