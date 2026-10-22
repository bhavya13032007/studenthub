<?php
/**
 * Practical 10: Secure Login with password_verify(), session_regenerate_id(), and Remember-Me
 * Student ID: 25DCE075
 */
require_once __DIR__ . '/auth_check.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: student_dashboard.php");
        exit();
    }
}

$error = '';
$info = '';

// Check URL query parameters for feedback messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'timeout') {
        $info = "Your session expired due to 15 minutes of inactivity. Please log in again.";
    } elseif ($_GET['msg'] === 'unauthorized') {
        $error = "Access denied! Please log in to view that page.";
    } elseif ($_GET['msg'] === 'forbidden') {
        $error = "Unauthorized role! You do not have permission to view that resource.";
    } elseif ($_GET['msg'] === 'logged_out') {
        $info = "You have been logged out securely.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = "CSRF Token validation failed! Please refresh.";
    } else {
        $identity = trim($_POST['identity'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        if (empty($identity) || empty($password)) {
            $error = "Please enter both username/email and password.";
        } else {
            // Find user by username OR email
            $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE (username = ? OR email = ?) LIMIT 1");
            $stmt->bind_param("ss", $identity, $identity);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is deactivated. Contact the administrator.";
                } elseif (password_verify($password, $user['password'])) {
                    // Password verified! Prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // Intermediate Extension: Update last_login timestamp
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    // Advanced Extension: Handle Remember-Me Securely
                    if ($remember_me) {
                        $raw_token = bin2hex(random_bytes(32));
                        $token_hash = hash('sha256', $raw_token);
                        $expires_at = date('Y-m-d H:i:s', time() + (30 * 86400)); // 30 days validity

                        $tok_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                        $tok_stmt->bind_param("iss", $user['id'], $token_hash, $expires_at);
                        $tok_stmt->execute();
                        $tok_stmt->close();

                        // Set HttpOnly, Secure Cookie: userId:rawToken
                        setcookie(
                            'remember_token',
                            $user['id'] . ':' . $raw_token,
                            [
                                'expires' => time() + (30 * 86400),
                                'path' => '/',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );
                    }

                    // Audit Log
                    log_audit_action($conn, $user['id'], 'LOGIN_SUCCESS', "User logged in with role: {$user['role']}");

                    // Role-Based Redirection
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: student_dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid credentials. Password did not match.";
                    log_audit_action($conn, null, 'LOGIN_FAILED', "Failed login attempt for identifier: {$identity}");
                }
            } else {
                $error = "Invalid credentials. No user found.";
            }
            $stmt->close();
        }
    }
}
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 10: Secure Login - 25DCE075</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #cbd5e1;
            --danger: #dc2626;
            --success: #16a34a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: var(--card-bg); width: 100%; max-width: 440px; padding: 36px 30px; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid var(--border); }
        .header { text-align: center; margin-bottom: 24px; }
        .badge { display: inline-block; padding: 4px 10px; background: #dbeafe; color: #1e40af; border-radius: 9999px; font-size: 12px; font-weight: 600; margin-bottom: 8px; }
        .header h1 { font-size: 24px; color: var(--text); }
        .header p { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .alert { padding: 12px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .checkbox-group { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; margin: 12px 0 20px 0; }
        .btn { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: var(--primary-dark); }
        .credentials-box { margin-top: 24px; padding: 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 12px; color: #475569; line-height: 1.5; }
        .credentials-box strong { color: #0f172a; }
        .footer-links { text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <span class="badge">Practical - 10 | 25DCE075</span>
        <h1>Account Sign In</h1>
        <p>Role-Based Authentication with Secure Sessions</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
            <label for="identity">Username or Email</label>
            <input type="text" id="identity" name="identity" placeholder="e.g. admin or 25DCE075@charusat.edu.in" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="remember_me" name="remember_me">
            <label for="remember_me" style="margin-bottom: 0; font-weight: normal; cursor: pointer;">Remember me on this device (30 days)</label>
        </div>

        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="credentials-box">
        <strong>Test Credentials (from seed):</strong><br>
        &bull; <strong>Admin:</strong> <code>admin</code> / <code>AdminPassword123!</code><br>
        &bull; <strong>Student:</strong> <code>bhavya_patel</code> / <code>StudentPassword123!</code>
    </div>

    <div class="footer-links">
        Don't have an account? <a href="../Practical 09/register.php">Register here</a>
    </div>
</div>

</body>
</html>
