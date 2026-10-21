<?php
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $mobile = trim($_POST["mobile"]);
    $password = $_POST["password"];

    if ($name == "" || $mobile == "" || $password == "") {
        $message = "All fields are required.";
    } else {

        // Check mobile already exists
        $check = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
        $check->bind_param("s", $mobile);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Mobile number already registered.";

        } else {

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, mobile, password) VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $name, $mobile, $hashedPassword);

            if ($stmt->execute()) {
                $message = "Registration successful. You can login now.";
            } else {
                $message = "Registration failed.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Create Account</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<form method="POST">

    <input type="text" name="name" placeholder="Full Name" required>
    <br><br>

    <input type="text" name="mobile" placeholder="Mobile Number" required>
    <br><br>

    <input type="password" name="password" placeholder="Password" required>
    <br><br>

    <button type="submit">Register</button>

</form>

<br>

<a href="login.php">Already have an account? Login</a>

</body>
</html>