<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION["provider_id"]) || (int) $_SESSION["provider_id"] <= 0) {
    if (isset($_COOKIE["provider_logged_in"])) {
        setcookie("provider_logged_in", "", time() - 3600, "/");
    }
    header("Location: login.php?session_expired=1", true, 302);
    exit;
} else {
    if (empty($_COOKIE["provider_logged_in"])) {
        setcookie("provider_logged_in", "1", time() + 86400 * 30, "/");
    }
}
