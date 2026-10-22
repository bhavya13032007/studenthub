<?php
/**
 * Practical 9: Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/db.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token (Advanced Extension)
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "CSRF Token validation failed! Please refresh the page.";
    }

    // 2. Collect and Sanitize Inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    // 3. Backend Validation (Consistent with Frontend)
    if (empty($full_name) || strlen($full_name) < 3) {
        $errors[] = "Full Name must be at least 3 characters long.";
    }
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $errors[] = "Username must be 4-20 alphanumeric characters (underscores allowed).";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!in_array($role, ['admin', 'student'])) {
        $role = 'student';
    }

    // 4. Duplicate Check for Username and Email (Using Prepared Statements)
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['username'] === $username) {
                $errors[] = "Username is already taken. Please choose another.";
            }
            if ($existing_user['email'] === $email) {
                $errors[] = "An account with this email already exists.";
            }
        }
        $check_stmt->close();
    }

    // 5. Hash Password & Insert Record
    if (empty($errors)) {
        // Secure Password Hashing
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $insert_stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

        if ($insert_stmt->execute()) {
            $new_user_id = $insert_stmt->insert_id;
            
            // Advanced Extension: Log registration audit action
            log_audit_action($conn, $new_user_id, 'USER_REGISTERED', "New user registered with username: {$username} and email: {$email}");

            $success_message = "Registration successful! You can now log in.";
            // Clear input fields on success
            $full_name = $username = $email = '';
        } else {
            $errors[] = "Database error during registration: " . $conn->error;
        }
        $insert_stmt->close();
    }
}
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 9: Secure User Registration - 25DCE075</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 40px 20px; display: flex; justify-content: center; }
        .card { background: var(--card-bg); width: 100%; max-width: 520px; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); border: 1px solid var(--border); }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { font-size: 24px; color: var(--text); }
        .header p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .badge { display: inline-block; padding: 4px 10px; background: #e0e7ff; color: #4338ca; border-radius: 9999px; font-size: 12px; font-weight: 600; margin-bottom: 8px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; transition: border-color 0.2s; }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .input-error { border-color: var(--danger) !important; }
        .error-text { color: var(--danger); font-size: 12px; margin-top: 4px; display: none; }
        .password-strength { height: 4px; width: 100%; background: #e2e8f0; border-radius: 2px; margin-top: 6px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s, background-color 0.3s; }
        .strength-text { font-size: 11px; color: var(--text-muted); margin-top: 4px; text-align: right; }
        .btn { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn:hover { background: var(--primary-dark); }
        .links { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted); }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <span class="badge">Practical - 9 | 25DCE075</span>
        <h1>Create New Account</h1>
        <p>Secure registration with password hashing & duplicate check</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?>
            <div style="margin-top: 8px;">
                <a href="../Practical 10/login.php" style="color: #166534; font-weight: bold;">Proceed to Login &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" id="regForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" placeholder="e.g. Bhavya Patel" required>
            <div class="error-text" id="err-full_name">Please enter at least 3 characters.</div>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" placeholder="e.g. krishna_026" required>
            <div class="error-text" id="err-username">4-20 alphanumeric characters allowed.</div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="name@charusat.edu.in" required>
            <div class="error-text" id="err-email">Please enter a valid email address.</div>
        </div>

        <div class="form-group">
            <label for="role">Register As</label>
            <select id="role" name="role">
                <option value="student">Student</option>
                <option value="admin">Administrator</option>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
            <div class="password-strength">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
            <div class="strength-text" id="strengthText">Enter password</div>
            <div class="error-text" id="err-password">Password must meet security standards.</div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
            <div class="error-text" id="err-confirm_password">Passwords do not match.</div>
        </div>

        <button type="submit" class="btn" id="submitBtn">Register Account</button>
    </form>

    <div class="links">
        Already registered? <a href="../Practical 10/login.php">Login here</a>
    </div>
</div>

<script>
// Frontend Validation & Strength Meter
const form = document.getElementById('regForm');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

// Realtime Password Strength Evaluation
passwordInput.addEventListener('input', function() {
    const val = passwordInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[\W]/.test(val)) score++;

    let width = '0%', color = '#ef4444', label = 'Very Weak';
    if (score === 1 || score === 2) {
        width = '30%'; color = '#ef4444'; label = 'Weak';
    } else if (score === 3 || score === 4) {
        width = '70%'; color = '#f59e0b'; label = 'Medium';
    } else if (score === 5) {
        width = '100%'; color = '#10b981'; label = 'Strong (Secure)';
    }

    strengthBar.style.width = width;
    strengthBar.style.backgroundColor = color;
    strengthText.textContent = label;
});

// Form Submit Validation
form.addEventListener('submit', function(e) {
    let isValid = true;
    
    function validateField(id, condition, errId) {
        const input = document.getElementById(id);
        const err = document.getElementById(errId);
        if (!condition) {
            input.classList.add('input-error');
            err.style.display = 'block';
            isValid = false;
        } else {
            input.classList.remove('input-error');
            err.style.display = 'none';
        }
    }

    validateField('full_name', document.getElementById('full_name').value.trim().length >= 3, 'err-full_name');
    validateField('username', /^[a-zA-Z0-9_]{4,20}$/.test(document.getElementById('username').value.trim()), 'err-username');
    validateField('email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email').value.trim()), 'err-email');
    validateField('password', passwordInput.value.length >= 8 && /[A-Z]/.test(passwordInput.value) && /[0-9]/.test(passwordInput.value), 'err-password');
    validateField('confirm_password', confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value !== '', 'err-confirm_password');

    if (!isValid) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
