<?php
// Start session tracking if it hasn't been initialized yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session variables
$_SESSION = array();

// 2. Destory the session cookie on the user's browser if it exists
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

// 3. Destroy the actual session file data on the server
session_destroy();

// 4. Redirect the logged-out admin back to the login page
header("Location: admin_login.php");
exit;
?>