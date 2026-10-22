<?php
/**
 * Practical 12: Add Event with Poster Upload & File Validation
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $status = $_POST['status'] ?? 'open';

    if (empty($title)) $errors[] = "Event title is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";
    if (empty($venue)) $errors[] = "Venue is required.";

    $poster_filename = null;

    // File Upload Validation (Key Requirement)
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file. Error code: " . $file['error'];
        } else {
            // 1. File Size Validation (Max 2MB = 2 * 1024 * 1024 bytes)
            $max_size = 2 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $errors[] = "File size exceeds 2MB limit. Current size: " . round($file['size'] / 1024, 2) . " KB.";
            }

            // 2. MIME Type Validation using finfo (Server-side inspection, not relying on client extension)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($mime_type, $allowed_mimes) || !in_array($ext, $allowed_exts)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and WebP images are allowed. Detected: {$mime_type}.";
            }

            // 3. Cryptographically Secure File Renaming (Advanced Extension)
            if (empty($errors)) {
                $poster_filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                $target_path = __DIR__ . '/uploads/' . $poster_filename;

                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $errors[] = "Failed to save uploaded file to destination directory.";
                    $poster_filename = null;
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, venue, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $category, $description, $event_date, $venue, $poster_filename, $status);
        if ($stmt->execute()) {
            log_audit_action($conn, $_SESSION['user_id'] ?? null, 'EVENT_CREATED', "New event created: {$title}");
            header("Location: events.php");
            exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event - Practical 12 (25DCE075)</title>
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
        .help-text { font-size: 12px; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>
<div class="card">
    <h2 style="margin-bottom: 15px;">Create New Event</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e) echo "<div>• " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" name="title" placeholder="e.g. Hackathon 2026" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Seminar">Seminar</option>
                <option value="Cultural">Cultural</option>
                <option value="Competition">Competition</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Provide event details..." required></textarea>
        </div>
        <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" required>
        </div>
        <div class="form-group">
            <label>Venue</label>
            <input type="text" name="venue" placeholder="e.g. Central Auditorium" required>
        </div>
        <div class="form-group">
            <label>Event Poster Image (File Validation Enabled)</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
            <div class="help-text">Max size: 2MB. Allowed types: JPG, PNG, WebP.</div>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="open">Open for Registrations</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <button type="submit" class="btn">Publish Event</button>
        <p style="text-align: center; margin-top: 15px; font-size: 13px;"><a href="events.php" style="color: var(--primary);">Cancel</a></p>
    </form>
</div>
</body>
</html>
