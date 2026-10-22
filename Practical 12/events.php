<?php
/**
 * Practical 12: Event Management CRUD with Poster Upload and File Validation
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$message = '';
$message_type = '';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    
    // Fetch image to delete if not default
    $img_stmt = $conn->prepare("SELECT poster_image FROM events WHERE id = ?");
    $img_stmt->bind_param("i", $event_id);
    $img_stmt->execute();
    $evt = $img_stmt->get_result()->fetch_assoc();
    $img_stmt->close();

    $del_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $del_stmt->bind_param("i", $event_id);
    if ($del_stmt->execute()) {
        if (!empty($evt['poster_image']) && $evt['poster_image'] !== 'default_event.png') {
            $filepath = __DIR__ . '/uploads/' . $evt['poster_image'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        $message = "Event deleted successfully.";
        $message_type = "success";
        log_audit_action($conn, $_SESSION['user_id'] ?? null, 'EVENT_DELETED', "Event ID {$event_id} was deleted.");
    } else {
        $message = "Failed to delete event: " . $conn->error;
        $message_type = "danger";
    }
    $del_stmt->close();
}

// Intermediate Extension: Filters (category and open/closed status)
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$query = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY event_date ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result();

// Categories for filter
$cats = $conn->query("SELECT DISTINCT category FROM events");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 12: Event Management CRUD - 25DCE075</title>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .badge { background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .btn { padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .filter-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 24px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .event-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .poster-container { height: 180px; width: 100%; background: #e2e8f0; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .poster-img { width: 100%; height: 100%; object-fit: cover; }
        .poster-placeholder { color: #94a3b8; font-weight: 600; font-size: 14px; }
        .status-pill { position: absolute; top: 12px; right: 12px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pill-open { background: #dcfce7; color: #166534; }
        .pill-closed { background: #fee2e2; color: #991b1b; }
        .event-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
        .category-tag { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary); margin-bottom: 6px; }
        .event-title { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .event-desc { font-size: 13px; color: var(--muted); line-height: 1.5; margin-bottom: 14px; flex: 1; }
        .event-meta { font-size: 12px; color: #475569; margin-bottom: 14px; border-top: 1px solid #f1f5f9; padding-top: 10px; }
        .event-actions { display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <span class="badge">Practical - 12 | 25DCE075</span>
            <h1 style="font-size: 22px; margin-top: 4px;">Event Management CRUD & Poster Upload</h1>
        </div>
        <a href="add_event.php" class="btn btn-primary">➕ Create New Event</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" class="filter-card">
        <label style="font-size: 13px; font-weight: 600;">Filter by:</label>
        <select name="category">
            <option value="">All Categories</option>
            <?php while ($cat = $cats->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($cat['category']) ?>" <?= ($category_filter === $cat['category']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="status">
            <option value="">All Statuses</option>
            <option value="open" <?= ($status_filter === 'open') ? 'selected' : '' ?>>Open Events</option>
            <option value="closed" <?= ($status_filter === 'closed') ? 'selected' : '' ?>>Closed Events</option>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if (!empty($category_filter) || !empty($status_filter)): ?>
            <a href="events.php" class="btn btn-secondary btn-sm" style="background:#94a3b8;">Reset</a>
        <?php endif; ?>
    </form>

    <div class="events-grid">
        <?php if ($events->num_rows > 0): ?>
            <?php while ($e = $events->fetch_assoc()): ?>
                <div class="event-card">
                    <div class="poster-container">
                        <span class="status-pill pill-<?= $e['status'] ?>"><?= $e['status'] ?></span>
                        <?php if (!empty($e['poster_image']) && file_exists(__DIR__ . '/uploads/' . $e['poster_image'])): ?>
                            <img src="uploads/<?= htmlspecialchars($e['poster_image']) ?>" alt="Event Poster" class="poster-img">
                        <?php else: ?>
                            <div class="poster-placeholder">🖼️ No Poster Uploaded</div>
                        <?php endif; ?>
                    </div>
                    <div class="event-body">
                        <div class="category-tag"><?= htmlspecialchars($e['category']) ?></div>
                        <h2 class="event-title"><?= htmlspecialchars($e['title']) ?></h2>
                        <p class="event-desc"><?= htmlspecialchars(substr($e['description'], 0, 110)) ?>...</p>
                        <div class="event-meta">
                            <div>📍 <strong>Venue:</strong> <?= htmlspecialchars($e['venue']) ?></div>
                            <div style="margin-top: 4px;">📅 <strong>Date:</strong> <?= date('M d, Y', strtotime($e['event_date'])) ?></div>
                        </div>
                        <div class="event-actions">
                            <a href="edit_event.php?id=<?= $e['id'] ?>" class="btn btn-primary btn-sm">Edit Event</a>
                            <a href="events.php?action=delete&id=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete event: <?= htmlspecialchars($e['title']) ?>?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 10px; border: 1px solid var(--border); color: var(--muted);">
                No events match the selected criteria. Click "Create New Event" to add one!
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
