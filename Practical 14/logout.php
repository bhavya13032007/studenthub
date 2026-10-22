<?php
/**
 * Practical 10: Secure Logout Script
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

// Log audit if user is logged in
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    log_audit_action($conn, $uid, 'LOGOUT', "User logged out securely.");

    // Delete remember-me token from DB if present
    if (isset($_COOKIE['remember_token'])) {
        $parts = explode(':', $_COOKIE['remember_token']);
        if (count($parts) === 2) {
            $token_hash = hash('sha256', $parts[1]);
            $del_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?");
            $del_stmt->bind_param("is", $uid, $token_hash);
            $del_stmt->execute();
            $del_stmt->close();
        }
    }
}

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy session cookie in browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Clear Remember-Me persistent cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// 4. Destroy the session storage on server
session_destroy();

// 5. Redirect with feedback
header("Location: login.php?msg=logged_out");
exit();
