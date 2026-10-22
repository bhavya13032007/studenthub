<?php
/**
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, Analytics & Audit Log
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/dynamic_menu.php';

// Strict Role Guard
require_role('admin');

// 1. Dashboard Aggregation Queries
$total_users = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$active_students = $conn->query("SELECT COUNT(*) AS c FROM students WHERE status='active'")->fetch_assoc()['c'];
$total_events = $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];
$total_registrations = $conn->query("SELECT COUNT(*) AS c FROM event_registrations")->fetch_assoc()['c'];

// 2. Fetch Users List for Status Toggle
$users_list = $conn->query("SELECT id, username, email, full_name, role, status, last_login, created_at FROM users ORDER BY id ASC");

// 3. Fetch Recent Audit Logs
$audit_logs = $conn->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 10");

// 4. Registration statistics by Event for Chart.js
$chart_query = "SELECT e.title, COUNT(er.id) AS reg_count FROM events e LEFT JOIN event_registrations er ON e.id = er.event_id GROUP BY e.id";
$chart_result = $conn->query($chart_query);
$chart_labels = [];
$chart_data = [];
while ($row = $chart_result->fetch_assoc()) {
    $chart_labels[] = $row['title'];
    $chart_data[] = (int)$row['reg_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 14: Comprehensive Admin Dashboard - 25DCE075</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        .sidebar { width: 270px; background: #0f172a; color: white; padding: 24px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .brand { font-size: 18px; font-weight: 700; color: #a5b4fc; padding-bottom: 16px; border-bottom: 1px solid #1e293b; margin-bottom: 20px; }
        .dynamic-nav { list-style: none; }
        .dynamic-nav li { margin-bottom: 6px; }
        .dynamic-nav a { display: block; color: #94a3b8; text-decoration: none; padding: 10px 14px; border-radius: 6px; font-size: 14px; transition: background 0.2s, color 0.2s; }
        .dynamic-nav a:hover, .dynamic-nav a.active { background: #1e293b; color: white; }
        .user-tag { background: #1e293b; padding: 12px; border-radius: 8px; font-size: 13px; }
        .main { flex: 1; padding: 32px 40px; overflow-y: auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .btn-logout { background: #ef4444; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .badge { background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .metric-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .metric-card h3 { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .metric-card p { font-size: 26px; font-weight: 700; margin-top: 8px; color: var(--text); }
        .content-grid { display: grid; grid-template-columns: 1fr; gap: 28px; margin-bottom: 28px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background: #f8fafc; border-bottom: 1px solid var(--border); }
        th, td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid var(--border); }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .btn-toggle { padding: 4px 10px; font-size: 11px; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-toggle-deactivate { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-toggle-activate { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="brand">CHARUSAT Admin Portal</div>
        <!-- Dynamic Role-Based Menu Generated by PHP Function -->
        <?php render_dynamic_menu('dashboard'); ?>
    </div>
    <div class="user-tag">
        <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong><br>
        <span style="color: #94a3b8; font-size: 12px;">Role: <?= htmlspecialchars($_SESSION['role']) ?></span><br>
        <span style="color: #818cf8; font-size: 11px;">Student ID: 25DCE075</span>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <span class="badge">Practical - 14 | 25DCE075</span>
            <h1 style="font-size: 24px; margin-top: 6px;">Executive Dashboard & Audit Log</h1>
        </div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Total Users</h3>
            <p><?= $total_users ?></p>
        </div>
        <div class="metric-card">
            <h3>Active Students</h3>
            <p><?= $active_students ?></p>
        </div>
        <div class="metric-card">
            <h3>Active Events</h3>
            <p><?= $total_events ?></p>
        </div>
        <div class="metric-card">
            <h3>Total Registrations</h3>
            <p><?= $total_registrations ?></p>
        </div>
    </div>

    <!-- Advanced Extension: Analytics Chart using Chart.js -->
    <div class="card" style="margin-bottom: 28px;">
        <h2>📊 Event Registration Distribution (Advanced Analytics Extension)</h2>
        <div style="height: 280px;">
            <canvas id="registrationChart"></canvas>
        </div>
    </div>

    <!-- Intermediate Extension: User Status Management & Toggle -->
    <div class="card" id="users_section" style="margin-bottom: 28px;">
        <h2>👥 User Access Management (Intermediate Status Toggle Extension)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span style="text-transform: capitalize;"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td>
                            <span class="status-badge status-<?= $u['status'] ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($u['last_login'] ?? 'Never') ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <?php if ($u['status'] === 'active'): ?>
                                    <a href="toggle_user.php?id=<?= $u['id'] ?>" class="btn-toggle btn-toggle-deactivate" onclick="return confirm('Deactivate user account?');">Deactivate</a>
                                <?php else: ?>
                                    <a href="toggle_user.php?id=<?= $u['id'] ?>" class="btn-toggle btn-toggle-activate" onclick="return confirm('Activate user account?');">Activate</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 11px; color: var(--muted);">(Current User)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Security Audit Logs Table -->
    <div class="card" id="audit_section">
        <h2>🛡️ System Audit Trail (audit_logs Table)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $audit_logs->fetch_assoc()): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><strong><?= htmlspecialchars($log['username'] ?? 'System/Guest') ?></strong></td>
                        <td><code><?= htmlspecialchars($log['action']) ?></code></td>
                        <td><?= htmlspecialchars($log['description']) ?></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
// Chart.js initialization for event registrations
const ctx = document.getElementById('registrationChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Student Registrations',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: '#6366f1',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

</body>
</html>
