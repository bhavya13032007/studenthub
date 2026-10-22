<?php
/**
 * Practical 11: Edit Student Record
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: students.php");
    exit();
}

// Fetch existing record
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: students.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    $gender = $_POST['gender'] ?? 'Male';
    $status = $_POST['status'] ?? 'active';

    if (empty($full_name)) $errors[] = "Full name cannot be blank.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($mobile)) $errors[] = "Mobile number is required.";

    // Check duplicate email for other students
    $chk = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $chk->bind_param("si", $email, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $errors[] = "This email is already in use by another student.";
    }
    $chk->close();

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, semester = ?, gender = ?, status = ? WHERE id = ?");
        $upd->bind_param("ssssissi", $full_name, $email, $mobile, $course, $semester, $gender, $status, $id);
        if ($upd->execute()) {
            log_audit_action($conn, $_SESSION['user_id'] ?? null, 'STUDENT_UPDATED', "Student ID {$id} updated.");
            header("Location: students.php");
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
    <title>Edit Student - Practical 11 (25DCE075)</title>
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --card: #ffffff; --border: #cbd5e1; }
        * { box-sizing: border-box; font-family: sans-serif; }
        body { background: var(--bg); padding: 40px 20px; display: flex; justify-content: center; }
        .card { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 10px; width: 100%; max-width: 500px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        input, select { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .btn { padding: 10px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: var(--primary); color: white; width: 100%; margin-top: 10px; }
        .alert-danger { background: #fee2e2; color: #991b1b; padding: 10px 14px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
<div class="card">
    <h2 style="margin-bottom: 15px;">Edit Student (<?= htmlspecialchars($student['enrollment_no']) ?>)</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e) echo "<div>• " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Enrollment Number (Read Only)</label>
            <input type="text" value="<?= htmlspecialchars($student['enrollment_no']) ?>" disabled style="background: #e2e8f0;">
        </div>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Mobile Number</label>
            <input type="text" name="mobile" value="<?= htmlspecialchars($student['mobile']) ?>" required>
        </div>
        <div class="form-group">
            <label>Course</label>
            <input type="text" name="course" value="<?= htmlspecialchars($student['course']) ?>" required>
        </div>
        <div class="form-group">
            <label>Semester</label>
            <select name="semester">
                <?php for($i=1; $i<=8; $i++): ?>
                    <option value="<?= $i ?>" <?= ($student['semester'] == $i)?'selected':'' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender">
                <option value="Male" <?= ($student['gender'] == 'Male')?'selected':'' ?>>Male</option>
                <option value="Female" <?= ($student['gender'] == 'Female')?'selected':'' ?>>Female</option>
                <option value="Other" <?= ($student['gender'] == 'Other')?'selected':'' ?>>Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= ($student['status'] == 'active')?'selected':'' ?>>Active</option>
                <option value="deleted" <?= ($student['status'] == 'deleted')?'selected':'' ?>>Deleted (Soft-delete)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Student Record</button>
        <p style="text-align: center; margin-top: 15px; font-size: 13px;"><a href="students.php" style="color: var(--primary);">Cancel</a></p>
    </form>
</div>
</body>
</html>
