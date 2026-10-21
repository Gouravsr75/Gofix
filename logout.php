<?php

/* =========================================
   GOFIX CUSTOMER LOGOUT
========================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   CLEAR ALL SESSION DATA
========================================= */

$_SESSION = array();

/* =========================================
   DELETE SESSION COOKIE
========================================= */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* =========================================
   DESTROY SESSION
========================================= */

session_destroy();

/* =========================================
   CLEAR AUTH COOKIE
========================================= */
setcookie("user_logged_in", "", time() - 3600, "/");

/* =========================================
   PREVENT BROWSER CACHE
========================================= */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <script>
        try { sessionStorage.removeItem("user_logged_in"); } catch(e){}
        try { localStorage.removeItem("user_logged_in"); } catch(e){}
        document.cookie = "user_logged_in=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        window.location.replace("login.php?logged_out=1");
    </script>
</head>
<body>
    <noscript><meta http-equiv="refresh" content="0;url=login.php?logged_out=1"></noscript>
</body>
</html>
<?php
exit;

?>