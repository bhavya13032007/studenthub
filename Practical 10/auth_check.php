<?php
/**
 * Authentication & Session Management Middleware
 * Practical 10: Secure Login/Logout & RBAC
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

// Session Timeout duration (e.g., 900 seconds = 15 minutes)
define('SESSION_TIMEOUT_DURATION', 900);

// Check Session Timeout if logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_DURATION)) {
        // Session expired due to inactivity
        $expired_user_id = $_SESSION['user_id'];
        log_audit_action($conn, $expired_user_id, 'SESSION_TIMEOUT', 'Session expired due to 15-minute inactivity.');

        // Clean session
        session_unset();
        session_destroy();

        // Redirect to login with message
        header("Location: login.php?msg=timeout");
        exit();
    }
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
} else {
    // Check for Remember-Me Cookie if session is not active
    if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
        $cookie_token = $_COOKIE['remember_token'];
        $token_parts = explode(':', $cookie_token);
        
        if (count($token_parts) === 2) {
            $user_id = intval($token_parts[0]);
            $raw_token = $token_parts[1];
            $token_hash = hash('sha256', $raw_token);

            $stmt = $conn->prepare("SELECT u.id, u.username, u.email, u.full_name, u.role, u.status FROM remember_tokens rt JOIN users u ON rt.user_id = u.id WHERE rt.user_id = ? AND rt.token_hash = ? AND rt.expires_at > NOW() AND u.status = 'active' LIMIT 1");
            $stmt->bind_param("is", $user_id, $token_hash);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($user = $res->fetch_assoc()) {
                // Auto-login successful
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                log_audit_action($conn, $user['id'], 'REMEMBER_ME_LOGIN', 'User logged in via persistent remember-me token.');
            }
            $stmt->close();
        }
    }
}

/**
 * Access Control Helper: Require Login
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=unauthorized");
        exit();
    }
}

/**
 * Access Control Helper: Require Role
 */
function require_role($required_role) {
    require_login();
    if ($_SESSION['role'] !== $required_role) {
        // Block unauthorized role
        header("Location: login.php?msg=forbidden");
        exit();
    }
}
?>
