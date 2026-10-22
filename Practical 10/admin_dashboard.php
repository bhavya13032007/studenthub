<?php
/**
 * Practical 10: Admin Protected Dashboard
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/auth_check.php';

// Strict Role Guard: Only 'admin' role allowed
require_role('admin');

// Fetch basic metrics
$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$student_count = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status='active'")->fetch_assoc()['total'];
$event_count = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Practical 10 (25DCE075)</title>
    <style>
        :root {
            --primary: #4338ca;
            --primary-hover: #3730a3;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1e1b4b; color: white; padding: 24px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .brand { font-size: 18px; font-weight: 700; color: #e0e7ff; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-links a { display: block; color: #cbd5e1; text-decoration: none; padding: 10px 14px; border-radius: 6px; margin-bottom: 6px; font-size: 14px; transition: background 0.2s; }
        .nav-links a:hover, .nav-links a.active { background: #312e81; color: white; }
        .user-tag { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 8px; font-size: 13px; }
        .main { flex: 1; padding: 32px 40px; overflow-y: auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .badge { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card); padding: 20px; border-radius: 10px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { font-size: 28px; font-weight: 700; color: var(--text); margin-top: 8px; }
        .btn-logout { background: #ef4444; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block; }
        .btn-logout:hover { background: #dc2626; }
        .info-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 24px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="brand">🎓 CHARUSAT Portal</div>
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="active">📊 Overview</a>
            <a href="../practical_11_student_crud/students.php">👨‍🎓 Student CRUD</a>
            <a href="../practical_12_event_management/events.php">📅 Event Management</a>
            <a href="../practical_13_json_ajax_crud/index.html">⚡ Live AJAX CRUD</a>
            <a href="../practical_14_admin_dashboard/admin_dashboard.php">🛡️ Advanced Analytics</a>
        </nav>
    </div>
    <div class="user-tag">
        <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong><br>
        <span style="color: #94a3b8; font-size: 12px;">Role: <?= htmlspecialchars($_SESSION['role']) ?></span><br>
        <span style="color: #a5b4fc; font-size: 11px;">ID: 25DCE075</span>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h1>Admin Control Panel</h1>
            <p style="color: var(--muted); font-size: 14px;">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
        </div>
        <div style="display: flex; align-items: center; gap: 14px;">
            <span class="badge">Admin Restricted</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Registered Users</h3>
            <p><?= $user_count ?></p>
        </div>
        <div class="stat-card">
            <h3>Active Students</h3>
            <p><?= $student_count ?></p>
        </div>
        <div class="stat-card">
            <h3>Scheduled Events</h3>
            <p><?= $event_count ?></p>
        </div>
    </div>

    <div class="info-card">
        <h2 style="font-size: 18px; margin-bottom: 12px;">Security Verification Status</h2>
        <ul style="padding-left: 20px; line-height: 1.8; font-size: 14px; color: #334155;">
            <li><strong>Session ID Regeneration:</strong> Active (Protected against Session Fixation).</li>
            <li><strong>Inactivity Timeout:</strong> 15 minutes rolling timeout active (last activity: <?= date('H:i:s', $_SESSION['last_activity']) ?>).</li>
            <li><strong>Role-Based Redirection:</strong> Verified (Students are denied access and redirected).</li>
            <li><strong>Remember-Me Handler:</strong> Cryptographic token hash stored in database.</li>
        </ul>
    </div>
</main>

</body>
</html>
