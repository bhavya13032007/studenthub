<?php
/**
 * Practical 10: Student Protected Dashboard
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/auth_check.php';

// Strict Role Guard: Only 'student' role allowed
require_role('student');

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, full_name, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Practical 10 (25DCE075)</title>
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 40px 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 720px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: 700; color: var(--primary); }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .badge { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .profile-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .profile-table th, .profile-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        .profile-table th { color: var(--muted); width: 35%; font-weight: 600; }
        .btn-logout { background: #ef4444; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .btn-logout:hover { background: #dc2626; }
        .admin-test-link { margin-top: 24px; padding: 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; font-size: 13px; color: #9f1239; }
        .admin-test-link a { color: #be123c; font-weight: 700; text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="navbar">
        <div class="brand">🎓 Student Hub Portal</div>
        <div>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2>Welcome, <?= htmlspecialchars($user_data['full_name']) ?>!</h2>
                <p style="color: var(--muted); font-size: 13px; margin-top: 4px;">Role: Student | Enrolled Session</p>
            </div>
            <span class="badge">Student Role Verified</span>
        </div>

        <table class="profile-table">
            <tr>
                <th>Username</th>
                <td><?= htmlspecialchars($user_data['username']) ?></td>
            </tr>
            <tr>
                <th>Official Email</th>
                <td><?= htmlspecialchars($user_data['email']) ?></td>
            </tr>
            <tr>
                <th>Account Created</th>
                <td><?= htmlspecialchars($user_data['created_at']) ?></td>
            </tr>
            <tr>
                <th>Last Login Timestamp</th>
                <td><?= htmlspecialchars($user_data['last_login'] ?? 'First login') ?></td>
            </tr>
            <tr>
                <th>Current Session Timeout</th>
                <td>15 Minutes of Inactivity</td>
            </tr>
        </table>

        <div class="admin-test-link">
            <strong>🔒 Security Test (Unauthorized Access Block):</strong><br>
            Try navigating to the Admin Dashboard as a student:
            <a href="admin_dashboard.php">Attempt to open Admin Dashboard</a>.
            The access control middleware will block you and redirect with a <em>forbidden</em> error!
        </div>
    </div>
</div>

</body>
</html>
