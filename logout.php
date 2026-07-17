<?php
// Initialize session context
session_start();

// Unset all active user context variables
$_SESSION = array();

// If tracking session cookies, clear out the browser's token trace
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the actual server session cache
session_destroy();

// Redirect back cleanly to the login interface
header("Location: login.php");
exit;
?>