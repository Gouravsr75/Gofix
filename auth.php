<?php
/*
|--------------------------------------------------------------------------
| main/auth.php
|--------------------------------------------------------------------------
| हर logged-in पेज (dashboard.php, more.php, ...) के सबसे ऊपर:
|     require_once __DIR__ . '/auth.php';
| इस फाइल के बाद कोई भी HTML आउटपुट नहीं होना चाहिए, वरना header() काम नहीं करेगा।
| ध्यान रहे: <?php से पहले कोई खाली लाइन या स्पेस न हो।
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* पेज कभी cache न हो, ताकि Back दबाने पर हर बार सर्वर से चेक हो */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* लॉगिन नहीं है तो login.php पर भेजो */
if (empty($_SESSION["user_id"])) {
    if (isset($_COOKIE["user_logged_in"])) {
        setcookie("user_logged_in", "", time() - 3600, "/");
    }
    header("Location: login.php?session_expired=1");
    exit;
} else {
    // Keep user_logged_in cookie active while authenticated
    if (empty($_COOKIE["user_logged_in"])) {
        setcookie("user_logged_in", "1", time() + 86400 * 30, "/");
    }
}
