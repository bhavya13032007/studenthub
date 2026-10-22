<?php
/**
 * Practical 11: Add Student Record
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    $gender = $_POST['gender'] ?? 'Male';

    // Validation
    if (empty($enrollment_no)) $errors[] = "Enrollment number is required.";
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = "Valid 10-digit mobile number required.";
    if (empty($course)) $errors[] = "Course is required.";

    // Check unique enrollment & email
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM students WHERE enrollment_no = ? OR email = ?");
        $chk->bind_param("ss", $enrollment_no, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = "A student with this enrollment number or email already exists.";
        }
        $chk->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, semester, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssis", $enrollment_no, $full_name, $email, $mobile, $course, $semester, $gender);
        if ($stmt->execute()) {
            log_audit_action($conn, $_SESSION['user_id'] ?? null, 'STUDENT_CREATED', "New student created: {$enrollment_no}");
            header("Location: students.php");
            exit();
        } else {
            $errors[] = "Database insert error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student - Practical 11 (25DCE075)</title>
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --card: #ffffff; --border: #cbd5e1; }
        * { box-sizing: border-box; font-family: sans-serif; }
        body { background: var(--bg); padding: 40px 20px; display: flex; justify-content: center; }
        .card { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 10px; width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
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
    <h2 style="margin-bottom: 15px;">Add New Student</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e) echo "<div>• " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Enrollment Number</label>
            <input type="text" name="enrollment_no" placeholder="e.g. 25DCE099" required>
        </div>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="e.g. Yash Soni" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="e.g. yash@example.com" required>
        </div>
        <div class="form-group">
            <label>Mobile Number</label>
            <input type="text" name="mobile" placeholder="10 digit mobile" required>
        </div>
        <div class="form-group">
            <label>Course</label>
            <input type="text" name="course" placeholder="e.g. Computer Engineering" required>
        </div>
        <div class="form-group">
            <label>Semester</label>
            <select name="semester">
                <?php for($i=1; $i<=8; $i++): ?>
                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Student Record</button>
        <p style="text-align: center; margin-top: 15px; font-size: 13px;"><a href="students.php" style="color: var(--primary);">Cancel & Return</a></p>
    </form>
</div>
</body>
</html>
