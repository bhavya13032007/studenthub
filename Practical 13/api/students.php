<?php
/**
 * Practical 13: RESTful JSON API Endpoint for Students
 * Student ID: 25DCE075
 */
header('Content-Type: application/json; charset=UTF-8');

// Include DB config
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper to return standardized JSON response with HTTP code
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------
// GET: Fetch All Students or Single Student with Search Support
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            send_json(['status' => 'success', 'data' => $student], 200);
        } else {
            send_json(['status' => 'error', 'message' => 'Student not found'], 404);
        }
    }

    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE status='active' AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ? OR course LIKE ?) ORDER BY id DESC");
        $param = "%{$search}%";
        $stmt->bind_param("ssss", $param, $param, $param, $param);
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE status='active' ORDER BY id DESC LIMIT 50");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    send_json([
        'status' => 'success',
        'count' => count($students),
        'data' => $students
    ], 200);
}

// -------------------------------------------------------------
// POST: Create, Update, or Delete via Action parameter or Body
// -------------------------------------------------------------
if ($method === 'POST') {
    // Read JSON payload or standard POST
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true) ?? $_POST;

    $action = $input['action'] ?? 'create';

    // 1. DELETE
    if ($action === 'delete') {
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            send_json(['status' => 'error', 'message' => 'Invalid student ID'], 400);
        }

        $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            send_json(['status' => 'success', 'message' => 'Student soft-deleted successfully'], 200);
        } else {
            send_json(['status' => 'error', 'message' => 'Failed to delete student'], 500);
        }
    }

    // 2. UPDATE
    if ($action === 'update') {
        $id = intval($input['id'] ?? 0);
        $full_name = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $mobile = trim($input['mobile'] ?? '');
        $course = trim($input['course'] ?? '');
        $semester = intval($input['semester'] ?? 1);
        $gender = $input['gender'] ?? 'Male';

        if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_json(['status' => 'error', 'message' => 'Valid name and email are required'], 422);
        }

        $stmt = $conn->prepare("UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, semester = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("ssssisi", $full_name, $email, $mobile, $course, $semester, $gender, $id);
        if ($stmt->execute()) {
            send_json(['status' => 'success', 'message' => 'Student updated successfully'], 200);
        } else {
            send_json(['status' => 'error', 'message' => 'Database error: ' . $conn->error], 500);
        }
    }

    // 3. CREATE
    if ($action === 'create') {
        $enrollment_no = trim($input['enrollment_no'] ?? '');
        $full_name = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $mobile = trim($input['mobile'] ?? '');
        $course = trim($input['course'] ?? '');
        $semester = intval($input['semester'] ?? 1);
        $gender = $input['gender'] ?? 'Male';

        if (empty($enrollment_no) || empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_json(['status' => 'error', 'message' => 'Validation error: All required fields must be properly formatted.'], 422);
        }

        // Duplicate check
        $chk = $conn->prepare("SELECT id FROM students WHERE enrollment_no = ? OR email = ?");
        $chk->bind_param("ss", $enrollment_no, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            send_json(['status' => 'error', 'message' => 'Enrollment number or email already in use.'], 409);
        }
        $chk->close();

        $stmt = $conn->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, semester, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssis", $enrollment_no, $full_name, $email, $mobile, $course, $semester, $gender);
        if ($stmt->execute()) {
            send_json(['status' => 'success', 'message' => 'Student created successfully', 'id' => $stmt->insert_id], 201);
        } else {
            send_json(['status' => 'error', 'message' => 'Failed to save student: ' . $conn->error], 500);
        }
    }
}

send_json(['status' => 'error', 'message' => 'Unsupported HTTP method'], 405);
