<?php
/**
 * Practical 11: Student Management CRUD with Search, Filter & Soft Delete
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$message = '';
$message_type = '';

// Handle Soft Delete / Restore action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    // Advanced Extension: Soft delete using status = 'deleted'
    $del_stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
    $del_stmt->bind_param("i", $student_id);
    if ($del_stmt->execute()) {
        $message = "Student record marked as deleted successfully (Soft Delete).";
        $message_type = "success";
        log_audit_action($conn, $_SESSION['user_id'] ?? null, 'STUDENT_SOFT_DELETED', "Student ID {$student_id} was soft-deleted.");
    } else {
        $message = "Error deleting student: " . $conn->error;
        $message_type = "danger";
    }
    $del_stmt->close();
}

if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $res_stmt = $conn->prepare("UPDATE students SET status = 'active' WHERE id = ?");
    $res_stmt->bind_param("i", $student_id);
    if ($res_stmt->execute()) {
        $message = "Student record restored successfully.";
        $message_type = "success";
        log_audit_action($conn, $_SESSION['user_id'] ?? null, 'STUDENT_RESTORED', "Student ID {$student_id} was restored.");
    }
    $res_stmt->close();
}

// Search and Filter parameters
$search = trim($_GET['search'] ?? '');
$course_filter = trim($_GET['course'] ?? '');
$status_filter = trim($_GET['status'] ?? 'active');

// Build query dynamically with prepared statements
$query = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($course_filter)) {
    $query .= " AND course = ?";
    $params[] = $course_filter;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

// Distinct courses for filter dropdown
$courses_result = $conn->query("SELECT DISTINCT course FROM students ORDER BY course ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 11: Student Management CRUD - 25DCE075</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 30px 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .badge { background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .action-bar { background: var(--card); padding: 18px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1; }
        input[type="text"], select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        input[type="text"] { min-width: 240px; }
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: #64748b; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .table-card { background: var(--card); border-radius: 10px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background: #f1f5f9; border-bottom: 1px solid var(--border); }
        th, td { padding: 12px 14px; font-size: 13px; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        tbody tr:hover { background: #f8fafc; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-deleted { background: #fee2e2; color: #991b1b; }
        .back-nav { margin-top: 24px; font-size: 13px; }
        .back-nav a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <div>
            <span class="badge">Practical - 11 | 25DCE075</span>
            <h1 style="font-size: 22px; margin-top: 4px;">Student Management CRUD Module</h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="export_csv.php" class="btn btn-secondary">📥 Export CSV (Intermediate)</a>
            <a href="add_student.php" class="btn btn-primary">➕ Add New Student</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="action-bar">
        <form method="GET" action="students.php" class="filter-form">
            <input type="text" name="search" placeholder="Search by name, email, or enrollment..." value="<?= htmlspecialchars($search) ?>">
            
            <select name="course">
                <option value="">All Courses</option>
                <?php while ($c = $courses_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c['course']) ?>" <?= ($course_filter === $c['course']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="status">
                <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Active Records</option>
                <option value="deleted" <?= ($status_filter === 'deleted') ? 'selected' : '' ?>>Soft-Deleted Records</option>
                <option value="" <?= ($status_filter === '') ? 'selected' : '' ?>>All Statuses</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter / Search</button>
            <?php if (!empty($search) || !empty($course_filter) || $status_filter !== 'active'): ?>
                <a href="students.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Enrollment No</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Sem</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($s = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><strong><?= htmlspecialchars($s['enrollment_no']) ?></strong></td>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['mobile']) ?></td>
                            <td><?= htmlspecialchars($s['course']) ?></td>
                            <td><?= $s['semester'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $s['status'] ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                    <a href="edit_student.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="students.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete student: <?= htmlspecialchars($s['full_name']) ?>?');">Delete</a>
                                <?php else: ?>
                                    <a href="students.php?action=restore&id=<?= $s['id'] ?>" class="btn btn-success btn-sm">Restore</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--muted); padding: 30px;">
                            No student records found matching the filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="back-nav">
        <a href="../practical_10_auth_sessions/admin_dashboard.php">&larr; Back to Admin Dashboard</a>
    </div>
</div>

</body>
</html>
