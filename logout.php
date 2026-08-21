<?php
// logout.php - Centralized logout handler
require_once __DIR__ . '/config/config.php';

// Log logout activity if user was signed in
if (isset($_SESSION['username'])) {
    $conn = getDBConnection();
    $user = $_SESSION['username'];
    $actionText = "User logged out";
    $moduleText = "Authentication";
    $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, ?, NOW())");
    if ($logStmt) {
        $logStmt->bind_param("sss", $user, $actionText, $moduleText);
        @$logStmt->execute();
        @$logStmt->close();
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with friendly logout feedback
header("Location: login.php?logged_out=1");
exit();