<?php
/**
 * Practical 14: Toggle User Status (Active / Inactive)
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/auth_check.php';

// Only admin can toggle status
require_role('admin');

$user_id = intval($_GET['id'] ?? 0);
if ($user_id > 0) {
    // Prevent admin from deactivating themselves
    if ($user_id === $_SESSION['user_id']) {
        header("Location: admin_dashboard.php?error=cannot_toggle_self");
        exit();
    }

    $stmt = $conn->prepare("SELECT username, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
        $upd = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->bind_param("si", $new_status, $user_id);
        $upd->execute();
        $upd->close();

        // Audit Log Entry
        log_audit_action($conn, $_SESSION['user_id'], 'USER_STATUS_TOGGLE', "Changed status of user {$user['username']} to {$new_status}");
    }
}

header("Location: admin_dashboard.php?msg=status_updated");
exit();
