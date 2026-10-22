<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Submission Result</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: rgba(248, 252, 255, 0.95); }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); border: 1px solid rgba(13, 47, 87, 0.1); }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { display: inline-block; padding: 10px 15px; text-decoration: none; background-color: rgb(13, 47, 87); color: white; border-radius: 4px; font-weight: bold; }
        .btn:hover { background-color: rgb(70, 130, 180); }
    </style>
</head>
<body>
    <div class="container">
        <?php
        function posted_string($key) {
            return isset($_POST[$key]) && is_string($_POST[$key])
                ? trim($_POST[$key])
                : '';
        }

        function legacy_student_value($student, $key) {
            $value = $student[$key] ?? '';
            return is_scalar($value) ? (string)$value : '';
        }

        function xlsx_column_name($column) {
            $name = '';
            while ($column > 0) {
                $column--;
                $name = chr(65 + ($column % 26)) . $name;
                $column = intdiv($column, 26);
            }
            return $name;
        }

        function xlsx_cell($value, $column, $row) {
            $cell = xlsx_column_name($column) . $row;
            $value = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
            return '<c r="' . $cell . '" t="inlineStr"><is><t xml:space="preserve">' .
                $value . '</t></is></c>';
        }

        function create_students_workbook($csv_handle, $xlsx_file) {
            if (!class_exists('PharData')) {
                return false;
            }

            rewind($csv_handle);
            $sheet_data = '<sheetData>';
            $row_number = 0;
            while (($record = fgetcsv($csv_handle, 0, ',', '"', '')) !== false) {
                $row_number++;
                $sheet_data .= '<row r="' . $row_number . '">';
                foreach ($record as $index => $value) {
                    $sheet_data .= xlsx_cell($value, $index + 1, $row_number);
                }
                $sheet_data .= '</row>';
            }
            $sheet_data .= '</sheetData>';

            $temporary_base = tempnam(dirname($xlsx_file), 'students_');
            if ($temporary_base === false) {
                return false;
            }
            $temporary_file = $temporary_base . '.zip';
            if (!unlink($temporary_base)) {
                return false;
            }

            $files = [
                '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
                    '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
                    '<Default Extension="xml" ContentType="application/xml"/>' .
                    '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
                    '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
                    '</Types>',
                '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
                    '</Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
                    'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
                    '<sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
                    '</Relationships>',
                'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
                    $sheet_data . '</worksheet>'
            ];

            try {
                $archive = new PharData($temporary_file, 0, null, Phar::ZIP);
                foreach ($files as $path => $contents) {
                    $archive->addFromString($path, $contents);
                }
                unset($archive);
            } catch (PharException | UnexpectedValueException $error) {
                unlink($temporary_file);
                return false;
            }

            if (!is_file($temporary_file) || filesize($temporary_file) === 0) {
                unlink($temporary_file);
                return false;
            }

            if (file_exists($xlsx_file) && !unlink($xlsx_file)) {
                unlink($temporary_file);
                return false;
            }

            if (!rename($temporary_file, $xlsx_file)) {
                unlink($temporary_file);
                return false;
            }

            return true;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            $full_name = posted_string('full_name');
            $student_id = posted_string('student_id');
            $department = posted_string('department');
            $course = posted_string('course');
            $semester = posted_string('semester');
            $email = posted_string('email');
            $phone = posted_string('phone');

            if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $full_name)) {
                $errors[] = "Name must be 3-50 characters with letters and spaces only.";
            }
            if (!preg_match('/^[a-zA-Z0-9]{5,10}$/', $student_id)) {
                $errors[] = "Student ID must be alphanumeric (5-10 characters).";
            }
            if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $department)) {
                $errors[] = "Department must be letters and spaces only (2-50 characters).";
            }
            if (!preg_match('/^[a-zA-Z0-9.\s]{2,50}$/', $course)) {
                $errors[] = "Course must be letters, numbers, and dots (2-50 characters).";
            }
            if (!preg_match('/^[1-8]$/', $semester)) {
                $errors[] = "Semester must be a number (1-8).";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            }
            if (!preg_match('/^[0-9]{10}$/', $phone)) {
                $errors[] = "Phone must be exactly 10 digits.";
            }

            $photo_name = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo_name = basename($_FILES['photo']['name']);
                } else {
                    $errors[] = "The profile photo could not be uploaded.";
                }
            }

            if (empty($errors)) {
                if (!class_exists('PharData')) {
                    http_response_code(500);
                    echo '<div class="message error"><h3>Excel Support Is Not Enabled</h3><p>Enable the PHP Phar extension in your XAMPP configuration, then restart Apache.</p></div>';
                } else {
                    $csv_file = __DIR__ . '/../json/students.csv';
                    $xlsx_file = __DIR__ . '/../json/students.xlsx';
                    $csv_handle = fopen($csv_file, 'c+');

                    if ($csv_handle === false) {
                        http_response_code(500);
                        echo '<div class="message error"><h3>Internal Error</h3><p>Could not open the records file. Please check the folder permissions.</p></div>';
                    } else {
                        if (!flock($csv_handle, LOCK_EX)) {
                            fclose($csv_handle);
                            http_response_code(500);
                            echo '<div class="message error"><h3>Internal Error</h3><p>Could not lock the CSV file. Please try again.</p></div>';
                        } else {
                            fseek($csv_handle, 0, SEEK_END);
                            $is_empty = ftell($csv_handle) === 0;
                            $saved = true;
                            $migration_failed = false;
                            $legacy_students = [];

                            if ($is_empty && file_exists(__DIR__ . '/../json/students.json')) {
                                $legacy_json = file_get_contents(__DIR__ . '/../json/students.json');
                                if ($legacy_json === false) {
                                    $saved = false;
                                    $migration_failed = true;
                                } else {
                                    $legacy_students = json_decode($legacy_json, true);
                                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($legacy_students)) {
                                        $saved = false;
                                        $migration_failed = true;
                                    } else {
                                        foreach ($legacy_students as $student) {
                                            if (!is_array($student)) {
                                                $saved = false;
                                                $migration_failed = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            if ($is_empty && $saved) {
                                $saved = fputcsv($csv_handle, [
                                    'Full Name',
                                    'Student ID',
                                    'Department',
                                    'Course',
                                    'Semester',
                                    'Email',
                                    'Phone',
                                    'Photo Filename',
                                    'Registration Date'
                                ], ',', '"', '') !== false;
                            }

                            if ($saved && $is_empty) {
                                foreach ($legacy_students as $student) {
                                    $saved = fputcsv($csv_handle, [
                                        legacy_student_value($student, 'name'),
                                        legacy_student_value($student, 'student_id_code'),
                                        legacy_student_value($student, 'branch'),
                                        legacy_student_value($student, 'course'),
                                        legacy_student_value($student, 'semester'),
                                        legacy_student_value($student, 'email'),
                                        legacy_student_value($student, 'phone'),
                                        legacy_student_value($student, 'photo'),
                                        legacy_student_value($student, 'registration_date')
                                    ], ',', '"', '') !== false;

                                    if (!$saved) {
                                        break;
                                    }
                                }
                            }

                            if ($saved) {
                                $saved = fputcsv($csv_handle, [
                                    $full_name,
                                    $student_id,
                                    $department,
                                    $course,
                                    (int)$semester,
                                    $email,
                                    $phone,
                                    $photo_name,
                                    date('Y-m-d H:i:s')
                                ], ',', '"', '') !== false;
                            }

                            fflush($csv_handle);
                            $workbook_saved = $saved && create_students_workbook($csv_handle, $xlsx_file);
                            flock($csv_handle, LOCK_UN);
                            fclose($csv_handle);

                            if ($workbook_saved) {
                                echo '<div class="message success"><h3>Profile Submitted Successfully!</h3><p>Your validated profile has been saved to the Excel workbook.</p></div>';
                            } elseif ($saved) {
                                http_response_code(500);
                                echo '<div class="message error"><h3>Excel Save Error</h3><p>The profile was saved to the CSV records, but the Excel workbook could not be updated. Check file permissions and try again.</p></div>';
                            } elseif ($migration_failed) {
                                http_response_code(500);
                                echo '<div class="message error"><h3>Existing Records Could Not Be Loaded</h3><p>The existing students.json file could not be read or did not contain valid student records. No new profile was added.</p></div>';
                            } else {
                                http_response_code(500);
                                echo '<div class="message error"><h3>Internal Error</h3><p>Could not save the profile. Please check file permissions.</p></div>';
                            }
                        }
                    }
                }
            } else {
                echo '<div class="message error"><h3>Validation Errors</h3><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                }
                echo '</ul></div>';
            }
        } else {
            echo '<div class="message error"><h3>Invalid Request</h3><p>Please submit the form using the POST method.</p></div>';
        }
        ?>
        
        <br>
        <div style="display: flex; gap: 10px;">
            <a href="profile.html" class="btn">Go Back to Profile</a>
            <a href="../json/students.xlsx" class="btn">Download Excel Records</a>
        </div>
    </div>
</body>
</html>
