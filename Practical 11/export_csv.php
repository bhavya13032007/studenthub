<?php
/**
 * Practical 11: Export Student Records to CSV
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

// Fetch all active students
$result = $conn->query("SELECT id, enrollment_no, full_name, email, mobile, course, semester, gender, status, created_at FROM students ORDER BY id ASC");

// Set download headers
$filename = "students_export_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV Column Headers
fputcsv($output, ['ID', 'Enrollment No', 'Full Name', 'Email', 'Mobile', 'Course', 'Semester', 'Gender', 'Status', 'Created At']);

// Output rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
