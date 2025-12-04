<?php
require_once("config.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
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

// Destroy the session
session_destroy();

// Clear any output buffers if output buffering is active
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>

