<?php
/**
 * Practical 12: Edit Event with Optional Poster Replacement
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: events.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: events.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $status = $_POST['status'] ?? 'open';
    $poster_filename = $event['poster_image'];

    if (empty($title)) $errors[] = "Event title is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";

    // If new poster uploaded, validate and replace
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = "Poster size cannot exceed 2MB.";
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($mime_type, $allowed_mimes) || !in_array($ext, $allowed_exts)) {
                $errors[] = "Invalid image type. Allowed: JPG, PNG, WebP.";
            }

            if (empty($errors)) {
                $new_filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $new_filename)) {
                    // Delete old file if present
                    if (!empty($event['poster_image']) && file_exists(__DIR__ . '/uploads/' . $event['poster_image'])) {
                        unlink(__DIR__ . '/uploads/' . $event['poster_image']);
                    }
                    $poster_filename = $new_filename;
                } else {
                    $errors[] = "Failed to upload new poster file.";
                }
            }
        }
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE events SET title = ?, category = ?, description = ?, event_date = ?, venue = ?, poster_image = ?, status = ? WHERE id = ?");
        $upd->bind_param("sssssssi", $title, $category, $description, $event_date, $venue, $poster_filename, $status, $id);
        if ($upd->execute()) {
            log_audit_action($conn, $_SESSION['user_id'] ?? null, 'EVENT_UPDATED', "Event ID {$id} updated.");
            header("Location: events.php");
            exit();
        } else {
            $errors[] = "Update failed: " . $conn->error;
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - Practical 12 (25DCE075)</title>
    <style>
        :root { --primary: #8b5cf6; --bg: #f8fafc; --card: #ffffff; --border: #cbd5e1; }
        * { box-sizing: border-box; font-family: sans-serif; }
        body { background: var(--bg); padding: 40px 20px; display: flex; justify-content: center; }
        .card { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 10px; width: 100%; max-width: 550px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .btn { padding: 10px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; background: var(--primary); color: white; width: 100%; margin-top: 10px; }
        .alert-danger { background: #fee2e2; color: #991b1b; padding: 10px 14px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .current-poster { margin-top: 8px; width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
<div class="card">
    <h2 style="margin-bottom: 15px;">Edit Event</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e) echo "<div>• " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="Technical" <?= ($event['category'] === 'Technical')?'selected':'' ?>>Technical</option>
                <option value="Workshop" <?= ($event['category'] === 'Workshop')?'selected':'' ?>>Workshop</option>
                <option value="Seminar" <?= ($event['category'] === 'Seminar')?'selected':'' ?>>Seminar</option>
                <option value="Cultural" <?= ($event['category'] === 'Cultural')?'selected':'' ?>>Cultural</option>
                <option value="Competition" <?= ($event['category'] === 'Competition')?'selected':'' ?>>Competition</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" required><?= htmlspecialchars($event['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" value="<?= htmlspecialchars($event['event_date']) ?>" required>
        </div>
        <div class="form-group">
            <label>Venue</label>
            <input type="text" name="venue" value="<?= htmlspecialchars($event['venue']) ?>" required>
        </div>
        <div class="form-group">
            <label>Replace Poster (Leave blank to keep existing)</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
            <?php if (!empty($event['poster_image']) && file_exists(__DIR__ . '/uploads/' . $event['poster_image'])): ?>
                <div>
                    <img src="uploads/<?= htmlspecialchars($event['poster_image']) ?>" class="current-poster" alt="Current Poster">
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="open" <?= ($event['status'] === 'open')?'selected':'' ?>>Open</option>
                <option value="closed" <?= ($event['status'] === 'closed')?'selected':'' ?>>Closed</option>
            </select>
        </div>
        <button type="submit" class="btn">Save Event Changes</button>
        <p style="text-align: center; margin-top: 15px; font-size: 13px;"><a href="events.php" style="color: var(--primary);">Cancel</a></p>
    </form>
</div>
</body>
</html>
